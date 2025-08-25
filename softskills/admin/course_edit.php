<?php
// /admin/course_edit.php (final PRG)
require_once '../includes/db.php';
require_once '../includes/auth_guard.php';
require_once '../includes/csrf.php';
require_role('instructor');

$id = (int)($_GET['id'] ?? 0);

// --- helpers -------------------------------------------------
function redirect_edit($course_id, $msg = null) {
  $u = url('admin/course_edit.php') . '?id=' . (int)$course_id;
  if ($msg) $u .= '&msg=' . urlencode($msg);
  header('Location: ' . $u);
  exit;
}

// --- handle POST actions (PRG: every change ends with redirect) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  // CREATE / UPDATE course
  if (isset($_POST['save_course'])) {
    $title = trim($_POST['title'] ?? '');
    $short = trim($_POST['short_desc'] ?? '');
    $desc  = $_POST['description'] ?? '';
    $pub   = isset($_POST['is_published']) ? 1 : 0;

    if ($title === '') {
      // keep id on redirect if editing, else go back to list with error
      if ($id) redirect_edit($id, 'err_title');
      header('Location: ' . url('admin/courses.php') . '?msg=err_title'); exit;
    }

    if ($id) {
      $pdo->prepare('UPDATE courses SET title=?, short_desc=?, description=?, is_published=? WHERE id=?')
          ->execute([$title,$short,$desc,$pub,$id]);
      redirect_edit($id, 'course_saved');
    } else {
      $pdo->prepare('INSERT INTO courses (title, short_desc, description, is_published, created_by) VALUES (?,?,?,?,?)')
          ->execute([$title,$short,$desc,$pub,$_SESSION['user']['id']]);
      $newId = (int)$pdo->lastInsertId();
      redirect_edit($newId, 'course_created');
    }
  }

  // ADD module
  if ($id && isset($_POST['add_module'])) {
    $mtitle = trim($_POST['module_title'] ?? '');
    $sort   = ($_POST['module_sort'] === '' ? null : (int)$_POST['module_sort']);
    if ($mtitle === '') redirect_edit($id, 'err_module_title');

    if ($sort === null) {
      $q = $pdo->prepare('SELECT COALESCE(MAX(sort_order),0) m FROM modules WHERE course_id=?');
      $q->execute([$id]); $max = (int)($q->fetch()['m'] ?? 0);
      $sort = $max + 10;
    }
    $pdo->prepare('INSERT INTO modules (course_id,title,sort_order) VALUES (?,?,?)')
        ->execute([$id,$mtitle,$sort]);
    redirect_edit($id, 'module_added');
  }

  // UPDATE module
  if ($id && isset($_POST['update_module'])) {
    $mid   = (int)($_POST['module_id'] ?? 0);
    $title = trim($_POST['module_title_edit'] ?? '');
    $sort  = (int)($_POST['module_sort_edit'] ?? 0);
    if (!$mid || $title === '') redirect_edit($id, 'err_module_title');

    $pdo->prepare('UPDATE modules SET title=?, sort_order=? WHERE id=? AND course_id=?')
        ->execute([$title,$sort,$mid,$id]);
    redirect_edit($id, 'module_saved');
  }

  // ADD lesson
  if ($id && isset($_POST['add_lesson'])) {
    $module_id = (int)($_POST['lesson_module_id'] ?? 0);
    $ltitle    = trim($_POST['lesson_title'] ?? '');
    $lcontent  = $_POST['lesson_content'] ?? '';
    $lsort     = ($_POST['lesson_sort'] === '' ? null : (int)$_POST['lesson_sort']);
    if (!$module_id || $ltitle === '') redirect_edit($id, 'err_lesson_title');

    if ($lsort === null) {
      $q = $pdo->prepare('SELECT COALESCE(MAX(sort_order),0) m FROM lessons WHERE module_id=?');
      $q->execute([$module_id]); $max = (int)($q->fetch()['m'] ?? 0);
      $lsort = $max + 10;
    }
    $pdo->prepare('INSERT INTO lessons (module_id,title,content,sort_order) VALUES (?,?,?,?)')
        ->execute([$module_id,$ltitle,$lcontent,$lsort]);
    redirect_edit($id, 'lesson_added');
  }

  // UPDATE lesson (title/sort/move)
  if ($id && isset($_POST['update_lesson'])) {
    $lid   = (int)($_POST['lesson_id'] ?? 0);
    $title = trim($_POST['lesson_title_edit'] ?? '');
    $sort  = (int)($_POST['lesson_sort_edit'] ?? 0);
    $mod   = (int)($_POST['lesson_module_edit'] ?? 0);
    if (!$lid || !$mod || $title === '') redirect_edit($id, 'err_lesson_title');

    $pdo->prepare('UPDATE lessons SET title=?, sort_order=?, module_id=? WHERE id=?')
        ->execute([$title,$sort,$mod,$lid]);
    redirect_edit($id, 'lesson_saved');
  }

  // Fallback
  redirect_edit($id ?: 0);
}

