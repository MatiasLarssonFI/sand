/**
 * Keyboard demo
 *
 * Arrow keys (Up/Down) nudge a target size; Left/Right nudge a target
 * rotation. A single PIDController per axis smooths the discrete key-press
 * input into continuous DOM changes, illustrating that a PID loop can act as
 * a smoothing/rate-limiting actuator even when its "setpoint" changes in
 * abrupt steps. Uses derivative-on-measurement (the PIDController default)
 * specifically to avoid derivative kick from these step changes.
 *
 * @param {object} deps
 * @param {typeof PIDController} deps.PIDController
 * @param {typeof InputSource} deps.InputSource
 * @param {typeof DomActuator} deps.DomActuator
 */
function createKeyboardDemo({ PIDController, InputSource, DomActuator }) {
  const stage = document.getElementById('keyboard-stage');
  const box = document.getElementById('keyboard-box');
  const hint = document.getElementById('keyboard-hint');

  if (!stage || !box) {
    return null;
  }

  const input = new InputSource(stage);
  const actuator = new DomActuator(box);

  const sizePid = new PIDController({
    kp: 4,
    ki: 0.5,
    kd: 0.8,
    integralMin: -200,
    integralMax: 200,
    outputMin: -400,
    outputMax: 400,
  });
  const rotationPid = new PIDController({
    kp: 4,
    ki: 0.2,
    kd: 0.6,
    outputMin: -720,
    outputMax: 720,
  });

  let targetSize = 100;
  let currentSize = 100;
  let targetRotation = 0;
  let currentRotation = 0;
  let hintTimer = 0;

  const MIN_SIZE = 40;
  const MAX_SIZE = 220;
  const SIZE_STEP_PER_SEC = 60;
  const ROTATION_STEP_PER_SEC = 90;

  function update(dt) {
    if (dt <= 0) return;

    const sizeAxis = input.keyAxis('ArrowUp', 'ArrowDown');
    const rotationAxis = input.keyAxis('ArrowRight', 'ArrowLeft');

    if (sizeAxis !== 0 || rotationAxis !== 0) {
      hintTimer = 2;
    }
    hintTimer = Math.max(0, hintTimer - dt);
    if (hint) {
      hint.style.opacity = hintTimer > 0 || (currentSize === 100 && currentRotation === 0) ? '1' : '0.35';
    }

    targetSize = PIDController.clamp(targetSize + sizeAxis * SIZE_STEP_PER_SEC * dt, MIN_SIZE, MAX_SIZE);
    targetRotation += rotationAxis * ROTATION_STEP_PER_SEC * dt;

    const sizeOut = sizePid.update(targetSize, currentSize, dt);
    currentSize += sizeOut * dt;
    currentSize = PIDController.clamp(currentSize, MIN_SIZE, MAX_SIZE);

    const rotationOut = rotationPid.update(targetRotation, currentRotation, dt);
    currentRotation += rotationOut * dt;

    actuator.setSize(currentSize);
    actuator.setTransform(0, 0, { rotateDeg: currentRotation });
    actuator.setHue(currentRotation);
  }

  return { update };
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = createKeyboardDemo;
}
