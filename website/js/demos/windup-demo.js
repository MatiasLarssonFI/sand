/**
 * Windup demo
 *
 * Illustrates integral windup: a horizontal "gauge" needle is PID-controlled
 * towards the mouse position, but its travel is physically clamped to a
 * narrow track (simulating an actuator with a hard limit). When the pointer
 * moves far outside the track and anti-windup is disabled, the integral term
 * keeps accumulating while the needle is pinned at its limit, causing a large
 * overshoot once the setpoint comes back into range. Toggling anti-windup on
 * clamps the integral accumulator and removes the overshoot.
 *
 * @param {object} deps
 * @param {typeof PIDController} deps.PIDController
 * @param {typeof InputSource} deps.InputSource
 * @param {typeof DomActuator} deps.DomActuator
 */
function createWindupDemo({ PIDController, InputSource, DomActuator }) {
  const track = document.getElementById('windup-track');
  const needle = document.getElementById('windup-needle');
  const toggle = document.getElementById('windup-toggle');
  const integralReadout = document.getElementById('windup-integral');

  if (!track || !needle) {
    return null;
  }

  const input = new InputSource(track);
  const actuator = new DomActuator(needle);
  const needleSize = needle.offsetWidth || 24;

  const TRACK_MIN = 0;
  let trackMax = Math.max(0, track.clientWidth - needleSize);

  const pid = new PIDController({
    kp: 3,
    ki: 2,
    kd: 0.5,
    outputMin: -2000,
    outputMax: 2000,
  });

  let position = 0;

  function applyAntiWindupBounds() {
    if (toggle.checked) {
      pid.integralMin = -80;
      pid.integralMax = 80;
    } else {
      pid.integralMin = -Infinity;
      pid.integralMax = Infinity;
    }
  }
  toggle.addEventListener('change', applyAntiWindupBounds);
  applyAntiWindupBounds();

  function update(dt) {
    if (dt <= 0) return;
    trackMax = Math.max(0, track.clientWidth - needleSize);

    // The setpoint follows the pointer but is allowed to travel far beyond
    // the track's physical bounds, mimicking a demand that exceeds actuator
    // capability.
    const setpoint = input.pointer.x - needleSize / 2;

    const output = pid.update(setpoint, position, dt);
    position += output * dt;
    // Hard actuator limit: the needle cannot physically leave the track.
    position = PIDController.clamp(position, TRACK_MIN, trackMax);

    actuator.setTransform(position, 0);
    integralReadout.textContent = pid._integral.toFixed(1);
  }

  return { update };
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = createWindupDemo;
}
