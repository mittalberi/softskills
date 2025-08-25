<?php
require_once '../includes/db.php';
$id = (int)($_GET['id'] ?? 0);
$course = $pdo->prepare('SELECT * FROM courses WHERE id=? AND is_published=1');
$course->execute([$id]);
$course = $course->fetch();
if (!$course) { http_response_code(404); exit('Course not found'); }

$modules = $pdo->prepare('SELECT * FROM modules WHERE course_id=? ORDER BY sort_order, id');
$modules->execute([$id]);
$modules = $modules->fetchAll();

include '../includes/header.php';
?>
<h3><?= htmlspecialchars($course['title']) ?></h3>
<p class="text-secondary"><?= nl2br(htmlspecialchars($course['description'] ?? '')) ?></p>

<?php foreach ($modules as $m): ?>
  <div class="card my-3">
    <div class="card-body">
      <h5><?= htmlspecialchars($m['title']) ?></h5>
      <?php
        $ls = $pdo->prepare('SELECT * FROM lessons WHERE module_id=? ORDER BY sort_order, id');
        $ls->execute([$m['id']]);
        $lessons = $ls->fetchAll();
      ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($lessons as $l): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <a href="<?= url('lessons/view.php') ?>?id=<?= (int)$l['id'] ?>"><?= htmlspecialchars($l['title']) ?></a>
            <a class="btn btn-sm btn-outline-primary" href="<?= url('lessons/view.php') ?>?id=<?= (int)$l['id'] ?>">Open</a>
          </li>
        <?php endforeach; ?>
        <?php if (!$lessons): ?><li class="list-group-item text-secondary">No lessons yet.</li><?php endif; ?>
      </ul>
    </div>
  </div>
<?php endforeach; ?>

<h5 class="mt-4">Quizzes</h5>
<ul class="list-group">
<?php
  $qs = $pdo->prepare('SELECT * FROM quizzes WHERE course_id=? AND is_published=1 ORDER BY created_at DESC');
  $qs->execute([$id]);
  $qs = $qs->fetchAll();
  foreach ($qs as $q):
?>
  <li class="list-group-item d-flex justify-content-between align-items-center">
    <span><?= htmlspecialchars($q['title']) ?> <span class="badge text-bg-warning ms-2"><?= (int)$q['duration_minutes'] ?> min</span></span>
    <a class="btn btn-sm btn-primary" href="<?= url('quizzes/take.php') ?>?id=<?= (int)$q['id'] ?>">Start</a>
  </li>
<?php endforeach; if (!$qs): ?>
  <li class="list-group-item text-secondary">No quizzes yet.</li>
<?php endif; ?>
</ul>
<?php include '../includes/footer.php'; ?>
