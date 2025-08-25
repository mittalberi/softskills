<?php
require_once '../includes/db.php';
require_once '../includes/auth_guard.php';
require_login();
$attempt_id = (int)($_GET['attempt_id'] ?? 0);
$a = $pdo->prepare('SELECT qa.*, q.title FROM quiz_attempts qa JOIN quizzes q ON q.id=qa.quiz_id WHERE qa.id=? AND qa.user_id=?');
$a->execute([$attempt_id, $_SESSION['user']['id']]);
$attempt = $a->fetch();
if (!$attempt) { http_response_code(404); exit('Result not found'); }

$ans = $pdo->prepare('SELECT a.*, q.question_text, q.correct_answer FROM quiz_attempt_answers a JOIN questions q ON q.id=a.question_id WHERE a.attempt_id=?');
$ans->execute([$attempt_id]);
$answers = $ans->fetchAll();

include '../includes/header.php';
?>
<h3>Result: <?= htmlspecialchars($attempt['title']) ?></h3>
<div class="alert alert-success">Score: <strong><?= $attempt['score'] ?></strong> / <?= $attempt['total_marks'] ?></div>

<div class="accordion" id="review">
  <?php foreach ($answers as $i=>$r): ?>
    <div class="accordion-item">
      <h2 class="accordion-header" id="h<?= $r['id'] ?>">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c<?= $r['id'] ?>">
          Q<?= $i+1 ?>: <?= htmlspecialchars(mb_strimwidth(strip_tags($r['question_text']), 0, 90, '…')) ?>
        </button>
      </h2>
      <div id="c<?= $r['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#review">
        <div class="accordion-body">
          <p><?= nl2br(htmlspecialchars($r['question_text'])) ?></p>
          <p class="mb-1"><strong>Your Answer:</strong> <?= htmlspecialchars($r['chosen_answer'] ?: '—') ?></p>
          <p class="mb-1"><strong>Correct:</strong> <?= htmlspecialchars($r['correct_answer']) ?></p>
          <span class="badge <?= $r['is_correct']?'text-bg-success':'text-bg-danger' ?>"><?= $r['is_correct']?'Correct':'Incorrect' ?></span>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php include '../includes/footer.php'; ?>
