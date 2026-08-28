/**
 * Follow demo
 *
 * A "ball" div chases the mouse pointer within a bounded arena. Both the X
 * and Y axes are driven by independent PIDController instances whose gains
 * are tied to shared Kp/Ki/Kd sliders, so visitors can directly feel the
 * effect of tuning: low Kp is sluggish, high Kp overshoots and oscillates,
 * Ki removes steady-state lag (or causes windup if overdone), Kd damps
 * oscillation.
 *
 * @param {object} deps
 * @param {typeof PIDController} deps.PIDController
 * @param {typeof InputSource} deps.InputSource
 * @param {typeof DomActuator} deps.DomActuator
 * @param {typeof PidChart} deps.PidChart
 */
function createFollowDemo({ PIDController, InputSource, DomActuator, PidChart }) {
  const arena = document.getElementById('follow-arena');
  const ball = document.getElementById('follow-ball');
  const kpInput = document.getElementById('follow-kp');
  const kiInput = document.getElementById('follow-ki');
  const kdInput = document.getElementById('follow-kd');
  const kpValue = document.getElementById('follow-kp-value');
  const kiValue = document.getElementById('follow-ki-value');
  const kdValue = document.getElementById('follow-kd-value');
  const errorReadout = document.getElementById('follow-error');
  const outputReadout = document.getElementById('follow-output');
  const pauseButton = document.getElementById('follow-pause');
  const resetButton = document.getElementById('follow-reset');
  const chartCanvas = document.getElementById('follow-chart');

  if (!arena || !ball) {
    return null;
  }

  const input = new InputSource(arena);
  const ballSize = ball.offsetWidth || 40;

  const makeAxisPid = () =>
    new PIDController({
      kp: parseFloat(kpInput.value),
      ki: parseFloat(kiInput.value),
      kd: parseFloat(kdInput.value),
      integralMin: -400,
      integralMax: 400,
      outputMin: -2000,
      outputMax: 2000,
    });

  let pidX = makeAxisPid();
  let pidY = makeAxisPid();
  const actuator = new DomActuator(ball);

  // Process-variable state: the ball's current position, in the arena's
  // coordinate frame (top-left origin), independent from the DOM transform
  // which is applied relative to the ball's natural flow position.
  let posX = 0;
  let posY = 0;
  let paused = false;

  const chart = chartCanvas
    ? new PidChart(chartCanvas, { maxPoints: 240, min: -50, max: 50 })
    : null;

  function syncGainsFromSliders() {
    const kp = parseFloat(kpInput.value);
    const ki = parseFloat(kiInput.value);
    const kd = parseFloat(kdInput.value);
    pidX.kp = kp;
    pidX.ki = ki;
    pidX.kd = kd;
    pidY.kp = kp;
    pidY.ki = ki;
    pidY.kd = kd;
    kpValue.textContent = kp.toFixed(2);
    kiValue.textContent = ki.toFixed(2);
    kdValue.textContent = kd.toFixed(2);
  }

  [kpInput, kiInput, kdInput].forEach((el) => {
    el.addEventListener('input', syncGainsFromSliders);
  });
  syncGainsFromSliders();

  function reset() {
    const rect = arena.getBoundingClientRect();
    posX = rect.width / 2 - ballSize / 2;
    posY = rect.height / 2 - ballSize / 2;
    pidX.reset();
    pidY.reset();
    if (chart) chart.clear();
    actuator.setTransform(posX, posY);
  }

  pauseButton.addEventListener('click', () => {
    paused = !paused;
    pauseButton.textContent = paused ? 'Resume' : 'Pause';
    pauseButton.setAttribute('aria-pressed', String(paused));
  });

  resetButton.addEventListener('click', reset);

  reset();

  /**
   * @param {number} dt Elapsed seconds since previous frame.
   */
  function update(dt) {
    if (paused || dt <= 0) return;

    const target = input.pointer;
    const targetX = target.x - ballSize / 2;
    const targetY = target.y - ballSize / 2;

    const outX = pidX.update(targetX, posX, dt);
    const outY = pidY.update(targetY, posY, dt);

    // Treat PID output as a velocity (px/s) applied over dt, integrated into position.
    posX += outX * dt;
    posY += outY * dt;

    const rect = arena.getBoundingClientRect();
    posX = Math.min(Math.max(posX, 0), Math.max(0, rect.width - ballSize));
    posY = Math.min(Math.max(posY, 0), Math.max(0, rect.height - ballSize));

    actuator.setTransform(posX, posY);

    const errorMag = Math.hypot(targetX - posX, targetY - posY);
    errorReadout.textContent = errorMag.toFixed(1);
    outputReadout.textContent = Math.hypot(outX, outY).toFixed(1);

    if (chart) {
      // Plot error (setpoint - measurement) around a zero baseline so the
      // chart clearly shows overshoot/oscillation/settling regardless of
      // where the pointer happens to be on screen.
      chart.push(0, targetX - posX);
      chart.draw();
    }
  }

  function onResize() {
    const rect = arena.getBoundingClientRect();
    posX = Math.min(posX, Math.max(0, rect.width - ballSize));
    posY = Math.min(posY, Math.max(0, rect.height - ballSize));
  }
  window.addEventListener('resize', onResize);

  return { update };
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = createFollowDemo;
}
