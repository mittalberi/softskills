<?php
require_once '../includes/db.php';
include '../includes/header.php';

$c = $pdo->query('SELECT * FROM courses WHERE is_published=1 ORDER BY created_at DESC')->fetchAll();
?>
<h3 class="mb-3">Courses</h3>
<div class="row g-3">
  <?php foreach ($c as $row): ?>
    <div class="col-md-4">
      <div class="card card-hover h-100">
        <div class="card-body d-flex flex-column">
          <h5 class="card-title"><?= htmlspecialchars($row['title']) ?></h5>
          <p class="text-secondary flex-grow-1"><?= htmlspecialchars($row['short_desc'] ?? '') ?></p>
          <a href="<?= url('courses/view.php') ?>?id=<?= (int)$row['id'] ?>" class="stretched-link">Open →</a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$c): ?>
    <div class="col-12"><div class="alert alert-info">No courses yet. Admins can add from dashboard.</div></div>
  <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
