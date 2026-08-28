/**
 * PIDController
 *
 * A pure, DOM-agnostic implementation of a PID (Proportional-Integral-Derivative)
 * controller. Given a setpoint and a measured process variable, it computes a
 * control output intended to drive the process variable towards the setpoint.
 *
 * Features:
 * - Configurable Kp / Ki / Kd gains (mutable at runtime for live tuning).
 * - Anti-windup via clamping of the accumulated integral term.
 * - Optional output clamping (min/max) to keep actuation within safe bounds.
 * - Derivative-on-measurement (default) to avoid "derivative kick" from sudden
 *   setpoint changes; derivative-on-error is also supported via options.
 */
class PIDController {
  /**
   * @param {object} [options]
   * @param {number} [options.kp=1] Proportional gain.
   * @param {number} [options.ki=0] Integral gain.
   * @param {number} [options.kd=0] Derivative gain.
   * @param {number} [options.integralMin=-Infinity] Lower clamp for the integral accumulator (anti-windup).
   * @param {number} [options.integralMax=Infinity] Upper clamp for the integral accumulator (anti-windup).
   * @param {number} [options.outputMin=-Infinity] Lower clamp for the controller output.
   * @param {number} [options.outputMax=Infinity] Upper clamp for the controller output.
   * @param {boolean} [options.derivativeOnMeasurement=true] Compute derivative from the
   *   process variable instead of the error, to avoid derivative kick on setpoint changes.
   */
  constructor(options = {}) {
    const {
      kp = 1,
      ki = 0,
      kd = 0,
      integralMin = -Infinity,
      integralMax = Infinity,
      outputMin = -Infinity,
      outputMax = Infinity,
      derivativeOnMeasurement = true,
    } = options;

    this.kp = kp;
    this.ki = ki;
    this.kd = kd;

    this.integralMin = integralMin;
    this.integralMax = integralMax;
    this.outputMin = outputMin;
    this.outputMax = outputMax;
    this.derivativeOnMeasurement = derivativeOnMeasurement;

    this.reset();
  }

  /** Reset internal state (integral accumulator and previous-value memory). */
  reset() {
    this._integral = 0;
    this._prevError = null;
    this._prevMeasurement = null;
  }

  /**
   * Clamp a value between min and max.
   * @param {number} value
   * @param {number} min
   * @param {number} max
   * @returns {number}
   */
  static clamp(value, min, max) {
    if (min > max) {
      throw new RangeError('min must not be greater than max');
    }
    return Math.min(Math.max(value, min), max);
  }

  /**
   * Compute the next control output.
   *
   * @param {number} setpoint Desired target value.
   * @param {number} measurement Current measured process variable.
   * @param {number} dt Elapsed time in seconds since the previous update. Must be >= 0.
   * @returns {number} The clamped control output.
   */
  update(setpoint, measurement, dt) {
    if (!Number.isFinite(dt) || dt < 0) {
      throw new RangeError('dt must be a finite number >= 0');
    }

    const error = setpoint - measurement;

    // Proportional term.
    const p = this.kp * error;

    // Integral term with anti-windup clamping. Skip accumulation when dt is 0
    // (no elapsed time means no meaningful contribution).
    if (dt > 0) {
      this._integral += error * dt;
      this._integral = PIDController.clamp(this._integral, this.integralMin, this.integralMax);
    }
    const i = this.ki * this._integral;

    // Derivative term. On the very first update there is no previous value to
    // differentiate against, so the derivative contribution is zero.
    let d = 0;
    if (dt > 0) {
      if (this.derivativeOnMeasurement) {
        if (this._prevMeasurement !== null) {
          const rate = (measurement - this._prevMeasurement) / dt;
          // Derivative-on-measurement approximates d(error)/dt as -d(measurement)/dt
          // (assuming a constant setpoint), avoiding spikes from setpoint jumps.
          d = -this.kd * rate;
        }
      } else if (this._prevError !== null) {
        const rate = (error - this._prevError) / dt;
        d = this.kd * rate;
      }
    }

    this._prevError = error;
    this._prevMeasurement = measurement;

    const output = p + i + d;
    return PIDController.clamp(output, this.outputMin, this.outputMax);
  }
}

// Support both CommonJS (Node/tests) and browser <script> usage.
if (typeof module !== 'undefined' && module.exports) {
  module.exports = PIDController;
}
