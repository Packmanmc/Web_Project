var markerMap = {};
var vizMap = null;

proj4.defs('EPSG:3949', '+proj=lcc +lat_1=48.25 +lat_2=49.75 +lat_0=49 +lon_0=3 +x_0=1700000 +y_0=8200000 +ellps=GRS80 +units=m +no_defs +type=crs');

document.addEventListener('DOMContentLoaded', function () {
  Promise.all([getAllArbres(), getStats()]).then(function (results) {
    var arbres = results[0].data || [];
    var stats  = results[1];

    document.getElementById('stat-total').textContent        = stats.total             ?? 0;
    document.getElementById('stat-remarquables').textContent = stats.remarquables      ?? 0;
    document.getElementById('stat-hauteur').textContent      = (stats.hauteur_moyenne ?? 0) + 'm';
    document.getElementById('stat-especes').textContent      = stats.especes           ?? 0;

    renderTable(arbres);
    initMap(arbres);
  }).catch(function (err) {
    document.getElementById('arbreTableBody').innerHTML =
      '<tr><td colspan="11" class="viz-empty-cell">Erreur de chargement : ' + err.message + '</td></tr>';
  });
});

function badgeClass(etat) {
  var low = (etat || '').toLowerCase();
  if (low.includes('bon')     || low.includes('good'))  return 'badge-green';
  if (low.includes('moyen')   || low.includes('fair'))  return 'badge-yellow';
  if (low.includes('mauvais') || low.includes('bad') || low.includes('mort')) return 'badge-red';
  return 'badge-gray';
}

function fmt(val, unit, decimals) {
  return val != null ? parseFloat(val).toFixed(decimals) + ' ' + unit : '—';
}

function updateCount(n) {
  document.getElementById('rowCount').textContent  = n;
  document.getElementById('rowCountS').textContent = n > 1 ? 's' : '';
}

function renderTable(arbres) {
  var tbody = document.getElementById('arbreTableBody');
  var total = arbres.length;

  document.getElementById('table-title').textContent = total + ' arbre' + (total > 1 ? 's' : '');
  updateCount(total);

  if (total === 0) {
    tbody.innerHTML = '<tr><td colspan="11" class="viz-empty-cell">Aucun arbre. <a href="ajouter.html" class="viz-empty-link">Ajouter le premier →</a></td></tr>';
    return;
  }

  tbody.innerHTML = arbres.map(function (a) {
    var etat        = a.etat || '—';
    var especeNom   = (a.espece_nom       || '').trim();
    var feuillage   = (a.espece_feuillage || '').trim();
    var especeLabel = feuillage ? especeNom + ' (' + feuillage + ')' : (especeNom || '—');
    var cls         = badgeClass(etat);
    var x           = a.X != null ? a.X : '';
    var y           = a.Y != null ? a.Y : '';
    return '<tr class="arbre-row" data-id="' + (a.id_arbre ?? 0) + '" data-x="' + x + '" data-y="' + y + '" title="Cliquez pour voir sur la carte">'
      + '<td class="viz-id-cell">'      + (a.id_arbre ?? 0) + '</td>'
      + '<td class="viz-species-cell">' + especeLabel + '</td>'
      + '<td class="viz-coord-cell">'   + (x || '—') + '</td>'
      + '<td class="viz-coord-cell">'   + (y || '—') + '</td>'
      + '<td>' + fmt(a.haut_tot,   'm',  1) + '</td>'
      + '<td>' + fmt(a.haut_tronc, 'm',  1) + '</td>'
      + '<td>' + fmt(a.diam_tronc, 'cm', 1) + '</td>'
      + '<td>' + (a.stade_dev || a.libelle_stade || '—') + '</td>'
      + '<td><span class="badge ' + cls + '">' + etat + '</span></td>'
      + '<td>' + (a.remarquable ? '<span class="badge badge-green">Oui</span>' : '<span class="badge badge-gray">Non</span>') + '</td>'
      + '<td><button class="btn btn-danger btn-xs" onclick="handleDelete(' + (a.id_arbre ?? 0) + ', event)">Supprimer</button></td>'
      + '</tr>';
  }).join('');

  attachRowClickHandlers();
}

