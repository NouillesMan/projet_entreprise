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
