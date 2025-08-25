<?php
require_once '../includes/db.php';
require_once '../includes/auth_guard.php';
require_once '../includes/csrf.php';
require_role('instructor');

// Filters
$company = strtolower(trim($_GET['company'] ?? ''));
$type    = strtolower(trim($_GET['type'] ?? ''));
$diff    = strtolower(trim($_GET['difficulty'] ?? ''));
$topic   = trim($_GET['topic'] ?? '');

$where = []; $params = [];
if (in_array($company, ['infosys','tcs','wipro','generic'], true)) { $where[]='company_tag=?'; $params[]=$company; }
if (in_array($type, ['mcq_single','mcq_multi','true_false','short'], true)) { $where[]='question_type=?'; $params[]=$type; }
if (in_array($diff, ['easy','medium','hard'], true)) { $where[]='difficulty=?'; $params[]=$diff; }
if ($topic!=='') { $where[]='topic LIKE ?'; $params[]='%'.$topic.'%'; }
$sql = 'SELECT id, company_tag, topic, question_type, difficulty FROM questions';
if ($where) $sql .= ' WHERE '.implode(' AND ',$where);
$sql .= ' ORDER BY id DESC LIMIT 200';
$rows = $pdo->prepare($sql);
$rows->execute($params);
$rows = $rows->fetchAll();

// Delete
if (isset($_GET['del'], $_GET['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_GET['csrf'])) {
  $pdo->prepare('DELETE FROM questions WHERE id=?')->execute([(int)$_GET['del']]);
  header('Location: '.url('admin/questions.php')); exit;
}

include '../includes/header.php';
include __DIR__.'/_nav.php';
?>
<div class="d-flex justify-content-between align-items-center">
  <h4 class="mb-0">Question Bank</h4>
  <a class="btn btn-primary btn-sm" href="<?= url('admin/question_edit.php') ?>">Add Question</a>
</div>

<form class="row g-2 mt-2">
  <div class="col-md-2"><input class="form-control" name="topic" value="<?= htmlspecialchars($topic) ?>" placeholder="Topic"></div>
  <div class="col-md-2">
    <select class="form-select" name="company">
      <option value="">Company</option>
      <?php foreach (['infosys','tcs','wipro','generic'] as $c): ?>
        <option value="<?= $c ?>" <?= $company===$c?'selected':'' ?>><?= strtoupper($c) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <select class="form-select" name="type">
      <option value="">Type</option>
      <?php foreach (['mcq_single','mcq_multi','true_false','short'] as $t): ?>
        <option value="<?= $t ?>" <?= $type===$t?'selected':'' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <select class="form-select" name="difficulty">
      <option value="">Difficulty</option>
      <?php foreach (['easy','medium','hard'] as $d): ?>
        <option value="<?= $d ?>" <?= $diff===$d?'selected':'' ?>><?= ucfirst($d) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
  <div class="col-md-2"><a class="btn btn-outline-secondary w-100" href="<?= url('admin/questions.php') ?>">Clear</a></div>
</form>

<div class="table-responsive mt-3">
  <table class="table table-sm align-middle">
    <thead><tr><th>ID</th><th>Company</th><th>Topic</th><th>Type</th><th>Diff</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><span class="badge text-bg-secondary"><?= strtoupper($r['company_tag']) ?></span></td>
        <td><?= htmlspecialchars($r['topic'] ?: 'General') ?></td>
        <td><?= htmlspecialchars($r['question_type']) ?></td>
        <td><?= htmlspecialchars($r['difficulty']) ?></td>
        <td class="d-flex gap-2">
          <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/question_edit.php') ?>?id=<?= (int)$r['id'] ?>">Edit</a>
          <a class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete question?')" href="<?= url('admin/questions.php') ?>?del=<?= (int)$r['id'] ?>&csrf=<?= htmlspecialchars(csrf_token()) ?>">Delete</a>
        </td>
      </tr>
      <?php endforeach; if (!$rows): ?>
      <tr><td colspan="6" class="text-secondary">No questions found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include '../includes/footer.php'; ?>
