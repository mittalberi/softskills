<?php
// /mocks/index.php (final)
require_once '../includes/db.php';           // ✅ make $pdo available
// If you want to restrict to logged-in users, uncomment the next two lines:
// require_once '../includes/auth_guard.php';
// require_login();

$rows = $pdo->query("
  SELECT * FROM quizzes
  WHERE is_mock=1 AND is_published=1
  ORDER BY COALESCE(available_from, created_at) DESC
")->fetchAll();

include '../includes/header.php';
?>
<h3>Mock Tests</h3>
<p class="text-secondary">Full-length, timed tests simulating campus hiring rounds.</p>

<div class="list-group">
<?php foreach ($rows as $r):
  $now = new DateTime();
  $from = $r['available_from'] ? new DateTime($r['available_from']) : null;
  $to   = $r['available_to']   ? new DateTime($r['available_to'])   : null;
  $status = 'Open';
  if ($from && $now < $from) $status = 'Opens '. $from->format('M d, H:i');
  if ($to && $now > $to)     $status = 'Closed';
?>
  <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
     href="<?= ($status==='Closed' || strpos($status,'Opens')===0) ? 'javascript:void(0)' : url('quizzes/take.php').'?id='.(int)$r['id'] ?>"
     onclick="<?= ($status==='Closed' || strpos($status,'Opens')===0) ? 'return false' : '' ?>">
    <span>
      <strong><?= htmlspecialchars($r['title']) ?></strong>
      <?php if ($from): ?><span class="badge text-bg-light ms-2">From <?= $from->format('M d, H:i') ?></span><?php endif; ?>
      <?php if ($to): ?><span class="badge text-bg-light ms-1">To <?= $to->format('M d, H:i') ?></span><?php endif; ?>
    </span>
    <span class="d-flex align-items-center gap-2">
      <span class="badge <?= $status==='Open'?'text-bg-success':(strpos($status,'Opens')===0?'text-bg-warning':'text-bg-secondary') ?>"><?= $status ?></span>
      <span class="badge text-bg-warning"><?= (int)$r['duration_minutes'] ?> min</span>
    </span>
  </a>
<?php endforeach; if (!$rows): ?>
  <div class="alert alert-info mt-3">No mock tests yet.</div>
<?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
