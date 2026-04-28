<?php
require_once 'includes/api.php';
$arbres       = getAllArbres();
$stats        = getStats();
$total        = $stats['total'] ?? 0;
$remarquables = $stats['remarquables'] ?? 0;
$especes_u    = $stats['especes'] ?? 0;
$avg_h        = $stats['hauteur_totale_moyenne'] ?? 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Visualisation — ArboData</title>
  <link rel="stylesheet" href="assets/style.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
</head>
<body>

<?php include 'includes/nav.php'; ?>

<div class="page-header">
  <h1>Visualisation</h1>
  <p>Tableau et carte de tous les arbres de la base de données.</p>
</div>

<div class="container">

  <div class="stats-row">
    <div class="stat"><div class="val"><?= $total ?></div><div class="lbl">Total</div></div>
    <div class="stat"><div class="val"><?= $remarquables ?></div><div class="lbl">Remarquables</div></div>
    <div class="stat"><div class="val"><?= $avg_h ?>m</div><div class="lbl">Hauteur moy.</div></div>
    <div class="stat"><div class="val"><?= $especes_u ?></div><div class="lbl">Espèces</div></div>
  </div>

  <div class="tabs">
    <button class="tab-btn active" onclick="switchTab('tableau', this)">Tableau</button>
    <button class="tab-btn" onclick="switchTab('carte', this)" id="btn-carte">Carte</button>
  </div>

  <!-- TABLEAU -->
  <div class="tab-panel active" id="tab-tableau">
    <div class="card">
      <div class="table-bar">
        <h2><?= $total ?> arbre<?= $total > 1 ? 's' : '' ?></h2>
        <div style="display:flex;gap:.6rem;align-items:center;">
          <input class="search" type="text" id="searchInput" placeholder="Rechercher…" oninput="filterTable()">
          <a href="ajouter.php" class="btn btn-primary btn-sm">+ Ajouter</a>
        </div>
      </div>
      <div class="table-wrap">
        <table id="arbreTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Espèce</th>
              <th>H. totale</th>
              <th>H. tronc</th>
              <th>Ø tronc</th>
              <th>Remarquable</th>
              <th>X (EPSG:3949)</th>
              <th>Y (EPSG:3949)</th>
              <th>État</th>
              <th>Stade dev.</th>
            </tr>
          </thead>
          <tbody>

          <!-- Si aucun arbre -->
          <?php if (empty($arbres)): ?>
            <tr><td colspan="12" style="text-align:center;padding:2rem;color:var(--muted);">
              Aucun arbre. <a href="ajouter.php" style="color:var(--green);">Ajouter le premier →</a>
            </td></tr>
          
          <!-- Sinon, afficher les arbres -->
          <?php else: foreach ($arbres as $a):
            $etat = htmlspecialchars($a['etat'] ?? ($a['etat'] ?? '—'));
            $especeNom = trim((string) ($a['espece_nom'] ?? ''));
            $especeFeuillage = trim((string) ($a['espece_feuillage'] ?? ''));
            $especeLabel = trim($especeNom . ' (' . $especeFeuillage . ')');
            $low  = strtolower($etat);
            $cls  = 'badge-gray';
            if (str_contains($low,'bon') || str_contains($low,'good')) $cls = 'badge-green';
            elseif (str_contains($low,'moyen') || str_contains($low,'fair')) $cls = 'badge-yellow';
            elseif (str_contains($low,'mauvais') || str_contains($low,'bad') || str_contains($low,'mort')) $cls = 'badge-red';
          ?>
            <tr>
              <td style="color:var(--muted);"><?= (int)($a['id_arbre'] ?? 0) ?></td>
              <td style="font-style:italic;"><?= htmlspecialchars($especeLabel !== '' ? $especeLabel : '—') ?></td>
              <td><?= isset($a['haut_tot']) ? number_format((float)$a['haut_tot'],1).' m' : '—' ?></td>
              <td><?= isset($a['haut_tronc'])  ? number_format((float)$a['haut_tronc'], 1).' m' : '—' ?></td>
              <td><?= isset($a['diam_tronc']) ? number_format((float)$a['diam_tronc'],1).' cm': '—' ?></td>
              <td><?= !empty($a['remarquable']) ? '<span class="badge badge-green">Oui</span>' : '<span class="badge badge-gray">Non</span>' ?></td>
              <td style="font-variant-numeric:tabular-nums;font-size:.8rem;"><?= isset($a['X'])  ? number_format((float)$a['X'], 6) : '—' ?></td>
              <td style="font-variant-numeric:tabular-nums;font-size:.8rem;"><?= isset($a['Y']) ? number_format((float)$a['Y'],6) : '—' ?></td>
              <td><span class="badge <?= $cls ?>"><?= $etat ?></span></td>
              <td><?= htmlspecialchars($a['stade_dev'] ?? ($a['libelle_stade'] ?? '—')) ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <div style="padding:.65rem 1.25rem;font-size:.8rem;color:var(--muted);border-top:1px solid var(--border);">
        <span id="rowCount"><?= $total ?></span> ligne<?= $total > 1 ? 's' : '' ?>
      </div>
    </div>
  </div>

  <!-- CARTE -->
  <div class="tab-panel" id="tab-carte">
    <div id="map" style="height:500px;"></div>
  </div>

  <!-- CTA -->
  <div style="margin-top:1.5rem;display:flex;justify-content:flex-end;">
    <a href="clusters.php" class="btn btn-primary btn-lg">Prédire les clusters →</a>
  </div>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.11.0/proj4.js"></script>
