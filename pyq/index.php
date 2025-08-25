<?php
require_once '../includes/db.php';
include '../includes/header.php';
$c = $_GET['company'] ?? '';
$valid = ['infosys','tcs','wipro'];
$filter = in_array($c,$valid) ? $c : null;

$sql = 'SELECT DISTINCT topic FROM questions';
$params = [];
if ($filter) { $sql .= ' WHERE company_tag=?'; $params[]=$filter; }
$sql .= ' ORDER BY topic';
$st = $pdo->prepare($sql);
$st->execute($params);
$topics = $st->fetchAll(PDO::FETCH_COLUMN);
?>
<h3>PYQ Bank <?= $filter?('· '.strtoupper($filter)) : '' ?></h3>
<p class="text-secondary">Select a topic to practice. These mirror Infosys/TCS/Wipro styles.</p>
<div class="d-flex gap-2 mb-3">
  <a class="btn btn-outline-secondary btn-sm" href="<?= url('pyq/') ?>">All</a>
  <a class="btn btn-outline-secondary btn-sm" href="/pyq/?company=infosys">Infosys</a>
  <a class="btn btn-outline-secondary btn-sm" href="/pyq/?company=tcs">TCS</a>
  <a class="btn btn-outline-secondary btn-sm" href="/pyq/?company=wipro">Wipro</a>
</div>
<ul class="list-group">
  <?php foreach ($topics as $t): ?>
    <li class="list-group-item d-flex justify-content-between align-items-center">
      <span><?= htmlspecialchars($t ?: 'General') ?></span>
      <a class="btn btn-sm btn-primary" href="<?= url('quizzes/list.php') ?>">Practice</a>
    </li>
  <?php endforeach; if (!$topics): ?>
    <li class="list-group-item text-secondary">No PYQ topics found yet.</li>
  <?php endif; ?>
</ul>
<?php include '../includes/footer.php'; ?>
