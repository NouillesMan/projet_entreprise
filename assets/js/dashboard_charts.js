// Dashboard charts — reads window.dashboardData
(function() {
  var d = window.dashboardData;
  if (!d) return;

  // Dark theme defaults
  Chart.defaults.color = '#8b949e';
  Chart.defaults.borderColor = '#30363d';

  var colors = {
    'En service':    '#3fb950',
    'En stock':      '#58a6ff',
    'En réparation': '#d29922',
    'Retiré':        '#8b949e'
  };

  var brandColors = ['#58a6ff', '#3fb950', '#d29922', '#f85149', '#bc8cff'];
  var osColors    = ['#3fb950', '#58a6ff', '#d29922', '#f85149', '#bc8cff'];

  // Status doughnut
  var statusCtx = document.getElementById('chartStatus');
  if (statusCtx && d.statut) {
    var labels = Object.keys(d.statut);
    var values = Object.values(d.statut);
    var bgColors = labels.map(function(l) { return colors[l] || '#8b949e'; });
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
          x: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#21262d' } },
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
          x: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#21262d' } },
          y: { grid: { display: false } }
        }
      }
    });
  }
})();