// --- handle GET deletes (with CSRF) ---------------------------
if ($id && isset($_GET['del_module'], $_GET['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_GET['csrf'])) {
  $mid = (int)$_GET['del_module'];
  $pdo->beginTransaction();
  try {
    $pdo->prepare('DELETE FROM lessons WHERE module_id=?')->execute([$mid]); // if no FK cascade
    $pdo->prepare('DELETE FROM modules WHERE id=? AND course_id=?')->execute([$mid,$id]);
    $pdo->commit();
    redirect_edit($id, 'module_deleted');
  } catch (Throwable $e) {
    $pdo->rollBack();
    redirect_edit($id, 'err_module_delete');
  }
}

if ($id && isset($_GET['del_lesson'], $_GET['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_GET['csrf'])) {
  $pdo->prepare('DELETE FROM lessons WHERE id=?')->execute([(int)$_GET['del_lesson']]);
  redirect_edit($id, 'lesson_deleted');
}

// --- load course + structure ---------------------------------
$course = null;
if ($id) {
  $st = $pdo->prepare('SELECT * FROM courses WHERE id=?');
  $st->execute([$id]);
  $course = $st->fetch();
  if (!$course) { http_response_code(404); exit('Course not found'); }
}

$modules = [];
if ($id) {
  $ms = $pdo->prepare('SELECT * FROM modules WHERE course_id=? ORDER BY sort_order, id');
  $ms->execute([$id]);
  $modules = $ms->fetchAll();
}

// --- small message map ---------------------------------------
$msgMap = [
  'course_created'   => 'Course created.',
  'course_saved'     => 'Course updated.',
  'module_added'     => 'Module added.',
  'module_saved'     => 'Module updated.',
  'module_deleted'   => 'Module deleted.',
  'lesson_added'     => 'Lesson added.',
  'lesson_saved'     => 'Lesson updated.',
  'lesson_deleted'   => 'Lesson deleted.',
  'err_title'        => 'Course title is required.',
  'err_module_title' => 'Module title is required.',
  'err_lesson_title' => 'Lesson title and module are required.',
  'err_module_delete'=> 'Could not delete module.',
];
$flashKey = $_GET['msg'] ?? '';
$flashMsg = $msgMap[$flashKey] ?? '';

include '../includes/header.php';
include __DIR__.'/_nav.php';
?>
<div class="d-flex justify-content-between align-items-center">
  <h4 class="mb-0"><?= $id ? 'Edit Course' : 'New Course' ?></h4>
  <div class="d-flex gap-2">
    <?php if ($id): ?>
      <a class="btn btn-outline-secondary btn-sm" href="<?= url('courses/view.php') ?>?id=<?= (int)$id ?>" target="_blank">Preview</a>
    <?php endif; ?>
    <a class="btn btn-outline-secondary btn-sm" href="<?= url('admin/courses.php') ?>">Back</a>
  </div>
</div>

<?php if ($flashMsg): ?>
  <div class="alert <?= str_starts_with($flashKey,'err_') ? 'alert-danger' : 'alert-success' ?> mt-3">
    <?= htmlspecialchars($flashMsg) ?>
  </div>
<?php endif; ?>

<form method="post" class="card mt-3">
  <div class="card-body">
    <?php csrf_field(); ?>
    <input type="hidden" name="save_course" value="1">
    <div class="mb-3">
      <label class="form-label">Title</label>
      <input class="form-control" name="title" value="<?= htmlspecialchars($course['title'] ?? '') ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Short Description</label>
      <input class="form-control" name="short_desc" value="<?= htmlspecialchars($course['short_desc'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Description (HTML allowed)</label>
      <textarea class="form-control" name="description" rows="5"><?= htmlspecialchars($course['description'] ?? '') ?></textarea>
    </div>
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="is_published" id="pub" <?= !empty($course['is_published']) ? 'checked' : '' ?>>
      <label class="form-check-label" for="pub">Published</label>
    </div>
    <button class="btn btn-primary"><?= $id ? 'Save' : 'Create' ?></button>
  </div>
</form>

<?php if ($id): ?>
<div class="row g-3 mt-2">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Modules</h5>

        <!-- Add Module -->
        <form method="post" class="row g-2">
          <?php csrf_field(); ?>
          <input type="hidden" name="add_module" value="1">
          <div class="col-md-7">
            <input class="form-control" name="module_title" placeholder="Module title" required>
          </div>
          <div class="col-md-3">
            <input class="form-control" name="module_sort" type="number" placeholder="Order (auto if empty)">
          </div>
          <div class="col-md-2">
            <button class="btn btn-outline-primary w-100">Add</button>
          </div>
        </form>

        <ul class="list-group list-group-flush mt-3">
          <?php foreach ($modules as $m): ?>
            <li class="list-group-item">
              <div class="d-flex justify-content-between align-items-start">
                <!-- Quick-edit module -->
                <form method="post" class="row g-2 flex-grow-1 me-2">
                  <?php csrf_field(); ?>
                  <input type="hidden" name="update_module" value="1">
                  <input type="hidden" name="module_id" value="<?= (int)$m['id'] ?>">
                  <div class="col-md-7">
                    <input class="form-control form-control-sm" name="module_title_edit" value="<?= htmlspecialchars($m['title']) ?>" required>
                  </div>
                  <div class="col-md-3">
                    <input class="form-control form-control-sm" name="module_sort_edit" type="number" value="<?= (int)$m['sort_order'] ?>">
                  </div>
                  <div class="col-md-2">
                    <button class="btn btn-sm btn-outline-primary w-100">Save</button>
                  </div>
                </form>
                <a class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Delete module and its lessons?')"
                   href="<?= url('admin/course_edit.php') ?>?id=<?= (int)$id ?>&del_module=<?= (int)$m['id'] ?>&csrf=<?= htmlspecialchars(csrf_token()) ?>">
                  Delete
                </a>
              </div>

              <?php
                $ls = $pdo->prepare('SELECT * FROM lessons WHERE module_id=? ORDER BY sort_order, id');
                $ls->execute([$m['id']]);
                $lessons = $ls->fetchAll();
              ?>
              <ul class="list-group list-group-flush mt-2">
                <?php foreach ($lessons as $l): ?>
                  <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                      <!-- Quick-edit lesson -->
                      <form method="post" class="row g-2 flex-grow-1 me-2">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="update_lesson" value="1">
                        <input type="hidden" name="lesson_id" value="<?= (int)$l['id'] ?>">
                        <div class="col-md-5">
                          <input class="form-control form-control-sm" name="lesson_title_edit" value="<?= htmlspecialchars($l['title']) ?>" required>
                        </div>
                        <div class="col-md-2">
                          <input class="form-control form-control-sm" name="lesson_sort_edit" type="number" value="<?= (int)$l['sort_order'] ?>">
                        </div>
                        <div class="col-md-3">
                          <select class="form-select form-select-sm" name="lesson_module_edit" required>
                            <?php foreach ($modules as $mm): ?>
                              <option value="<?= (int)$mm['id'] ?>" <?= $mm['id']==$m['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mm['title']) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="col-md-2">
                          <button class="btn btn-sm btn-outline-primary w-100">Save</button>
                        </div>
                      </form>

                      <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= url('lessons/view.php') ?>?id=<?= (int)$l['id'] ?>" target="_blank">View</a>
                        <a class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Delete lesson?')"
                           href="<?= url('admin/course_edit.php') ?>?id=<?= (int)$id ?>&del_lesson=<?= (int)$l['id'] ?>&csrf=<?= htmlspecialchars(csrf_token()) ?>">
                          Delete
                        </a>
                      </div>
                    </div>
                  </li>
                <?php endforeach; if (!$lessons): ?>
                  <li class="list-group-item text-secondary">No lessons.</li>
                <?php endif; ?>
              </ul>

              <!-- Add lesson under this module -->
              <form method="post" class="row g-2 mt-2">
                <?php csrf_field(); ?>
                <input type="hidden" name="add_lesson" value="1">
                <input type="hidden" name="lesson_module_id" value="<?= (int)$m['id'] ?>">
                <div class="col-md-6">
                  <input class="form-control" name="lesson_title" placeholder="Lesson title" required>
                </div>
                <div class="col-md-3">
                  <input class="form-control" name="lesson_sort" type="number" placeholder="Order (auto if empty)">
                </div>
                <div class="col-12">
                  <textarea class="form-control" name="lesson_content" rows="3" placeholder="Lesson HTML content... (You can paste a YouTube link to auto-embed)"></textarea>
                </div>
                <div class="col-12">
                  <button class="btn btn-outline-primary">Add Lesson</button>
                </div>
              </form>
            </li>
          <?php endforeach; if (!$modules): ?>
            <li class="list-group-item text-secondary">No modules yet.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="alert alert-info">
      <strong>Tips</strong><br>
      • Leave <em>Order</em> blank to auto-place at the end (+10 steps).<br>
      • Paste a YouTube link in lesson content to auto-embed on the lesson page.<br>
      • Use the dropdown to move lessons between modules, then <em>Save</em>.
    </div>
  </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
