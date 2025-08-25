<?php
require_once '../includes/db.php';
require_once '../includes/auth_guard.php';
require_role('instructor');
include '../includes/header.php';
include __DIR__.'/_nav.php';

// quick metrics
$metrics = [
  'courses'  => $pdo->query('SELECT COUNT(*) c FROM courses')->fetch()['c'] ?? 0,
  'lessons'  => $pdo->query('SELECT COUNT(*) c FROM lessons')->fetch()['c'] ?? 0,
  'questions'=> $pdo->query('SELECT COUNT(*) c FROM questions')->fetch()['c'] ?? 0,
  'quizzes'  => $pdo->query('SELECT COUNT(*) c FROM quizzes')->fetch()['c'] ?? 0,
  'attempts' => $pdo->query('SELECT COUNT(*) c FROM quiz_attempts')->fetch()['c'] ?? 0,
];

// latest activity
$recentQuestions = $pdo->query("SELECT id, topic, question_type, difficulty, company_tag FROM questions ORDER BY id DESC LIMIT 6")->fetchAll();
$recentQuizzes = $pdo->query("SELECT id, title, is_published, created_at FROM quizzes ORDER BY id DESC LIMIT 6")->fetchAll();
$recentAttempts = $pdo->query("SELECT qa.id, q.title, qa.user_id, qa.score, qa.total_marks, qa.submitted_at FROM quiz_attempts qa JOIN quizzes q ON q.id=qa.quiz_id WHERE qa.status='submitted' ORDER BY qa.submitted_at DESC LIMIT 6")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Instructor Dashboard</h3>
  <div class="d-flex gap-2">
    <a class="btn btn-primary btn-sm" href="<?= url('admin/questions.php') ?>">Question Bank</a>
    <a class="btn btn-outline-primary btn-sm" href="<?= url('admin/upload_questions_csv.php') ?>">CSV Import</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-2"><div class="card card-hover h-100"><div class="card-body"><div class="text-muted small">Courses</div><div class="display-6"><?= (int)$metrics['courses'] ?></div></div></div></div>
  <div class="col-md-2"><div class="card card-hover h-100"><div class="card-body"><div class="text-muted small">Lessons</div><div class="display-6"><?= (int)$metrics['lessons'] ?></div></div></div></div>
  <div class="col-md-2"><div class="card card-hover h-100"><div class="card-body"><div class="text-muted small">Questions</div><div class="display-6"><?= (int)$metrics['questions'] ?></div></div></div></div>
  <div class="col-md-2"><div class="card card-hover h-100"><div class="card-body"><div class="text-muted small">Quizzes</div><div class="display-6"><?= (int)$metrics['quizzes'] ?></div></div></div></div>
  <div class="col-md-2"><div class="card card-hover h-100"><div class="card-body"><div class="text-muted small">Attempts</div><div class="display-6"><?= (int)$metrics['attempts'] ?></div></div></div></div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title mb-2">Recent Questions</h5>
        <ul class="list-group list-group-flush small">
          <?php foreach ($recentQuestions as $r): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span>#<?= (int)$r['id'] ?> · <?= htmlspecialchars($r['topic'] ?: 'General') ?> <span class="badge text-bg-secondary ms-1"><?= htmlspecialchars($r['question_type']) ?></span></span>
              <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/question_edit.php') ?>?id=<?= (int)$r['id'] ?>">Edit</a>
            </li>
          <?php endforeach; if (!$recentQuestions): ?>
            <li class="list-group-item text-secondary">No questions yet.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title mb-2">Recent Quizzes</h5>
        <ul class="list-group list-group-flush small">
          <?php foreach ($recentQuizzes as $r): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span><?= htmlspecialchars($r['title']) ?></span>
              <div class="d-flex align-items-center gap-2">
                <span class="badge <?= $r['is_published']?'text-bg-success':'text-bg-warning' ?>"><?= $r['is_published']?'Published':'Draft' ?></span>
                <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/quiz_edit.php') ?>?id=<?= (int)$r['id'] ?>">Open</a>
              </div>
            </li>
          <?php endforeach; if (!$recentQuizzes): ?>
            <li class="list-group-item text-secondary">No quizzes yet.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title mb-2">Latest Attempts</h5>
        <ul class="list-group list-group-flush small">
          <?php foreach ($recentAttempts as $r): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span><?= htmlspecialchars($r['title']) ?></span>
              <span class="badge text-bg-secondary"><?= (float)$r['score'] ?>/<?= (float)$r['total_marks'] ?></span>
            </li>
          <?php endforeach; if (!$recentAttempts): ?>
            <li class="list-group-item text-secondary">No attempts yet.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
