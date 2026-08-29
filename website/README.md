# This Page Is PID Regulated

A single-page, dependency-free website that teaches PID (Proportional–Integral–
Derivative) control theory by making the page itself the controlled system.
Real HTML elements — their position, size, rotation, and color — are process
variables driven by live PID loops. Your mouse, keyboard, and scroll wheel act
as the setpoint/disturbance.

Open `index.html` in a browser (or serve the folder statically) to try it.

## Project structure

- `index.html` — page markup and educational content.
- `css/styles.css` — layout and styling.
- `js/pid.js` — `PIDController`, a pure, DOM-agnostic PID implementation.
- `js/input.js` — `InputSource`, normalizes mouse/keyboard/scroll input.
- `js/actuator.js` — `DomActuator`, applies PID output to DOM style properties.
- `js/chart.js` — `PidChart`, a minimal canvas line chart for visualizing loops.
- `js/demos/*.js` — wiring for each interactive demo (input → PID → DOM).
- `js/main.js` — bootstraps all demos and drives a shared animation loop.
- `tests/pid.test.js` — unit tests for `PIDController`.

## Running tests

```sh
npm test
```

This runs `tests/pid.test.js` with plain Node.js assertions (no build step or
external test framework required) against the DOM-agnostic `PIDController`
class.
