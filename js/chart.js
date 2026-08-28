/**
 * PidChart
 *
 * A minimal, dependency-free line chart rendered on a <canvas>, used to plot
 * setpoint vs. process-variable (and optionally raw error) over time for a
 * PID demo. Not part of the PID math itself — purely a visualization helper.
 */
class PidChart {
  /**
   * @param {HTMLCanvasElement} canvas
   * @param {object} [options]
   * @param {number} [options.maxPoints=200] Number of samples retained/drawn.
   * @param {number} [options.min=0] Y-axis minimum.
   * @param {number} [options.max=100] Y-axis maximum.
   */
  constructor(canvas, options = {}) {
    this.canvas = canvas;
    this.ctx = canvas.getContext('2d');
    this.maxPoints = options.maxPoints || 200;
    this.min = options.min ?? 0;
    this.max = options.max ?? 100;
    this.setpoints = [];
    this.measurements = [];
  }

  /**
   * Record a new sample.
   * @param {number} setpoint
   * @param {number} measurement
   */
  push(setpoint, measurement) {
    this.setpoints.push(setpoint);
    this.measurements.push(measurement);
    if (this.setpoints.length > this.maxPoints) {
      this.setpoints.shift();
      this.measurements.shift();
    }
  }

  /** Clear all recorded samples. */
  clear() {
    this.setpoints.length = 0;
    this.measurements.length = 0;
  }

  /** Redraw the chart based on current samples. Call once per frame. */
  draw() {
    const { ctx, canvas } = this;
    if (!ctx) return; // Canvas 2D context unavailable in this environment.
    const width = canvas.width;
    const height = canvas.height;
    ctx.clearRect(0, 0, width, height);

    ctx.strokeStyle = 'rgba(255,255,255,0.08)';
    ctx.lineWidth = 1;
    for (let i = 1; i < 4; i += 1) {
      const y = (height / 4) * i;
      ctx.beginPath();
      ctx.moveTo(0, y);
      ctx.lineTo(width, y);
      ctx.stroke();
    }

    this._drawSeries(this.setpoints, '#6cf27a');
    this._drawSeries(this.measurements, '#4da3ff');
  }

  _drawSeries(series, color) {
    if (series.length < 2) return;
    const { ctx, canvas } = this;
    const width = canvas.width;
    const height = canvas.height;
    const range = this.max - this.min || 1;

    ctx.strokeStyle = color;
    ctx.lineWidth = 2;
    ctx.beginPath();
    series.forEach((value, index) => {
      const x = (index / (this.maxPoints - 1)) * width;
      const normalized = (value - this.min) / range;
      const y = height - normalized * height;
      if (index === 0) {
        ctx.moveTo(x, y);
      } else {
        ctx.lineTo(x, y);
      }
    });
    ctx.stroke();
  }
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = PidChart;
}
