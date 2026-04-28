<?php
require_once 'includes/api.php';
$arbres       = getAllArbres();
$stats        = getStats();
$total        = $stats['total'] ?? 0;
$remarquables = $stats['remarquables'] ?? 0;
$especes_u    = $stats['especes'] ?? 0;
$avg_h        = $stats['hauteur_moyenne'] ?? 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Visualisation</title>
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


   <!-- CARTE -->
  <div>
    <div id="map" class="viz-map"></div>
  </div>

  
  <!-- TABLEAU -->
  <div>
    <div class="card">
      <div class="table-bar">
        <h2><?= $total ?> arbre<?= $total > 1 ? 's' : '' ?></h2>
        <div class="viz-table-actions">
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
              <th>X</th>
              <th>Y</th>
              <th>H. totale</th>
              <th>H. tronc</th>
              <th>Ø tronc</th>
              <th>Stade dev.</th>
              <th>État</th>
              <th>Remarquable</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>

          <!-- Si aucun arbre -->
          <?php if (empty($arbres)): ?>
            <tr><td colspan="11" class="viz-empty-cell">
              Aucun arbre. <a href="ajouter.php" class="viz-empty-link">Ajouter le premier →</a>
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
            <tr class="arbre-row" data-id="<?= (int)($a['id_arbre'] ?? 0) ?>" data-x="<?= isset($a['X']) ? (float)$a['X'] : '0' ?>" data-y="<?= isset($a['Y']) ? (float)$a['Y'] : '0' ?>" title="Cliquez pour voir sur la carte">
              <td class="viz-id-cell"><?= (int)($a['id_arbre'] ?? 0) ?></td>
              <td class="viz-species-cell"><?= htmlspecialchars($especeLabel !== '' ? $especeLabel : '—') ?></td>
              <td class="viz-coord-cell"><?= isset($a['X'])  ? $a['X'] : '—' ?></td>
              <td class="viz-coord-cell"><?= isset($a['Y']) ? $a['Y'] : '—' ?></td>
              <td><?= isset($a['haut_tot']) ? number_format((float)$a['haut_tot'],1).' m' : '—' ?></td>
              <td><?= isset($a['haut_tronc'])  ? number_format((float)$a['haut_tronc'], 1).' m' : '—' ?></td>
              <td><?= isset($a['diam_tronc']) ? number_format((float)$a['diam_tronc'],1).' cm': '—' ?></td>
              <td><?= htmlspecialchars($a['stade_dev'] ?? ($a['libelle_stade'] ?? '—')) ?></td>
              <td><span class="badge <?= $cls ?>"><?= $etat ?></span></td>
              <td><?= !empty($a['remarquable']) ? '<span class="badge badge-green">Oui</span>' : '<span class="badge badge-gray">Non</span>' ?></td>
              <td><button class="btn btn-danger btn-xs" onclick="deleteArbre(<?= (int)($a['id_arbre'] ?? 0) ?>, event)">Supprimer</button></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <div class="viz-row-count">
        <span id="rowCount"><?= $total ?></span> ligne<?= $total > 1 ? 's' : '' ?>
      </div>
    </div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.11.0/proj4.js"></script>
<script>
var arbres = <?= json_encode(array_values($arbres), JSON_UNESCAPED_UNICODE) ?>;
var markerMap = {};

initMap();

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

function initMap() {
  window._mapInit = true;
  proj4.defs('EPSG:3949', '+proj=lcc +lat_1=48.25 +lat_2=49.75 +lat_0=49 +lon_0=3 +x_0=1700000 +y_0=8200000 +ellps=GRS80 +units=m +no_defs +type=crs');

  const SAINT_QUENTIN = [49.8489, 3.2870];
  const map = L.map('map').setView(SAINT_QUENTIN, 13);
  window.vizMap = map;
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors', maxZoom: 19
  }).addTo(map);

  const icon = L.divIcon({
    className: '',
    html: '<div class="viz-map-dot"></div>',
    iconSize: [10,10], iconAnchor: [5,5]
  });

  const pts = [];
  arbres.forEach(a => {
    const x = parseFloat(a.X);
    const y = parseFloat(a.Y);
    if (isNaN(x) || isNaN(y)) return;

    let lat = null;
    let lng = null;

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
    ].map(([k,v]) => `<tr><td class="viz-tooltip-key">${k}</td><td>${v}</td></tr>`).join('');

    const marker = L.marker([lat,lng], {icon}).addTo(map).bindTooltip(
      `<div class="viz-tooltip-wrap">
        <div class="viz-tooltip-title">${a.espece_nom || 'Arbre #'+(a.id_arbre ?? '')}</div>
        <table class="viz-tooltip-table">${rows}</table>
       </div>`,
      { sticky: true, direction: 'top', offset: [0,-8], opacity: 1 }
    );
    
    markerMap[a.id_arbre] = { marker, lat, lng };
    pts.push([lat,lng]);
  });

  if (pts.length > 0) {
    map.fitBounds(pts, {padding:[40,40]});
  } else {
    map.setView(SAINT_QUENTIN, 13);
  }
  
  attachRowClickHandlers();
}

function attachRowClickHandlers() {
  document.querySelectorAll('.arbre-row').forEach(row => {
    row.addEventListener('click', function() {
      const id = parseInt(this.dataset.id);
      const x = parseFloat(this.dataset.x);
      const y = parseFloat(this.dataset.y);
      
      if (isNaN(x) || isNaN(y)) return;
      
      let lat = null, lng = null;
      if (Math.abs(x) <= 180 && Math.abs(y) <= 90) {
        lng = x; lat = y;
      } else {
        const wgs84 = proj4('EPSG:3949', 'EPSG:4326', [x, y]);
        lng = wgs84[0]; lat = wgs84[1];
      }
      
      if (!isNaN(lat) && !isNaN(lng)) {
        window.vizMap.setView([lat, lng], 16);
        if (markerMap[id]) {
          markerMap[id].marker.openTooltip();
        }
      }
    });
  });
}

if (window.location.hash === '#carte') document.getElementById('btn-carte').click();

const API_URL = "<?= rtrim(getenv('API_URL') ?: 'http://api', '/') ?>";
function deleteArbre(id, event) {
  event.stopPropagation();
  if (!confirm('Êtes-vous sûr de vouloir supprimer cet arbre ?')) {
    return;
  }
  
  fetch(API_URL + '/arbres/' + id, { method: 'DELETE' })
    .then(r => r.json())
    .then(d => {
      if (d.error) {
        alert('Erreur: ' + d.error);
      } else {
        document.querySelector('tr[data-id="' + id + '"]').remove();
        const total = parseInt(document.getElementById('rowCount').textContent) - 1;
        document.getElementById('rowCount').textContent = total;
        const h2 = document.querySelector('.table-bar h2');
        if (h2) h2.textContent = total + ' arbre' + (total > 1 ? 's' : '');
      }
    })
    .catch(e => alert('Erreur lors de la suppression: ' + e));
}
</script>
</body>
</html>
