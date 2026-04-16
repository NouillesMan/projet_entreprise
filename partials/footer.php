<?php if (isset($_SESSION["user_id"])): ?>
  <footer class="app-footer">
    &copy; <?= date("Y") ?> Inventaire PC
  </footer>
</main><!-- /.app-main -->
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if (isset($_SESSION["user_id"])): ?>
<script>
// Sidebar toggle for mobile
(function() {
  const toggle   = document.getElementById('sidebarToggle');
  const sidebar  = document.getElementById('appSidebar');
  const backdrop = document.getElementById('sidebarBackdrop');

  if (!toggle || !sidebar) return;

  function openSidebar()  { sidebar.classList.add('show'); backdrop.classList.add('show'); }
  function closeSidebar() { sidebar.classList.remove('show'); backdrop.classList.remove('show'); }

  toggle.addEventListener('click', function() {
    sidebar.classList.contains('show') ? closeSidebar() : openSidebar();
  });

  backdrop.addEventListener('click', closeSidebar);

  // Close on nav link click (mobile)
  sidebar.querySelectorAll('.nav-link').forEach(function(link) {
    link.addEventListener('click', closeSidebar);
  });
})();

// Flash panel dismiss
document.querySelectorAll('.flash-dismiss').forEach(function(btn) {
  btn.addEventListener('click', function(e) {
    e.stopPropagation();
    btn.closest('.flash-panel').remove();
  });
});

// Theme toggle
(function() {
  var themeBtn = document.getElementById('themeToggle');
  if (!themeBtn) return;
  themeBtn.addEventListener('click', function() {
    var html = document.documentElement;
    var current = html.getAttribute('data-bs-theme');
    var next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-bs-theme', next);
    document.cookie = 'theme=' + next + ';path=/;max-age=31536000;SameSite=Lax';
    var icon = themeBtn.querySelector('i');
    icon.className = 'bi bi-' + (next === 'dark' ? 'sun' : 'moon-stars');
    // Update button text
    var textNode = themeBtn.childNodes[themeBtn.childNodes.length - 1];
    textNode.textContent = next === 'dark' ? ' Mode clair' : ' Mode sombre';
  });
})();
</script>
<?php endif; ?>

<?php
$pageScripts = $pageScripts ?? "";
if ($pageScripts !== "") {
  echo $pageScripts;
}
?>
</body>
</html>
