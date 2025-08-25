// Light/Dark toggle using Bootstrap 5.3 theme attribute
(function() {
  const key = 'theme-pref';
  const root = document.documentElement;
  const stored = localStorage.getItem(key);
  if (stored) root.setAttribute('data-bs-theme', stored);

  const btn = document.getElementById('themeToggle');
  if (btn) {
    btn.addEventListener('click', () => {
      const curr = root.getAttribute('data-bs-theme') || 'light';
      const next = curr === 'light' ? 'dark' : 'light';
      root.setAttribute('data-bs-theme', next);
      localStorage.setItem(key, next);
      btn.innerText = next === 'dark' ? 'Light' : 'Dark';
    });
  }
})();
