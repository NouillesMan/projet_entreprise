/**
 * Shared JS for pc_add.php and pc_edit.php
 * Expects window.modelesByBrand to be set before this script loads.
 */
(function () {
  var modelesByBrand = window.modelesByBrand || {};

  // ── Brand → Model cascade ──────────────────────────────────────────────
  var marqueSelect = document.getElementById('marque');
  var modeleSelect = document.getElementById('modele');
  var modeleCustom = document.getElementById('modele_custom');

  if (marqueSelect) {
    marqueSelect.addEventListener('change', function () {
      var brand = this.value;
      modeleSelect.innerHTML = '<option value="">Sélectionner...</option>';

      if (modelesByBrand[brand]) {
        modelesByBrand[brand].forEach(function (model) {
          var opt = document.createElement('option');
          opt.value = model;
          opt.textContent = model;
          modeleSelect.appendChild(opt);
        });
      }

      var otherOpt = document.createElement('option');
      otherOpt.value = '__custom__';
      otherOpt.textContent = '+ Modèle personnalisé';
      modeleSelect.appendChild(otherOpt);
    });
  }

  // ── Custom model toggle ────────────────────────────────────────────────
  if (modeleSelect) {
    modeleSelect.addEventListener('change', function () {
      if (this.value === '__custom__') {
        modeleCustom.style.display = 'block';
        modeleCustom.required = true;
      } else {
        modeleCustom.style.display = 'none';
        modeleCustom.required = false;
      }
    });
  }

  // ── Custom user toggle ─────────────────────────────────────────────────
  var utilisateurSelect = document.getElementById('utilisateur');
  var utilisateurCustom = document.getElementById('utilisateur_custom');

  if (utilisateurSelect) {
    utilisateurSelect.addEventListener('change', function () {
      if (this.value === '__nouveau__') {
        utilisateurCustom.style.display = 'block';
        utilisateurCustom.required = true;
        this.removeAttribute('name');
        utilisateurCustom.setAttribute('name', 'utilisateur');
      } else {
        utilisateurCustom.style.display = 'none';
        utilisateurCustom.required = false;
        this.setAttribute('name', 'utilisateur');
        utilisateurCustom.removeAttribute('name');
      }
    });
  }

  // ── Form submit: resolve custom fields ─────────────────────────────────
  var form = document.querySelector('form');
  if (form) {
    form.addEventListener('submit', function () {
      // Model
      if (modeleCustom && modeleCustom.value.trim() !== '') {
        modeleSelect.value = '';
        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'modele';
        hidden.value = modeleCustom.value;
        this.appendChild(hidden);
        modeleCustom.removeAttribute('name');
      } else if (modeleSelect && modeleSelect.value && modeleSelect.value !== '__custom__') {
        modeleSelect.setAttribute('name', 'modele');
      }

      // OS Version
      var osVersionSelect = document.querySelector('select[name="os_version"]');
      var osVersionCustom = document.querySelector('input[name="os_version_custom"]');

      if (osVersionCustom && osVersionCustom.value.trim() !== '') {
        osVersionSelect.value = '';
        var hiddenV = document.createElement('input');
        hiddenV.type = 'hidden';
        hiddenV.name = 'os_version';
        hiddenV.value = osVersionCustom.value;
        this.appendChild(hiddenV);
        osVersionCustom.removeAttribute('name');
      } else if (osVersionSelect && osVersionSelect.value) {
        osVersionSelect.setAttribute('name', 'os_version');
      }
    });
  }
})();
