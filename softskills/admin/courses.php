<?php
require_once '../includes/db.php';
require_once '../includes/auth_guard.php';
require_once '../includes/csrf.php';
require_role('instructor');

// delete course (with CSRF)
if (isset($_GET['del'], $_GET['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_GET['csrf'])) {
  $id = (int)$_GET['del'];
  $pdo->prepare('DELETE FROM courses WHERE id=?')->execute([$id]);
  header('Location: '.url('admin/courses.php')); exit;
}

$courses = $pdo->query('SELECT * FROM courses ORDER BY created_at DESC')->fetchAll();

include '../includes/header.php';
include __DIR__.'/_nav.php';
?>
<div class="d-flex justify-content-between align-items-center">
  <h4 class="mb-0">Courses</h4>
  <a class="btn btn-primary btn-sm" href="<?= url('admin/course_edit.php') ?>">New Course</a>
</div>

<div class="table-responsive mt-3">
  <table class="table align-middle">
    <thead><tr><th>ID</th><th>Title</th><th>Published</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($courses as $c): ?>
        <tr>
          <td><?= (int)$c['id'] ?></td>
          <td><?= htmlspecialchars($c['title']) ?></td>
          <td><span class="badge <?= $c['is_published']?'text-bg-success':'text-bg-warning' ?>"><?= $c['is_published']?'Yes':'No' ?></span></td>
          <td class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/course_edit.php') ?>?id=<?= (int)$c['id'] ?>">Edit</a>
            <a class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete course? This removes modules/lessons.')" href="<?= url('admin/courses.php') ?>?del=<?= (int)$c['id'] ?>&csrf=<?= htmlspecialchars(csrf_token()) ?>">Delete</a>
          </td>
        </tr>
      <?php endforeach; if (!$courses): ?>
        <tr><td colspan="4" class="text-secondary">No courses yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include '../includes/footer.php'; ?>