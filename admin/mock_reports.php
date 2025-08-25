<?php
require_once '../includes/db.php';
require_once '../includes/auth_guard.php';
require_role('instructor');

$quiz_id = (int)($_GET['quiz_id'] ?? 0);
$quiz = null;
if ($quiz_id) {
  $st = $pdo->prepare('SELECT * FROM quizzes WHERE id=?');
  $st->execute([$quiz_id]);
  $quiz = $st->fetch();
}
if (!$quiz) { http_response_code(404); exit('Quiz not found'); }

// Export CSV
if (isset($_GET['export'])) {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="mock_results_'.$quiz_id.'.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['attempt_id','user_id','score','total_marks','percent','submitted_at']);
  $rs = $pdo->prepare('SELECT id, user_id, score, total_marks, submitted_at FROM quiz_attempts WHERE quiz_id=? AND status="submitted" ORDER BY submitted_at DESC');
  $rs->execute([$quiz_id]);
  while ($row = $rs->fetch()) {
    $pct = $row['total_marks'] > 0 ? round(($row['score']/$row['total_marks'])*100,2) : 0;
    fputcsv($out, [$row['id'],$row['user_id'],$row['score'],$row['total_marks'],$pct,$row['submitted_at']]);
  }
  fclose($out); exit;
}

$rows = $pdo->prepare('SELECT id, user_id, score, total_marks, submitted_at FROM quiz_attempts WHERE quiz_id=? AND status="submitted" ORDER BY submitted_at DESC');
$rows->execute([$quiz_id]);
$rows = $rows->fetchAll();

include '../includes/header.php';
include __DIR__.'/_nav.php';
?>
<div class="d-flex justify-content-between align-items-center">
  <h4 class="mb-0">Mock Reports · <?= htmlspecialchars($quiz['title']) ?></h4>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?= url('admin/quizzes.php') ?>">Back</a>
    <a class="btn btn-outline-primary btn-sm" href="<?= url('admin/mock_reports.php') ?>?quiz_id=<?= $quiz_id ?>&export=1">Export CSV</a>
  </div>
</div>

<div class="table-responsive mt-3">
  <table class="table table-sm align-middle">
    <thead><tr><th>Attempt</th><th>User</th><th>Score</th><th>Total</th><th>%</th><th>Submitted</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): $pct = $r['total_marks']>0 ? round(($r['score']/$r['total_marks'])*100,2) : 0; ?>
      <tr>
        <td>#<?= (int)$r['id'] ?></td>
        <td><?= (int)$r['user_id'] ?></td>
        <td><?= (float)$r['score'] ?></td>
        <td><?= (float)$r['total_marks'] ?></td>
        <td><?= $pct ?>%</td>
        <td><?= htmlspecialchars($r['submitted_at']) ?></td>
      </tr>
      <?php endforeach; if (!$rows): ?>
      <tr><td colspan="6" class="text-secondary">No submissions yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include '../includes/footer.php'; ?>