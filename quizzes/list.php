<?php
require_once '../includes/db.php';
include '../includes/header.php';
$rows = $pdo->query('SELECT q.*, c.title AS course_title FROM quizzes q LEFT JOIN courses c ON c.id=q.course_id WHERE q.is_published=1 ORDER BY q.created_at DESC')->fetchAll();
?>
<h3>All Quizzes</h3>
<div class="list-group">
  <?php foreach ($rows as $r): ?>
    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?= url('quizzes/take.php') ?>?id=<?= (int)$r['id'] ?>">
      <span>
        <strong><?= htmlspecialchars($r['title']) ?></strong>
        <small class="text-secondary ms-2"><?= htmlspecialchars($r['course_title'] ?? 'General') ?></small>
      </span>
      <span class="badge text-bg-warning"><?= (int)$r['duration_minutes'] ?> min</span>
    </a>
  <?php endforeach; if (!$rows): ?>
    <div class="alert alert-info mt-3">No quizzes available yet.</div>
  <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
