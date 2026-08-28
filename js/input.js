/**
 * InputSource
 *
 * Normalizes raw browser input (mouse movement, keyboard, scroll, touch) into
 * simple numeric signals that can be used as PID setpoints or disturbances.
 * Each InputSource instance is scoped to a container element and exposes
 * getters for the latest normalized values; it does not know anything about
 * PID controllers or DOM actuation.
 */
class InputSource {
  /**
   * @param {HTMLElement} [container=document.body] Element used as the coordinate frame for pointer input.
   */
  constructor(container = document.body) {
    this.container = container;

    // Pointer position, normalized to the container's bounding box, in pixels
    // relative to the container's top-left corner. Defaults to the container
    // center until the user moves the pointer.
    this._pointerX = 0;
    this._pointerY = 0;

    // Bitmask-like set of currently pressed keys (by KeyboardEvent.key).
    this._keys = new Set();

    // Cumulative scroll delta (pixels), reset-able via resetScroll().
    this._scrollDelta = 0;

    this._onPointerMove = this._onPointerMove.bind(this);
    this._onKeyDown = this._onKeyDown.bind(this);
    this._onKeyUp = this._onKeyUp.bind(this);
    this._onWheel = this._onWheel.bind(this);

    this._attach();
    this._centerPointer();
  }

  _centerPointer() {
    const rect = this.container.getBoundingClientRect();
    this._pointerX = rect.width / 2;
    this._pointerY = rect.height / 2;
  }

  _attach() {
    this.container.addEventListener('pointermove', this._onPointerMove);
    window.addEventListener('keydown', this._onKeyDown);
    window.addEventListener('keyup', this._onKeyUp);
    this.container.addEventListener('wheel', this._onWheel, { passive: true });
  }

  /** Remove all event listeners. Call when the InputSource is no longer needed. */
  destroy() {
    this.container.removeEventListener('pointermove', this._onPointerMove);
    window.removeEventListener('keydown', this._onKeyDown);
    window.removeEventListener('keyup', this._onKeyUp);
    this.container.removeEventListener('wheel', this._onWheel);
  }

  _onPointerMove(event) {
    const rect = this.container.getBoundingClientRect();
    this._pointerX = event.clientX - rect.left;
    this._pointerY = event.clientY - rect.top;
  }

  _onKeyDown(event) {
    this._keys.add(event.key);
  }

  _onKeyUp(event) {
    this._keys.delete(event.key);
  }

  _onWheel(event) {
    this._scrollDelta += event.deltaY;
  }

  /** @returns {{x: number, y: number}} Latest pointer position relative to the container. */
  get pointer() {
    return { x: this._pointerX, y: this._pointerY };
  }

  /**
   * Check whether a key is currently held down.
   * @param {string} key KeyboardEvent.key value, e.g. 'ArrowUp'.
   * @returns {boolean}
   */
  isKeyDown(key) {
    return this._keys.has(key);
  }

  /**
   * Signed axis derived from two opposing keys, useful as a PID setpoint delta.
   * @param {string} positiveKey Key that increases the axis (e.g. 'ArrowRight').
   * @param {string} negativeKey Key that decreases the axis (e.g. 'ArrowLeft').
   * @returns {number} -1, 0, or 1.
   */
  keyAxis(positiveKey, negativeKey) {
    const positive = this.isKeyDown(positiveKey) ? 1 : 0;
    const negative = this.isKeyDown(negativeKey) ? 1 : 0;
    return positive - negative;
  }

  /** @returns {number} Cumulative scroll delta since the last reset. */
  get scrollDelta() {
    return this._scrollDelta;
  }

  /** Reset the cumulative scroll delta to zero. */
  resetScroll() {
    this._scrollDelta = 0;
  }
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = InputSource;
}
