// Dashboard charts — reads window.dashboardData
(function() {
  var d = window.dashboardData;
  if (!d) return;

  // Read theme colors from CSS custom properties so charts follow light/dark mode
  var css = getComputedStyle(document.documentElement);
  var cssVar = function(name, fallback) {
    var v = css.getPropertyValue(name).trim();
    return v || fallback;
  };

  var fgMuted     = cssVar('--gh-fg-muted', '#8b949e');
  var borderMuted = cssVar('--gh-border-muted', '#21262d');
  var accentBlue  = cssVar('--gh-accent-blue', '#58a6ff');
  var accentGreen = cssVar('--gh-accent-green', '#3fb950');
  var accentYel   = cssVar('--gh-accent-yellow', '#d29922');
  var accentRed   = cssVar('--gh-accent-red', '#ff7b72');
  var accentPurp  = cssVar('--gh-accent-purple', '#bc8cff');

  Chart.defaults.color = fgMuted;
  Chart.defaults.borderColor = borderMuted;

  var colors = {
    'En service':    accentGreen,
    'En stock':      accentBlue,
    'En réparation': accentYel,
    'Retiré':        fgMuted
  };

  var brandColors = [accentBlue, accentGreen, accentYel, accentRed, accentPurp];
  var osColors    = [accentGreen, accentBlue, accentYel, accentRed, accentPurp];

  // Status doughnut
  var statusCtx = document.getElementById('chartStatus');
  if (statusCtx && d.statut) {
    var labels = Object.keys(d.statut);
    var values = Object.values(d.statut);
    var bgColors = labels.map(function(l) { return colors[l] || fgMuted; });
    new Chart(statusCtx, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{ data: values, backgroundColor: bgColors, borderWidth: 0 }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { padding: 12 } }
        },
        cutout: '60%'
      }
    });
  }

  // Top brands horizontal bar
  var brandsCtx = document.getElementById('chartBrands');
  if (brandsCtx && d.brands) {
    var bLabels = Object.keys(d.brands);
    var bValues = Object.values(d.brands);
    new Chart(brandsCtx, {
      type: 'bar',
      data: {
        labels: bLabels,
        datasets: [{
          data: bValues,
          backgroundColor: bLabels.map(function(_, i) { return brandColors[i % brandColors.length]; }),
          borderRadius: 4
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: borderMuted } },
          y: { grid: { display: false } }
        }
      }
    });
  }

  // Top OS horizontal bar
  var osCtx = document.getElementById('chartOs');
  if (osCtx && d.os) {
    var oLabels = Object.keys(d.os);
    var oValues = Object.values(d.os);
    new Chart(osCtx, {
      type: 'bar',
      data: {
        labels: oLabels,
        datasets: [{
          data: oValues,
          backgroundColor: oLabels.map(function(_, i) { return osColors[i % osColors.length]; }),
          borderRadius: 4
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: borderMuted } },
          y: { grid: { display: false } }
        }
      }
    });
  }
})();
