<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
requireLogin();

$username = (string) currentUser();
$data = loadResumeData($username);
$template = (int) ($data['template'] ?? '1');
if ($template < 1 || $template > 10) {
    $template = 1;
}

$eduItems = array_filter(array_map('trim', explode("\n", (string) ($data['education'] ?? ''))));
$expItems = array_filter(array_map('trim', explode("\n", (string) ($data['experience'] ?? ''))));
$skillItems = array_filter(array_map('trim', explode(',', (string) ($data['skills'] ?? ''))));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resume Preview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-body-secondary">
<div class="container py-4">
    <div class="d-flex justify-content-between mb-3 no-print">
        <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        <button class="btn btn-success" id="downloadPdfBtn"><i class="fa-solid fa-file-pdf"></i> Download PDF</button>
    </div>

    <div class="resume resume-template-<?= $template ?>" id="resumeRoot">
        <header class="mb-3">
            <h1 class="mb-1"><?= h((string) ($data['full_name'] ?? 'Your Name')) ?></h1>
            <h2 class="h5 text-muted"><?= h((string) ($data['title'] ?? 'Job Title')) ?></h2>
            <p class="mb-0"><i class="fa-solid fa-envelope"></i> <?= h((string) ($data['email'] ?? '-')) ?> | <i class="fa-solid fa-phone"></i> <?= h((string) ($data['phone'] ?? '-')) ?></p>
        </header>

        <section class="mb-3">
            <h3>Summary</h3>
            <p><?= nl2br(h((string) ($data['summary'] ?? ''))) ?></p>
        </section>

        <section class="mb-3">
            <h3>Experience</h3>
            <ul>
                <?php foreach ($expItems as $item): ?>
                    <li><?= h($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="mb-3">
            <h3>Education</h3>
            <ul>
                <?php foreach ($eduItems as $item): ?>
                    <li><?= h($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section>
            <h3>Skills</h3>
            <ul>
                <?php foreach ($skillItems as $item): ?>
                    <li><?= h($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
