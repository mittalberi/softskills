<?php
require_once '../includes/db.php';
require_once '../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  csrf_verify();
  $email = trim($_POST['email'] ?? '');
  $pass = $_POST['password'] ?? '';
  $stmt = $pdo->prepare('SELECT u.*, r.name AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE email=? AND is_active=1');
  $stmt->execute([$email]);
  $user = $stmt->fetch();
  if ($user && password_verify($pass, $user['password_hash'])) {
	// after verifying password
    $_SESSION['user'] = ['id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email'],'role'=>$user['role']];
    header('Location: ' . url(''));   // was: header('Location: /');
    exit;
  } else $error = 'Invalid credentials.';
}
include '../includes/header.php';
?>
<h3>Login</h3>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" class="col-md-6">
  <?php csrf_field(); ?>
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input name="email" type="email" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Password</label>
    <input name="password" type="password" class="form-control" required>
  </div>
  <button class="btn btn-primary">Login</button>
</form>
<?php include '../includes/footer.php'; ?>
