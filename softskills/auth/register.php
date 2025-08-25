<?php
require_once '../includes/db.php';
require_once '../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  csrf_verify();
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pass = $_POST['password'] ?? '';
  if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($pass)>=6) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email=?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) $error = 'Email already registered.';
    else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $roleId = $pdo->query("SELECT id FROM roles WHERE name='student'")->fetchColumn();
      $ins = $pdo->prepare('INSERT INTO users(name,email,password_hash,role_id) VALUES (?,?,?,?)');
      $ins->execute([$name,$email,$hash,$roleId]);
      header('Location: /auth/login.php?ok=1'); exit;
    }
  } else $error = 'Invalid input or weak password.';
}
include '../includes/header.php';
?>
<h3>Create account</h3>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" class="col-md-6">
  <?php csrf_field(); ?>
  <div class="mb-3">
    <label class="form-label">Name</label>
    <input name="name" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input name="email" type="email" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Password (min 6 chars)</label>
    <input name="password" type="password" class="form-control" minlength="6" required>
  </div>
  <button class="btn btn-primary">Register</button>
</form>
<?php include '../includes/footer.php'; ?>
