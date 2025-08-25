<?php
require_once '../includes/config.php'; // or __DIR__.'/../includes/config.php'
$_SESSION = [];
session_destroy();
header('Location: ' . url(''));   // was: '/'