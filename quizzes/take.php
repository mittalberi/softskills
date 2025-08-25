<?php
require_once '../includes/db.php';
require_once '../includes/auth_guard.php';
require_once '../includes/csrf.php';
require_login();
$quiz_id = (int)($_GET['id'] ?? 0);
$q = $pdo->prepare('SELECT * FROM quizzes WHERE id=? AND is_published=1');
$q->execute([$quiz_id]);
$quiz = $q->fetch();
if (!$quiz) { http_response_code(404); exit('Quiz not found or unpublished.'); }
$now = date('Y-m-d H:i:s');
if (!empty($quiz['available_from']) && $now < $quiz['available_from']) { include '../includes/header.php'; echo '<div class="alert alert-warning">This quiz opens at '.htmlspecialchars($quiz['available_from']).'.</div>'; include '../includes/footer.php'; exit; }
if (!empty($quiz['available_to']) && $now > $quiz['available_to']) { include '../includes/header.php'; echo '<div class="alert alert-warning">This quiz closed at '.htmlspecialchars($quiz['available_to']).'.</div>'; include '../includes/footer.php'; exit; }
if (!empty($quiz['max_attempts'])) { $ct = $pdo->prepare('SELECT COUNT(*) c FROM quiz_attempts WHERE quiz_id=? AND user_id=? AND status="submitted"'); $ct->execute([$quiz_id, $_SESSION['user']['id']]); if ((int)$ct->fetch()['c'] >= (int)$quiz['max_attempts']) { include '../includes/header.php'; echo '<div class="alert alert-danger">You have reached the maximum attempts for this quiz.</div>'; include '../includes/footer.php'; exit; } }
$a = $pdo->prepare("SELECT * FROM quiz_attempts WHERE quiz_id=? AND user_id=? AND status='in_progress' ORDER BY id DESC LIMIT 1");
$a->execute([$quiz_id, $_SESSION['user']['id']]); $attempt = $a->fetch();
if (!$attempt) { $ins = $pdo->prepare('INSERT INTO quiz_attempts (quiz_id,user_id,total_marks) VALUES (?,?,0)'); $ins->execute([$quiz_id, $_SESSION['user']['id']]); $attempt_id = (int)$pdo->lastInsertId(); $a2 = $pdo->prepare("SELECT * FROM quiz_attempts WHERE id=?"); $a2->execute([$attempt_id]); $attempt = $a2->fetch(); } else { $attempt_id = (int)$attempt['id']; }
$allowedSec = max(1, (int)$quiz['duration_minutes']) * 60; $startedTs  = strtotime($attempt['started_at']); $nowTs = time(); $elapsed = max(0, $nowTs - $startedTs); $remaining = $allowedSec - $elapsed;
if ($remaining <= 0) { $tm = $pdo->prepare('SELECT COALESCE(SUM(marks),0) t FROM quiz_questions WHERE quiz_id=?'); $tm->execute([$quiz_id]); $total_marks = (float)$tm->fetch()['t']; $upd = $pdo->prepare('UPDATE quiz_attempts SET submitted_at=NOW(), status="submitted", score=0, total_marks=? WHERE id=? AND status="in_progress"'); $upd->execute([$total_marks, $attempt_id]); header('Location: '.url('quizzes/result.php').'?attempt_id='.$attempt_id); exit; }
$orderSql = !empty($quiz['shuffle_questions']) ? 'ORDER BY RAND()' : 'ORDER BY qq.sort_order, qq.id';
$qq = $pdo->prepare("SELECT qq.*, q.* FROM quiz_questions qq JOIN questions q ON q.id=qq.question_id WHERE qq.quiz_id=? $orderSql"); $qq->execute([$quiz_id]); $questions = $qq->fetchAll();
include '../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <h4 class="mb-0"><?= htmlspecialchars($quiz['title']) ?></h4>
  <div class="badge text-bg-warning">Time left: <span id="timer" data-seconds="<?= (int)$remaining ?>"><?= (int)$remaining ?></span>s</div>
</div>
<form method="post" action="<?= url('quizzes/submit.php') ?>" id="quizForm">
  <?php csrf_field(); ?><input type="hidden" name="attempt_id" value="<?= (int)$attempt_id ?>">
  <?php foreach ($questions as $i => $q): ?>
    <div class="card my-3"><div class="card-body">
      <p class="fw-semibold mb-3"><?= ($i+1) ?>) <?= nl2br(htmlspecialchars($q['question_text'])) ?></p>
      <?php if ($q['question_type'] === 'true_false'): ?>
        <div class="form-check"><input class="form-check-input" type="radio" name="q<?= $q['question_id'] ?>" value="True" id="q<?= $q['question_id'] ?>t"><label class="form-check-label" for="q<?= $q['question_id'] ?>t">True</label></div>
        <div class="form-check"><input class="form-check-input" type="radio" name="q<?= $q['question_id'] ?>" value="False" id="q<?= $q['question_id'] ?>f"><label class="form-check-label" for="q<?= $q['question_id'] ?>f">False</label></div>
      <?php elseif ($q['question_type'] === 'short'): ?>
        <input class="form-control" name="q<?= $q['question_id'] ?>" placeholder="Type your answer">
      <?php else: ?>
        <?php $opts=[]; foreach(['A','B','C','D'] as $opt){ $txt=$q['option_'.strtolower($opt)]; if(!empty($txt)) $opts[]=['key'=>$opt,'text'=>$txt]; } if(!empty($quiz['shuffle_options'])){ shuffle($opts); } ?>
        <?php foreach($opts as $o): ?>
          <div class="form-check">
            <input class="form-check-input" type="<?= $q['question_type']==='mcq_multi'?'checkbox':'radio' ?>" name="q<?= $q['question_id'] ?><?= $q['question_type']==='mcq_multi'?'[]':'' ?>" value="<?= $o['key'] ?>" id="q<?= $q['question_id'].$o['key'] ?>">
            <label class="form-check-label" for="q<?= $q['question_id'].$o['key'] ?>"><?= $o['key'] ?>) <?= htmlspecialchars($o['text']) ?></label>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div></div>
  <?php endforeach; ?>
  <div class="d-flex justify-content-between align-items-center">
    <small class="text-muted">Your answers are saved on submit.</small>
    <button class="btn btn-primary">Submit</button>
  </div>
</form>
<script>(function(){var el=document.getElementById('timer');var t=parseInt(el.getAttribute('data-seconds'),10)||0;function tick(){t--;if(t<=0){document.getElementById('quizForm').submit();return;}el.textContent=t;setTimeout(tick,1000);}setTimeout(tick,1000);})();</script>
<?php include '../includes/footer.php'; ?>
