<?php
require_once '../includes/db.php';
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT l.*, m.id AS module_id, m.title AS module_title, m.course_id, c.title AS course_title
                     FROM lessons l JOIN modules m ON m.id=l.module_id JOIN courses c ON c.id=m.course_id WHERE l.id=?");
$st->execute([$id]);
$l = $st->fetch();
if (!$l) { http_response_code(404); exit('Lesson not found'); }

$ls = $pdo->prepare('SELECT id, title, sort_order FROM lessons WHERE module_id=? ORDER BY sort_order, id');
$ls->execute([$l['module_id']]);
$outline = $ls->fetchAll();

$idx = 0; $prevId = null; $nextId = null;
foreach ($outline as $i=>$row){ if ((int)$row['id']===(int)$l['id']) { $idx=$i; $prevId=$outline[$i-1]['id']??null; $nextId=$outline[$i+1]['id']??null; break; } }

function embed_youtube_auto($html){
  $pattern='~(?:https?://)?(?:www\.)?(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_\-]{11})~i';
  return preg_replace_callback($pattern,function($m){
    $id=$m[1]; $src="https://www.youtube-nocookie.com/embed/$id";
    return '<div class="ratio ratio-16x9 my-3"><iframe src="'.$src.'" title="YouTube video" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>';
  },$html);
}
include '../includes/header.php';
?>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= url('courses/') ?>">Courses</a></li>
    <li class="breadcrumb-item"><a href="<?= url('courses/view.php') ?>?id=<?= (int)$l['course_id'] ?>"><?= htmlspecialchars($l['course_title']) ?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($l['module_title']) ?></li>
  </ol>
</nav>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card"><div class="card-body">
      <div class="d-flex justify-content-between align-items-start">
        <h4 class="mb-0"><?= htmlspecialchars($l['title']) ?></h4>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">Print</button>
      </div>
      <div class="mt-3"><?= embed_youtube_auto($l['content']) ?></div>
      <div class="d-flex justify-content-between align-items-center mt-4">
        <div><?php if ($prevId): ?><a class="btn btn-outline-primary" href="<?= url('lessons/view.php') ?>?id=<?= (int)$prevId ?>">← Previous</a><?php endif; ?></div>
        <small class="text-muted">Lesson <?= $idx+1 ?> of <?= count($outline) ?></small>
        <div><?php if ($nextId): ?><a class="btn btn-primary" href="<?= url('lessons/view.php') ?>?id=<?= (int)$nextId ?>">Next →</a><?php endif; ?></div>
      </div>
    </div></div>
  </div>
  <div class="col-lg-4">
    <div class="card"><div class="card-body">
      <h6 class="card-title mb-2">Module Outline</h6>
      <ul class="list-group list-group-flush">
        <?php foreach ($outline as $r): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center <?= (int)$r['id']===(int)$l['id'] ? 'active' : '' ?>">
            <a class="<?= (int)$r['id']===(int)$l['id'] ? 'link-light text-decoration-none' : '' ?>" href="<?= url('lessons/view.php') ?>?id=<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['title']) ?></a>
            <span class="badge text-bg-light">#<?= (int)$r['sort_order'] ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="mt-3"><a class="btn btn-outline-secondary w-100" href="<?= url('courses/view.php') ?>?id=<?= (int)$l['course_id'] ?>">Back to Course</a></div>
    </div></div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
