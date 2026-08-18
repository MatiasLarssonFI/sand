<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($appName ?? 'Shared Calendar', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<?= $content ?? '' ?>
</body>
</html>
