<?php include __DIR__.'/includes/header.php'; ?>
<section class="hero rounded-2xl mb-4">
  <div class="row align-items-center">
    <div class="col-lg-7">
      <span class="badge badge-soft mb-2">Campus Hiring Prep</span>
      <h1 class="display-5 mb-2">Learn. Practice. <span class="text-primary">Crack</span> your Infosys, TCS & Wipro tests.</h1>
      <p class="lead text-secondary">Aptitude, Reasoning, Verbal, and Mocks — all curated for entry-level IT roles.</p>
      <div class="d-flex gap-2 mt-3">
        <a href="<?= url('courses/') ?>" class="btn btn-primary btn-lg">Browse Courses</a>
        <a href="<?= url('mocks/') ?>" class="btn btn-outline-primary btn-lg">Take a Mock</a>
      </div>
    </div>
    <div class="col-lg-5 mt-4 mt-lg-0">
      <div class="card card-hover">
        <div class="card-body">
          <h5 class="card-title mb-2">Continue Learning</h5>
          <p class="text-secondary mb-0">Login to see your progress and resume where you left.</p>
          <div class="mt-3">
            <a href="<?= url('auth/login.php') ?>" class="btn btn-outline-secondary btn-sm">Login</a>
            <a href="<?= url('auth/register.php') ?>" class="btn btn-secondary btn-sm">Create account</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="mb-4">
  <h3 class="h4 mb-3">Popular Tracks</h3>
  <div class="row g-3">
    <div class="col-md-4">
      <div class="card card-hover h-100">
        <div class="card-body">
          <h5 class="card-title">Quantitative Aptitude</h5>
          <p class="card-text text-secondary">Number System, Percentages, Time & Work, Probability, DI.</p>
          <a href="<?= url('courses/') ?>" class="stretched-link">Explore →</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card card-hover h-100">
        <div class="card-body">
          <h5 class="card-title">Logical Reasoning</h5>
          <p class="card-text text-secondary">Puzzles, Seating, Syllogisms, Series, Coding-Decoding.</p>
          <a href="<?= url('courses/') ?>" class="stretched-link">Explore →</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card card-hover h-100">
        <div class="card-body">
          <h5 class="card-title">Verbal Ability</h5>
          <p class="card-text text-secondary">RC, Error Spotting, Sentence Correction, Vocabulary.</p>
          <a href="<?= url('courses/') ?>" class="stretched-link">Explore →</a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__.'/includes/footer.php'; ?>