function filterTable() {
  var q = document.getElementById('searchInput').value.toLowerCase();
  var n = 0;
  document.querySelectorAll('#arbreTable tbody tr').forEach(function (r) {
    var ok = r.textContent.toLowerCase().includes(q);
    r.style.display = ok ? '' : 'none';
    if (ok) n++;
  });
  updateCount(n);
}

function toLatLng(x, y) {
  if (Math.abs(x) <= 180 && Math.abs(y) <= 90) return [y, x];
  var wgs84 = proj4('EPSG:3949', 'EPSG:4326', [x, y]);
  return [wgs84[1], wgs84[0]];
}

function initMap(arbres) {
  var SAINT_QUENTIN = [49.8489, 3.2870];
  vizMap = L.map('map').setView(SAINT_QUENTIN, 13);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors', maxZoom: 19
  }).addTo(vizMap);

  var icon = L.divIcon({
    className: '',
    html: '<div class="viz-map-dot"></div>',
    iconSize: [10, 10], iconAnchor: [5, 5]
  });

  var pts = [];
  arbres.forEach(function (a) {
    var x = parseFloat(a.X);
    var y = parseFloat(a.Y);
    if (isNaN(x) || isNaN(y)) return;

    var ll = toLatLng(x, y);
    var lat = ll[0], lng = ll[1];
    if (isNaN(lat) || isNaN(lng)) return;

    var rows = [
      ['Espèce',    a.espece_nom || '—'],
      ['H. totale', a.haut_tot   != null ? a.haut_tot   + ' m'  : '—'],
      ['Ø tronc',   a.diam_tronc != null ? a.diam_tronc + ' cm' : '—'],
      ['État',      a.etat       || '—'],
      ['Stade',     a.stade_dev  || '—'],
    ].map(function (kv) {
      return '<tr><td class="viz-tooltip-key">' + kv[0] + '</td><td>' + kv[1] + '</td></tr>';
    }).join('');

    var marker = L.marker([lat, lng], { icon }).addTo(vizMap).bindTooltip(
      '<div class="viz-tooltip-wrap">'
      + '<div class="viz-tooltip-title">' + (a.espece_nom || 'Arbre #' + (a.id_arbre ?? '')) + '</div>'
      + '<table class="viz-tooltip-table">' + rows + '</table>'
      + '</div>',
      { sticky: true, direction: 'top', offset: [0, -8], opacity: 1 }
    );

    markerMap[a.id_arbre] = { marker: marker, lat: lat, lng: lng };
    pts.push([lat, lng]);
  });

  if (pts.length > 0) vizMap.fitBounds(pts, { padding: [40, 40] });
}

function attachRowClickHandlers() {
  document.querySelectorAll('.arbre-row').forEach(function (row) {
    row.addEventListener('click', function () {
      var x = parseFloat(this.dataset.x);
      var y = parseFloat(this.dataset.y);
      if (isNaN(x) || isNaN(y)) return;

      var ll = toLatLng(x, y);
      if (isNaN(ll[0]) || isNaN(ll[1])) return;

      vizMap.setView(ll, 16);
      var entry = markerMap[parseInt(this.dataset.id)];
      if (entry) entry.marker.openTooltip();
    });
  });
}

function handleDelete(id, event) {
  event.stopPropagation();
  if (!confirm('Êtes-vous sûr de vouloir supprimer cet arbre ?')) return;

  deleteArbre(id).then(function () {
    var row = document.querySelector('tr[data-id="' + id + '"]');
    if (row) row.remove();

    var n = document.querySelectorAll('#arbreTable tbody tr.arbre-row').length;
    updateCount(n);
    document.getElementById('table-title').textContent = n + ' arbre' + (n > 1 ? 's' : '');

    if (markerMap[id]) {
      vizMap.removeLayer(markerMap[id].marker);
      delete markerMap[id];
    }
  }).catch(function (e) { alert('Erreur lors de la suppression : ' + e.message); });
}
