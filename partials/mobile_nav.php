<!-- Mobile Navigation -->
<nav class="mobile-nav">
  <div class="mobile-nav-content">
    <h1 class="mobile-nav-title"><?= htmlspecialchars($pageTitle ?? "Inventaire PC") ?></h1>
    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
      <i class="bi bi-list"></i>
    </button>
  </div>
  <div class="mobile-menu" id="mobileMenu">
    <a class="mobile-menu-item" href="pcs.php">
      <i class="bi bi-list-ul"></i> Liste des PC
    </a>
    <a class="mobile-menu-item" href="pc_add.php">
      <i class="bi bi-plus-circle"></i> Ajouter un PC
    </a>
    <a class="mobile-menu-item" href="admin_fields.php">
      <i class="bi bi-gear"></i> Gérer les champs
    </a>
  </div>
</nav>

<script>
function toggleMobileMenu() {
  const menu = document.getElementById('mobileMenu');
  menu.classList.toggle('show');
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(event) {
  const nav = document.querySelector('.mobile-nav');
  const menu = document.getElementById('mobileMenu');

  if (menu && menu.classList.contains('show') && !nav.contains(event.target)) {
    menu.classList.remove('show');
  }
});

// Close menu on link click
document.querySelectorAll('.mobile-menu-item').forEach(item => {
  item.addEventListener('click', () => {
    document.getElementById('mobileMenu').classList.remove('show');
  });
});
</script>
