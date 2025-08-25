<?php
require_once '../includes/db.php';
require_once '../includes/csrf.php';
require_once '../includes/auth_guard.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
csrf_verify();
$attempt_id = (int)($_POST['attempt_id'] ?? 0);
$st = $pdo->prepare('SELECT qa.*, q.duration_minutes FROM quiz_attempts qa JOIN quizzes q ON q.id=qa.quiz_id WHERE qa.id=? AND qa.user_id=? LIMIT 1');
$st->execute([$attempt_id, $_SESSION['user']['id']]); $attempt = $st->fetch();
if (!$attempt) { http_response_code(404); exit('Attempt not found.'); }
if ($attempt['status'] !== 'in_progress') { header('Location: ' . url('quizzes/result.php') . '?attempt_id=' . $attempt_id); exit; }
$allowedSec = max(1, (int)$attempt['duration_minutes']) * 60; $startedTs=strtotime($attempt['started_at']); $nowTs=time(); $elapsed=max(0,$nowTs-$startedTs); $graceSec=3; $time_up=($elapsed > $allowedSec + $graceSec);
$qq = $pdo->prepare('SELECT qq.question_id, qq.marks, qq.neg_marks, q.question_type, q.correct_answer FROM quiz_questions qq JOIN questions q ON q.id=qq.question_id WHERE qq.quiz_id=?');
$qq->execute([$attempt['quiz_id']]); $qrows=$qq->fetchAll();
$total=0.0; $score=0.0; $pdo->beginTransaction();
try{
  $pdo->prepare('DELETE FROM quiz_attempt_answers WHERE attempt_id=?')->execute([$attempt_id]);
  $ins=$pdo->prepare('INSERT INTO quiz_attempt_answers (attempt_id, question_id, chosen_answer, is_correct, marks_awarded) VALUES (?,?,?,?,?)');
  foreach($qrows as $row){
    $qid=(int)$row['question_id']; $marks=(float)$row['marks']; $neg=(float)$row['neg_marks']; $total+=$marks;
    $chosen_str='';
    if($row['question_type']==='mcq_multi'){ $chosen=$_POST["q$qid"]??[]; if(!is_array($chosen)) $chosen=[]; $chosen=array_map('strval',$chosen); sort($chosen); $chosen_str=implode('|',$chosen); }
    else { $chosen_str=trim((string)($_POST["q$qid"]??'')); }
    $correct=trim((string)$row['correct_answer']); $is_correct=0;
    if($row['question_type']==='short'){ $is_correct=(strcasecmp($chosen_str,$correct)===0)?1:0; } else { $is_correct=($chosen_str===$correct)?1:0; }
    $marks_awarded=$is_correct?$marks:(0-$neg); $score+=$marks_awarded;
    $ins->execute([$attempt_id,$qid,$chosen_str,$is_correct,$marks_awarded]);
  }
  // if($time_up){ $score=0.0; } // strict late policy (optional)
  $pdo->prepare('UPDATE quiz_attempts SET submitted_at=NOW(), status="submitted", score=?, total_marks=? WHERE id=? AND status="in_progress"')->execute([$score,$total,$attempt_id]);
  $pdo->commit();
}catch(Throwable $e){ $pdo->rollBack(); http_response_code(500); exit('Submission failed.'); }
header('Location: ' . url('quizzes/result.php') . '?attempt_id=' . $attempt_id);
