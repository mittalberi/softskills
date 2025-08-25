<?php
require_once __DIR__.'/config.php'; // add this line at top if not present

function require_login() {
  if (empty($_SESSION['user'])) {
    header('Location: ' . url('auth/login.php'));  // was: /auth/login.php
    exit;
  }
}
function require_role($role_name) {
  require_login();
  if (($_SESSION['user']['role'] ?? '') !== $role_name && $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403); exit('Forbidden');
  }
}
