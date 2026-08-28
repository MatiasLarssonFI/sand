const assert = require('node:assert/strict');
const PIDController = require('../js/pid.js');

let passed = 0;
let failed = 0;

function test(name, fn) {
  try {
    fn();
    passed += 1;
    console.log(`ok   - ${name}`);
  } catch (err) {
    failed += 1;
    console.error(`FAIL - ${name}`);
    console.error(err && err.stack ? err.stack : err);
  }
}

test('proportional-only response scales with error', () => {
  const pid = new PIDController({ kp: 2, ki: 0, kd: 0 });
  const output = pid.update(10, 4, 1); // error = 6
  assert.equal(output, 12);
});

test('proportional-only output is zero when measurement equals setpoint', () => {
  const pid = new PIDController({ kp: 5, ki: 0, kd: 0 });
  const output = pid.update(3, 3, 1);
  assert.equal(output, 0);
});

test('integral term accumulates error over time (step response)', () => {
  const pid = new PIDController({ kp: 0, ki: 1, kd: 0 });
  const out1 = pid.update(1, 0, 1); // integral = 1
  const out2 = pid.update(1, 0, 1); // integral = 2
  const out3 = pid.update(1, 0, 1); // integral = 3
  assert.equal(out1, 1);
  assert.equal(out2, 2);
  assert.equal(out3, 3);
});

test('integral term is not accumulated when dt is 0', () => {
  const pid = new PIDController({ kp: 0, ki: 1, kd: 0 });
  pid.update(1, 0, 1); // integral = 1
  const output = pid.update(1, 0, 0); // dt = 0, no accumulation
  assert.equal(output, 1);
});

test('anti-windup clamps the integral accumulator', () => {
  const pid = new PIDController({
    kp: 0,
    ki: 1,
    kd: 0,
    integralMin: -5,
    integralMax: 5,
  });
  for (let i = 0; i < 10; i += 1) {
    pid.update(1, 0, 1); // error = 1 each step, integral would grow to 10 unclamped
  }
  const output = pid.update(1, 0, 1);
  assert.equal(output, 5, 'integral should be clamped at integralMax');
});

test('derivative-on-measurement responds to changing measurement, ignores setpoint jumps', () => {
  const pid = new PIDController({ kp: 0, ki: 0, kd: 1, derivativeOnMeasurement: true });
  pid.update(0, 0, 1); // establishes previous measurement, no rate yet
  const output = pid.update(0, 5, 1); // measurement rate = +5/s -> d = -kd * 5 = -5
  assert.equal(output, -5);
});

test('derivative-on-measurement produces no kick on a sudden setpoint change', () => {
  const pid = new PIDController({ kp: 0, ki: 0, kd: 1, derivativeOnMeasurement: true });
  pid.update(0, 0, 1); // establish previous measurement
  const output = pid.update(100, 0, 1); // setpoint jumps, measurement unchanged
  assert.equal(output, 0, 'derivative-on-measurement should ignore setpoint jumps');
});

test('derivative-on-error responds to changing error when configured', () => {
  const pid = new PIDController({ kp: 0, ki: 0, kd: 1, derivativeOnMeasurement: false });
  pid.update(10, 0, 1); // error = 10, establishes previous error
  const output = pid.update(10, 5, 1); // error = 5, rate = (5-10)/1 = -5 -> d = -5
  assert.equal(output, -5);
});

test('first update has no derivative contribution', () => {
  const pid = new PIDController({ kp: 0, ki: 0, kd: 10 });
  const output = pid.update(5, 0, 1);
  assert.equal(output, 0);
});

test('zero dt produces no derivative or integral contribution beyond proportional', () => {
  const pid = new PIDController({ kp: 1, ki: 1, kd: 1 });
  const output = pid.update(10, 0, 0);
  assert.equal(output, 10, 'only proportional term should contribute when dt is 0');
});

test('negative dt throws a RangeError', () => {
  const pid = new PIDController();
  assert.throws(() => pid.update(1, 0, -1), RangeError);
});

test('non-finite dt throws a RangeError', () => {
  const pid = new PIDController();
  assert.throws(() => pid.update(1, 0, NaN), RangeError);
  assert.throws(() => pid.update(1, 0, Infinity), RangeError);
});

test('output is clamped between outputMin and outputMax', () => {
  const pid = new PIDController({ kp: 10, outputMin: -1, outputMax: 1 });
  assert.equal(pid.update(100, 0, 1), 1);
  assert.equal(pid.update(-100, 0, 1), -1);
});

test('negative gains invert the response direction', () => {
  const pid = new PIDController({ kp: -1, ki: 0, kd: 0 });
  const output = pid.update(10, 0, 1); // error = 10, kp = -1
  assert.equal(output, -10);
});

test('reset clears integral accumulator and derivative memory', () => {
  const pid = new PIDController({ kp: 0, ki: 1, kd: 0 });
  pid.update(1, 0, 1);
  pid.update(1, 0, 1);
  pid.reset();
  const output = pid.update(1, 0, 1); // integral should restart from 0
  assert.equal(output, 1);
});

test('combined PID terms sum correctly', () => {
  const pid = new PIDController({ kp: 1, ki: 1, kd: 1, derivativeOnMeasurement: false });
  // First call: error = 5, integral = 5, derivative = 0 (no previous error).
  const out1 = pid.update(5, 0, 1);
  assert.equal(out1, 5 + 5 + 0);
  // Second call: measurement moves to 5 -> error = 0, integral += 0 = 5,
  // derivative = (0 - 5) / 1 = -5.
  const out2 = pid.update(5, 5, 1);
  assert.equal(out2, 0 + 5 + -5);
});

test('static clamp throws when min > max', () => {
  assert.throws(() => PIDController.clamp(1, 5, 0), RangeError);
});

console.log(`\n${passed} passed, ${failed} failed`);
if (failed > 0) {
  process.exitCode = 1;
}
