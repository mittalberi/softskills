<?php
require_once '../includes/db.php';
require_once '../includes/auth_guard.php';
require_login();
include '../includes/header.php';

$uid = $_SESSION['user']['id'];
$attempts = $pdo->prepare('SELECT qa.*, q.title FROM quiz_attempts qa JOIN quizzes q ON q.id=qa.quiz_id WHERE qa.user_id=? AND qa.status="submitted" ORDER BY qa.submitted_at DESC');
$attempts->execute([$uid]);
$rows = $attempts->fetchAll();
?>
<h3>My Profile</h3>
<p>Email: <strong><?= htmlspecialchars($_SESSION['user']['email']) ?></strong></p>
<h5 class="mt-4">Recent Results</h5>
<div class="list-group">
  <?php foreach ($rows as $r): ?>
    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?= url('quizzes/result.php') ?>?attempt_id=<?= (int)$r['id'] ?>">
      <span><?= htmlspecialchars($r['title']) ?></span>
      <span class="badge text-bg-secondary"><?= $r['score'] ?>/<?= $r['total_marks'] ?></span>
    </a>
  <?php endforeach; if (!$rows): ?>
    <div class="alert alert-info mt-2">No results yet. Take a quiz to see results.</div>
  <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
