</main>
<footer class="site-footer py-4">
  <div class="container small text-muted">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <span>© <?= date('Y') ?> <?= htmlspecialchars($portal_name) ?>.</span>
      <span>Need help? <a href="mailto:<?= htmlspecialchars($contact_email) ?>"><?= htmlspecialchars($contact_email) ?></a></span>
    </div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
