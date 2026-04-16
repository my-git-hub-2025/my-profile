<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
requireLogin();

$username = (string) currentUser();
$data = loadResumeData($username);
$message = '';
$messageType = 'success';
$downloadToken = csrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $payload = [
            'template' => (string) ($_POST['template'] ?? '1'),
            'full_name' => trim((string) ($_POST['full_name'] ?? '')),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'summary' => trim((string) ($_POST['summary'] ?? '')),
            'education' => trim((string) ($_POST['education'] ?? '')),
            'experience' => trim((string) ($_POST['experience'] ?? '')),
            'skills' => trim((string) ($_POST['skills'] ?? '')),
        ];

        $template = (int) $payload['template'];
        if ($template < 1 || $template > 10) {
            $payload['template'] = '1';
        }

        saveResumeData($username, $payload);
        $data = loadResumeData($username);
        $message = 'Resume data saved.';
    } else {
        $message = 'Invalid request token. Refresh the page and try again.';
        $messageType = 'danger';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Resume Creator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand"><i class="fa-solid fa-file-lines"></i> Resume Creator</span>
        <div class="ms-auto">
            <span class="text-white me-3">Hi, <?= h($username) ?></span>
            <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= h($messageType) ?>"><?= h($message) ?></div>
    <?php endif; ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-3">Your Resume Details</h2>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= h($downloadToken) ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="full_name">Full Name</label>
                                <input id="full_name" class="form-control" name="full_name" value="<?= h((string) ($data['full_name'] ?? '')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="title">Job Title</label>
                                <input id="title" class="form-control" name="title" value="<?= h((string) ($data['title'] ?? '')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input id="email" type="email" class="form-control" name="email" autocomplete="email" value="<?= h((string) ($data['email'] ?? '')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Phone</label>
                                <input id="phone" class="form-control" name="phone" value="<?= h((string) ($data['phone'] ?? '')) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="summary">Professional Summary</label>
                                <textarea id="summary" class="form-control" name="summary" rows="3"><?= h((string) ($data['summary'] ?? '')) ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="education">Education</label>
                                <textarea id="education" class="form-control" name="education" rows="3" placeholder="One item per line"><?= h((string) ($data['education'] ?? '')) ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="experience">Experience</label>
                                <textarea id="experience" class="form-control" name="experience" rows="4" placeholder="One item per line"><?= h((string) ($data['experience'] ?? '')) ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="skills">Skills</label>
                                <textarea id="skills" class="form-control" name="skills" rows="2" placeholder="Comma separated"><?= h((string) ($data['skills'] ?? '')) ?></textarea>
                            </div>
                        </div>

                        <h3 class="h6 mt-4">Choose Template (1-10)</h3>
                        <div class="row g-2">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <label class="template-option card p-2">
                                        <input class="form-check-input me-2" type="radio" name="template" value="<?= $i ?>" <?= ((int) ($data['template'] ?? 1) === $i) ? 'checked' : '' ?>>
                                        <?= h(templateName((string) $i)) ?>
                                    </label>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div class="mt-4 d-flex flex-wrap gap-2">
                            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                            <a class="btn btn-outline-secondary" target="_blank" href="preview.php"><i class="fa-solid fa-eye"></i> Preview</a>
                            <a class="btn btn-outline-success" target="_blank" href="download.php?token=<?= urlencode($downloadToken) ?>"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6">How it works</h2>
                    <ol class="small mb-0">
                        <li>Fill your details.</li>
                        <li>Select one of 10 templates.</li>
                        <li>Preview your resume.</li>
                        <li>Download PDF from browser print dialog.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
