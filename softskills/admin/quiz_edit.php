<?php
// /admin/quiz_edit.php (final)
require_once '../includes/db.php';
require_once '../includes/auth_guard.php';
require_once '../includes/csrf.php';
require_role('instructor');

$id  = (int)($_GET['id'] ?? 0);
$msg = null;

/* -------------------------
   CREATE / UPDATE QUIZ
------------------------- */
if (isset($_POST['save_quiz'])) {
  csrf_verify();

  $title     = trim($_POST['title'] ?? '');
  $course_id = (int)($_POST['course_id'] ?? 0) ?: null;
  $duration  = max(1, (int)($_POST['duration_minutes'] ?? 30));
  $is_mock   = isset($_POST['is_mock']) ? 1 : 0;
  $is_pub    = isset($_POST['is_published']) ? 1 : 0;

  // Optional fields
  $available_from = !empty($_POST['available_from']) ? date('Y-m-d H:i:s', strtotime($_POST['available_from'])) : null;
  $available_to   = !empty($_POST['available_to'])   ? date('Y-m-d H:i:s', strtotime($_POST['available_to']))   : null;
  $max_attempts   = max(1, (int)($_POST['max_attempts'] ?? 1));
  $pass_marks     = (float)($_POST['pass_marks'] ?? 0);
  $shq            = isset($_POST['shuffle_questions']) ? 1 : 0;
  $sho            = isset($_POST['shuffle_options'])   ? 1 : 0;

  if ($title === '') {
    $msg = 'Title is required.';
  } else {
    if ($id) {
      $pdo->prepare('UPDATE quizzes
        SET course_id=?, title=?, duration_minutes=?, is_mock=?, is_published=?,
            available_from=?, available_to=?, shuffle_questions=?, shuffle_options=?,
            max_attempts=?, pass_marks=?
        WHERE id=?')
      ->execute([$course_id,$title,$duration,$is_mock,$is_pub,
                 $available_from,$available_to,$shq,$sho,$max_attempts,$pass_marks,$id]);
      $msg = 'Quiz updated.';
    } else {
      $pdo->prepare('INSERT INTO quizzes
        (course_id, title, duration_minutes, is_mock, is_published,
         available_from, available_to, shuffle_questions, shuffle_options, max_attempts, pass_marks, created_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
      ->execute([$course_id,$title,$duration,$is_mock,$is_pub,
                 $available_from,$available_to,$shq,$sho,$max_attempts,$pass_marks,$_SESSION['user']['id']]);
      $id = (int)$pdo->lastInsertId();
      $msg = 'Quiz created.';
    }
  }
}

/* -------------------------
   BULK ADD QUESTIONS BY IDs
------------------------- */
if ($id && isset($_POST['add_qids'])) {
  csrf_verify();
  $qids  = trim($_POST['qids'] ?? '');
  $marks = (float)($_POST['marks'] ?? 1);
  $neg   = (float)($_POST['neg_marks'] ?? 0);

  if ($qids !== '') {
    $ids = array_filter(array_map('intval', preg_split('/[,\s]+/', $qids)));
    if ($ids) {
      // Start from current max sort
      $maxSort = (int)($pdo->query("SELECT COALESCE(MAX(sort_order),0) m FROM quiz_questions WHERE quiz_id={$id}")->fetch()['m'] ?? 0);
      $ins = $pdo->prepare('INSERT INTO quiz_questions (quiz_id, question_id, marks, neg_marks, sort_order) VALUES (?,?,?,?,?)');

      foreach ($ids as $qid) {
        $maxSort++;
        try {
          $ins->execute([$id, $qid, $marks, $neg, $maxSort]);
        } catch (Throwable $e) {
          // silently skip duplicates/invalid IDs
        }
      }
      $msg = 'Questions added.';
    }
  }
}

/* -------------------------
   SAVE MAPPING (marks/neg/order)
------------------------- */
if ($id && isset($_POST['save_map'])) {
  csrf_verify();
  if (!empty($_POST['qid']) && is_array($_POST['qid'])) {
    $upd = $pdo->prepare('UPDATE quiz_questions SET marks=?, neg_marks=?, sort_order=? WHERE id=? AND quiz_id=?');
    foreach ($_POST['qid'] as $rowId => $qId) {
      $marks = (float)($_POST['marks'][$rowId] ?? 1);
      $neg   = (float)($_POST['neg'][$rowId]   ?? 0);
      $sort  = (int)($_POST['sort'][$rowId]    ?? 0);
      $upd->execute([$marks, $neg, $sort, (int)$rowId, $id]);
    }
    $msg = 'Mapping saved.';
  }
}

/* -------------------------
   REMOVE A QUESTION FROM QUIZ
------------------------- */
if ($id && isset($_GET['rm'], $_GET['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_GET['csrf'])) {
  $pdo->prepare('DELETE FROM quiz_questions WHERE id=? AND quiz_id=?')->execute([(int)$_GET['rm'], $id]);
  header('Location: '.url('admin/quiz_edit.php').'?id='.$id);
  exit;
}

/* -------------------------
   LOAD QUIZ + MAPPING + COURSES
------------------------- */
$quiz = null; $map = [];
if ($id) {
  $st = $pdo->prepare('SELECT * FROM quizzes WHERE id=?');
  $st->execute([$id]);
  $quiz = $st->fetch();

  $m = $pdo->prepare('SELECT
        qq.id, qq.question_id, qq.marks, qq.neg_marks, qq.sort_order,
        q.topic, q.question_type, q.company_tag
      FROM quiz_questions qq
      JOIN questions q ON q.id = qq.question_id
      WHERE qq.quiz_id=?
      ORDER BY qq.sort_order, qq.id');
  $m->execute([$id]);
  $map = $m->fetchAll();
}
$courses = $pdo->query('SELECT id, title FROM courses ORDER BY title')->fetchAll();

/* -------------------------
   RENDER
------------------------- */
include '../includes/header.php';
include __DIR__.'/_nav.php';
?>
<div class="d-flex justify-content-between align-items-center">
  <h4 class="mb-0"><?= $id ? 'Edit Quiz' : 'New Quiz' ?></h4>
  <a class="btn btn-outline-secondary btn-sm" href="<?= url('admin/quizzes.php') ?>">Back</a>
</div>

<?php if ($msg): ?>
  <div class="alert alert-success mt-3"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form method="post" class="card mt-3">
  <div class="card-body">
    <?php csrf_field(); ?>
    <input type="hidden" name="save_quiz" value="1">

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Title</label>
        <input class="form-control" name="title" value="<?= htmlspecialchars($quiz['title'] ?? '') ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Duration (minutes)</label>
        <input type="number" class="form-control" name="duration_minutes" value="<?= (int)($quiz['duration_minutes'] ?? 30) ?>" min="1" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Course</label>
        <select class="form-select" name="course_id">
          <option value="">— General —</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (!empty($quiz['course_id']) && $quiz['course_id']==$c['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Available From</label>
        <input type="datetime-local" class="form-control" name="available_from"
               value="<?= !empty($quiz['available_from']) ? date('Y-m-d\TH:i', strtotime($quiz['available_from'])) : '' ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Available To</label>
        <input type="datetime-local" class="form-control" name="available_to"
               value="<?= !empty($quiz['available_to']) ? date('Y-m-d\TH:i', strtotime($quiz['available_to'])) : '' ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Max Attempts</label>
        <input type="number" class="form-control" name="max_attempts" min="1"
               value="<?= (int)($quiz['max_attempts'] ?? 1) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Pass Marks</label>
        <input type="number" step="0.5" class="form-control" name="pass_marks"
               value="<?= (float)($quiz['pass_marks'] ?? 0) ?>">
      </div>

      <div class="col-md-3 form-check mt-4 pt-2">
        <input class="form-check-input" type="checkbox" name="is_mock" id="is_mock" <?= !empty($quiz['is_mock']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="is_mock">Mock test</label>
      </div>
      <div class="col-md-3 form-check mt-4 pt-2">
        <input class="form-check-input" type="checkbox" name="is_published" id="is_published" <?= !empty($quiz['is_published']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="is_published">Published</label>
      </div>
      <div class="col-md-3 form-check mt-4 pt-2">
        <input class="form-check-input" type="checkbox" name="shuffle_questions" id="shuffle_questions" <?= !empty($quiz['shuffle_questions']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="shuffle_questions">Shuffle questions</label>
      </div>
      <div class="col-md-3 form-check mt-4 pt-2">
        <input class="form-check-input" type="checkbox" name="shuffle_options" id="shuffle_options" <?= !empty($quiz['shuffle_options']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="shuffle_options">Shuffle options (A–D)</label>
      </div>
    </div>

    <div class="mt-3">
      <button class="btn btn-primary"><?= $id ? 'Save' : 'Create' ?></button>
    </div>
  </div>
</form>

<?php if ($id): ?>
  <div class="card mt-3">
    <div class="card-body">
      <h5 class="card-title">Add Questions</h5>
      <form method="post" class="row g-2">
        <?php csrf_field(); ?>
        <input type="hidden" name="add_qids" value="1">
        <div class="col-md-6">
          <input class="form-control" name="qids" placeholder="Enter question IDs (comma or space separated)">
        </div>
        <div class="col-md-2">
          <input class="form-control" name="marks" type="number" step="0.25" value="1" placeholder="Marks">
        </div>
        <div class="col-md-2">
          <input class="form-control" name="neg_marks" type="number" step="0.25" value="0" placeholder="Neg">
        </div>
        <div class="col-md-2">
          <button class="btn btn-outline-primary w-100">Add</button>
        </div>
      </form>
      <div class="form-text mt-1">
        Tip: Use <a href="<?= url('admin/questions.php') ?>" target="_blank">Question Bank</a> to find question IDs.
      </div>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-body">
      <h5 class="card-title">Mapped Questions</h5>
      <form method="post">
        <?php csrf_field(); ?>
        <input type="hidden" name="save_map" value="1">
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>#</th><th>Q.ID</th><th>Topic</th><th>Company</th><th>Type</th>
                <th>Marks</th><th>Neg</th><th>Order</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($map as $i => $m): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td>
                    <a href="<?= url('admin/question_edit.php') ?>?id=<?= (int)$m['question_id'] ?>" target="_blank">
                      <?= (int)$m['question_id'] ?>
                    </a>
                    <input type="hidden" name="qid[<?= (int)$m['id'] ?>]" value="<?= (int)$m['question_id'] ?>">
                  </td>
                  <td><?= htmlspecialchars($m['topic'] ?: 'General') ?></td>
                  <td><?= strtoupper(htmlspecialchars($m['company_tag'])) ?></td>
                  <td><?= htmlspecialchars($m['question_type']) ?></td>
                  <td style="width:110px">
                    <input class="form-control form-control-sm" name="marks[<?= (int)$m['id'] ?>]" value="<?= (float)$m['marks'] ?>">
                  </td>
                  <td style="width:110px">
                    <input class="form-control form-control-sm" name="neg[<?= (int)$m['id'] ?>]" value="<?= (float)$m['neg_marks'] ?>">
                  </td>
                  <td style="width:110px">
                    <input class="form-control form-control-sm" name="sort[<?= (int)$m['id'] ?>]" value="<?= (int)$m['sort_order'] ?>">
                  </td>
                  <td>
                    <a class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Remove from quiz?')"
                       href="<?= url('admin/quiz_edit.php') ?>?id=<?= $id ?>&rm=<?= (int)$m['id'] ?>&csrf=<?= htmlspecialchars(csrf_token()) ?>">
                       Remove
                    </a>
                  </td>
                </tr>
              <?php endforeach; if (!$map): ?>
                <tr><td colspan="9" class="text-secondary">No questions mapped yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <button class="btn btn-primary">Save Mapping</button>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>