<?php
require_once '../includes/db.php';
require_once '../includes/auth_guard.php';
require_once '../includes/csrf.php';
require_role('instructor');

// publish/unpublish/delete via GET + CSRF
if (isset($_GET['pub'], $_GET['id'], $_GET['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_GET['csrf'])) {
  $pdo->prepare('UPDATE quizzes SET is_published=? WHERE id=?')->execute([(int)$_GET['pub'], (int)$_GET['id']]);
  header('Location: '.url('admin/quizzes.php')); exit;
}
if (isset($_GET['del'], $_GET['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_GET['csrf'])) {
  $pdo->prepare('DELETE FROM quizzes WHERE id=?')->execute([(int)$_GET['del']]);
  header('Location: '.url('admin/quizzes.php')); exit;
}

$rows = $pdo->query('SELECT q.*, c.title AS course_title FROM quizzes q LEFT JOIN courses c ON c.id=q.course_id ORDER BY q.created_at DESC')->fetchAll();

include '../includes/header.php';
include __DIR__.'/_nav.php';
?>
<div class="d-flex justify-content-between align-items-center">
  <h4 class="mb-0">Quizzes</h4>
  <a class="btn btn-primary btn-sm" href="<?= url('admin/quiz_edit.php') ?>">New Quiz</a>
</div>

<div class="table-responsive mt-3">
  <table class="table align-middle">
    <thead><tr><th>ID</th><th>Title</th><th>Course</th><th>Duration</th><th>Mock?</th><th>Published</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars($r['title']) ?></td>
        <td><?= htmlspecialchars($r['course_title'] ?? 'General') ?></td>
        <td><?= (int)$r['duration_minutes'] ?> min</td>
        <td><span class="badge <?= $r['is_mock']?'text-bg-secondary':'text-bg-light' ?>"><?= $r['is_mock']?'Yes':'No' ?></span></td>
        <td><span class="badge <?= $r['is_published']?'text-bg-success':'text-bg-warning' ?>"><?= $r['is_published']?'Yes':'No' ?></span></td>
        <td class="d-flex gap-2">
          <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/quiz_edit.php') ?>?id=<?= (int)$r['id'] ?>">Edit</a>
          <?php if ($r['is_published']): ?>
            <a class="btn btn-sm btn-outline-warning" href="<?= url('admin/quizzes.php') ?>?pub=0&id=<?= (int)$r['id'] ?>&csrf=<?= htmlspecialchars(csrf_token()) ?>">Unpublish</a>
          <?php else: ?>
            <a class="btn btn-sm btn-outline-success" href="<?= url('admin/quizzes.php') ?>?pub=1&id=<?= (int)$r['id'] ?>&csrf=<?= htmlspecialchars(csrf_token()) ?>">Publish</a>
          <?php endif; ?>
          <a class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete quiz?')" href="<?= url('admin/quizzes.php') ?>?del=<?= (int)$r['id'] ?>&csrf=<?= htmlspecialchars(csrf_token()) ?>">Delete</a>
        </td>
      </tr>
      <?php endforeach; if (!$rows): ?>
        <tr><td colspan="7" class="text-secondary">No quizzes yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include '../includes/footer.php'; ?>