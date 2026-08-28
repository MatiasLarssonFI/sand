/**
 * main.js
 *
 * Bootstraps all interactive PID demos on the page once the DOM is ready,
 * and drives a single shared requestAnimationFrame loop that passes a
 * variable timestep (dt, in seconds) to each demo's update() function.
 */
document.addEventListener('DOMContentLoaded', () => {
  const deps = { PIDController, InputSource, DomActuator, PidChart };

  const demos = [
    typeof createFollowDemo === 'function' ? createFollowDemo(deps) : null,
    typeof createKeyboardDemo === 'function' ? createKeyboardDemo(deps) : null,
    typeof createWindupDemo === 'function' ? createWindupDemo(deps) : null,
  ].filter(Boolean);

  let lastTime = performance.now();
  let running = true;

  function frame(now) {
    // Clamp dt to avoid huge jumps (e.g. when the tab was backgrounded),
    // which would otherwise cause large, unrealistic integral/derivative spikes.
    const dt = Math.min((now - lastTime) / 1000, 0.1);
    lastTime = now;

    if (running) {
      for (const demo of demos) {
        demo.update(dt);
      }
    }

    requestAnimationFrame(frame);
  }

  // Pause simulation updates while the tab is hidden to save battery/CPU;
  // resume with a fresh lastTime reference to avoid a large dt spike.
  document.addEventListener('visibilitychange', () => {
    running = !document.hidden;
    if (running) {
      lastTime = performance.now();
    }
  });

  requestAnimationFrame(frame);
});
