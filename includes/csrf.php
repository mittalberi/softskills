<?php
function csrf_token() {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}
function csrf_field() {
  $t = htmlspecialchars(csrf_token(), ENT_QUOTES);
  echo "<input type='hidden' name='csrf' value='{$t}'>";
}
function csrf_verify() {
  if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
      http_response_code(403); exit('Invalid CSRF token.');
    }
  }
}
?>
