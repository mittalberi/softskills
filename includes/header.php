<?php require_once __DIR__.'/config.php'; ?>
<?php
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer-when-downgrade');
$csp = [
  "default-src 'self'",
  "img-src 'self' https: data:",
  "script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'",
  "style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'",
  "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com",
  "connect-src 'self'",
];
header('Content-Security-Policy: '.implode('; ', $csp));
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($portal_name) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= url('assets/css/custom.css') ?>" rel="stylesheet">
</head><body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand" href="<?= url('') ?>"><?= htmlspecialchars($portal_name) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= url('courses/') ?>">Courses</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('pyq/') ?>">PYQ Bank</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('mocks/') ?>">Mocks</a></li>
      </ul>
      <div class="d-flex align-items-center gap-2">
        <?php if (!empty($_SESSION['user'])): ?>
          <a class="btn btn-outline-primary" href="<?= url('admin/') ?>">Admin</a>
          <a class="btn btn-primary" href="<?= url('auth/logout.php') ?>">Logout</a>
        <?php else: ?>
          <a class="btn btn-primary" href="<?= url('auth/login.php') ?>">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<main class="container py-4">
