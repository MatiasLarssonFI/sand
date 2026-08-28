/**
 * DomActuator
 *
 * Applies PID controller output to real DOM element style properties. Keeps
 * per-property clamping and unit formatting in one place so demo wiring code
 * stays declarative: describe which elements/properties a PID loop drives,
 * and this layer handles writing to the DOM efficiently via requestAnimationFrame.
 */
class DomActuator {
  /**
   * @param {HTMLElement} element Target element to actuate.
   */
  constructor(element) {
    this.element = element;
    element.style.willChange = 'transform';
  }

  /**
   * Translate the element using a CSS transform (GPU-friendly, avoids layout thrash).
   * @param {number} x Horizontal translation in pixels.
   * @param {number} y Vertical translation in pixels.
   * @param {object} [extra] Additional transform components.
   * @param {number} [extra.scale] Uniform scale factor.
   * @param {number} [extra.rotateDeg] Rotation in degrees.
   */
  setTransform(x, y, extra = {}) {
    const { scale, rotateDeg } = extra;
    let transform = `translate(${x}px, ${y}px)`;
    if (typeof scale === 'number') {
      transform += ` scale(${scale})`;
    }
    if (typeof rotateDeg === 'number') {
      transform += ` rotate(${rotateDeg}deg)`;
    }
    this.element.style.transform = transform;
  }

  /**
   * Set opacity, clamped to the valid [0, 1] range.
   * @param {number} value
   */
  setOpacity(value) {
    this.element.style.opacity = String(PIDController_clamp(value, 0, 1));
  }

  /**
   * Set background color using an HSL hue value (wraps 0-360).
   * @param {number} hue Hue in degrees; wrapped into [0, 360).
   * @param {number} [saturation=70] Percentage.
   * @param {number} [lightness=55] Percentage.
   */
  setHue(hue, saturation = 70, lightness = 55) {
    const wrapped = ((hue % 360) + 360) % 360;
    this.element.style.backgroundColor = `hsl(${wrapped}deg ${saturation}% ${lightness}%)`;
  }

  /**
   * Set pixel size (width/height).
   * @param {number} size Size in pixels; negative values are clamped to 0.
   */
  setSize(size) {
    const clamped = Math.max(0, size);
    this.element.style.width = `${clamped}px`;
    this.element.style.height = `${clamped}px`;
  }
}

/**
 * Local clamp helper so this module has no hard dependency on PIDController.
 * @param {number} value
 * @param {number} min
 * @param {number} max
 * @returns {number}
 */
function PIDController_clamp(value, min, max) {
  return Math.min(Math.max(value, min), max);
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = DomActuator;
}
