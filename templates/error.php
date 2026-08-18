<main class="shell">
    <section class="panel error-panel">
        <p class="eyebrow">Error <?= (int) $status ?></p>
        <h1>Something went wrong</h1>
        <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <p><a href="/">Return to the calendar</a></p>
    </section>
</main>
