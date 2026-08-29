<main class="shell">
    <header class="topbar">
        <div>
            <p class="eyebrow">Shared calendar</p>
            <h1><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></h1>
        </div>
        <div class="topbar-actions">
            <label>
                Acting user
                <select id="user-switcher">
                    <?php foreach ($initialState['users'] as $user): ?>
                        <option value="<?= (int) $user['id'] ?>" <?= (int) $currentUserId === (int) $user['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button id="add-event-button" type="button">Add event</button>
        </div>
    </header>

    <section class="toolbar">
        <label>
            Calendar
            <select id="calendar-select"></select>
        </label>
        <label>
            View
            <select id="view-select">
                <option value="day">Day</option>
                <option value="week">Week</option>
                <option value="month">Month</option>
                <option value="n-weeks">N weeks</option>
            </select>
        </label>
        <label id="weeks-control">
            Weeks
            <input id="weeks-input" type="number" min="2" max="12" value="<?= (int) $defaultWeeks ?>">
        </label>
        <div class="toolbar-nav">
            <button id="previous-range" type="button">Previous</button>
            <button id="today-range" type="button">Today</button>
            <button id="next-range" type="button">Next</button>
        </div>
        <strong id="view-label"></strong>
    </section>

    <section class="content-grid">
        <section class="calendar-panel">
            <div id="calendar-grid" class="calendar-grid"></div>
        </section>
        <aside class="side-panel">
            <section class="panel">
                <h2>Sharing</h2>
                <div id="members-panel"></div>
                <form id="member-form" class="stack">
                    <label>
                        Add member
                        <select id="member-user" name="userId"></select>
                    </label>
                    <label>
                        Role
                        <select id="member-role" name="role">
                            <option value="viewer">Viewer</option>
                            <option value="editor">Editor</option>
                            <option value="owner">Owner</option>
                        </select>
                    </label>
                    <button type="submit">Share calendar</button>
                </form>
            </section>
            <section class="panel">
                <h2>Event details</h2>
                <form id="event-form" class="stack">
                    <input id="event-id" name="id" type="hidden">
                    <label>
                        Title
                        <input id="event-title" name="title" type="text" maxlength="120" required>
                    </label>
                    <label>
                        Description
                        <textarea id="event-description" name="description" rows="4"></textarea>
                    </label>
                    <label>
                        Start
                        <input id="event-start" name="start" type="datetime-local" required>
                    </label>
                    <label>
                        End
                        <input id="event-end" name="end" type="datetime-local" required>
                    </label>
                    <div class="form-actions">
                        <button type="submit">Save event</button>
                        <button id="delete-event-button" type="button" class="danger">Delete</button>
                        <button id="reset-event-button" type="button">Clear</button>
                    </div>
                </form>
                <p id="flash-message" class="flash-message" aria-live="polite"></p>
            </section>
        </aside>
    </section>
</main>

<script>
window.CALENDAR_APP = <?= json_encode([
    'csrfToken' => $csrfToken,
    'currentUserId' => $currentUserId,
    'defaultWeeks' => $defaultWeeks,
    'state' => $initialState,
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '{}' ?>;
</script>
<script src="/assets/app.js"></script>