<script>
function switchTab(name, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  btn.classList.add('active');
  if (name === 'carte' && !window._mapInit) initMap();
}

function filterTable() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  let n = 0;
  document.querySelectorAll('#arbreTable tbody tr').forEach(r => {
    const ok = r.textContent.toLowerCase().includes(q);
    r.style.display = ok ? '' : 'none';
    if (ok) n++;
  });
  document.getElementById('rowCount').textContent = n;
}

var arbres = <?= json_encode(array_values($arbres), JSON_UNESCAPED_UNICODE) ?>;

function initMap() {
  window._mapInit = true;
  proj4.defs('EPSG:3949', '+proj=lcc +lat_1=48.25 +lat_2=49.75 +lat_0=49 +lon_0=3 +x_0=1700000 +y_0=9200000 +ellps=GRS80 +units=m +no_defs +type=crs');

  const SAINT_QUENTIN = [49.8489, 3.2870];
  const map = L.map('map').setView(SAINT_QUENTIN, 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors', maxZoom: 19
  }).addTo(map);

  const icon = L.divIcon({
    className: '',
    html: '<div style="width:10px;height:10px;background:#2e6b45;border:2px solid white;border-radius:50%;box-shadow:0 1px 3px rgba(0,0,0,.3);"></div>',
    iconSize: [10,10], iconAnchor: [5,5]
  });

  const pts = [];
  arbres.forEach(a => {
    const x = parseFloat(a.X);
    const y = parseFloat(a.Y);
    if (isNaN(x) || isNaN(y)) return;

    let lat = null;
    let lng = null;

    // If values are already WGS84, keep them; otherwise convert from EPSG:3949.
    if (Math.abs(x) <= 180 && Math.abs(y) <= 90) {
      lng = x;
      lat = y;
    } else {
      const wgs84 = proj4('EPSG:3949', 'EPSG:4326', [x, y]);
      lng = wgs84[0];
      lat = wgs84[1];
      console.log(`Converted (${x}, ${y}) to (${lat}, ${lng})`);
    }

    if (isNaN(lat) || isNaN(lng)) return;

    const rows = [
      ['Espèce',     a.espece_nom || '—'],
      ['H. totale',  a.haut_tot != null ? a.haut_tot + ' m' : '—'],
      ['Ø tronc',    a.diam_tronc != null ? a.diam_tronc + ' cm' : '—'],
      ['État',       a.etat || '—'],
      ['Stade',      a.stade_dev || '—'],
    ].map(([k,v]) => `<tr><td style="padding:.15rem .5rem .15rem 0;color:#6b7280;font-size:.78rem;white-space:nowrap">${k}</td><td>${v}</td></tr>`).join('');

    L.marker([lat,lng], {icon}).addTo(map).bindTooltip(
      `<div style="font-family:'Inter',sans-serif;font-size:.82rem;min-width:160px;">
        <div style="font-weight:600;margin-bottom:.4rem;font-style:italic;">${a.espece_nom || 'Arbre #'+(a.id_arbre ?? '')}</div>
        <table style="border-collapse:collapse">${rows}</table>
       </div>`,
      { sticky: true, direction: 'top', offset: [0,-8], opacity: 1 }
    );
    pts.push([lat,lng]);
  });

  if (pts.length > 0) {
    map.fitBounds(pts, {padding:[40,40]});
  } else {
    map.setView(SAINT_QUENTIN, 13);
  }
}

if (window.location.hash === '#carte') document.getElementById('btn-carte').click();
</script>
</body>
</html>
