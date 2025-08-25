<?php // /admin/_nav.php
?>
<ul class="nav nav-pills gap-2 mb-3 flex-wrap">
  <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])==='index.php'?' active':'' ?>" href="<?= url('admin/index.php') ?>">Dashboard</a></li>
  <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])==='courses.php'?' active':'' ?>" href="<?= url('admin/courses.php') ?>">Courses</a></li>
  <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])==='questions.php'?' active':'' ?>" href="<?= url('admin/questions.php') ?>">Question Bank</a></li>
  <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])==='quizzes.php'?' active':'' ?>" href="<?= url('admin/quizzes.php') ?>">Quizzes</a></li>
  <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF'])==='upload_questions_csv.php'?' active':'' ?>" href="<?= url('admin/upload_questions_csv.php') ?>">CSV Import</a></li>
</ul>
