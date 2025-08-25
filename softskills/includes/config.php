<?php
// === Portal Config ===
$portal_name = 'BMIET Learning Portal';
$portal_url  = 'http://localhost/softskills'; // change for prod
$contact_email = 'support@bmiet.net';

// Database
$portal_url  = 'http://localhost/softskills'; // your subfolder URL
$db_host = 'localhost';
$db_name = 'learning_portal';
$db_user = 'portal_user';
$db_pass = 'Str0ng!Pass#2025';   // the same you set above

/**
 * Base path for subfolder deployments, e.g. '/softskills'.
 * Leave '' if deployed at domain root.
 * (Ignore getenv unless you purposely set it.)
 */
$base_path = '/softskills';

function url($path = '') {
  global $base_path;
  $path = ltrim($path, '/');
  if ($base_path) {
    return rtrim($base_path, '/') . '/' . $path;
  }
  return '/' . $path;
}

// Sessions
session_name('bmietlearn');
session_start();
