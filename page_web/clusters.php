<?php
require_once 'includes/api.php';

$arbres    = getAllArbres();
$total     = count($arbres);
$predicted = false;
$clusters  = [];
$error     = null;

if (isset($_POST['predict'])) {
    $result = predictClusters($arbres);
    if (!empty($result)) {
        $predicted = true;
        $clusters  = $result;
    } else {
        $error = "La prédiction a échoué ou n'a retourné aucun résultat.";
    }
}

$palette = ['#3b82f6','#ef4444','#f59e0b','#8b5cf6','#10b981','#f97316','#06b6d4','#84cc16'];

$clusterCounts = [];
foreach ($clusters as $a) {
    $c = $a['cluster'] ?? 0;
    $clusterCounts[$c] = ($clusterCounts[$c] ?? 0) + 1;
}
ksort($clusterCounts);
$uniqueClusters = array_keys($clusterCounts);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Clusters — ArboData</title>
  <link rel="stylesheet" href="assets/style.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
</head>
<body>

<?php include 'includes/nav.php'; ?>

<div class="page-header">
  <h1>Clusters</h1>
  <p>Prédiction des groupes d'arbres par machine learning.</p>
</div>

<div class="container">

  <?php if ($error): ?>
    <div class="alert alert-err">✕ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (!$predicted): ?>

  <!-- État initial -->
  <div class="card" style="padding:2rem; max-width:520px;">
    <p style="font-weight:600; margin-bottom:.4rem;"><?= $total ?> arbre<?= $total>1?'s':'' ?> dans la base</p>
    <p style="color:var(--muted); font-size:.9rem; margin-bottom:1.5rem; line-height:1.6;">
      L'algorithme analysera l'ensemble des arbres et les regroupera selon leurs caractéristiques morphologiques.
    </p>
    <form method="POST" action="clusters.php">
      <button type="submit" name="predict" value="1" class="btn btn-primary btn-lg" id="predictBtn">
        Prédire les clusters
      </button>
    </form>
  </div>

  <?php else: ?>

  <!-- Résultats -->
  <div class="stats-row">
    <div class="stat"><div class="val"><?= count($clusters) ?></div><div class="lbl">Arbres analysés</div></div>
    <div class="stat"><div class="val"><?= count($uniqueClusters) ?></div><div class="lbl">Clusters</div></div>
    <?php foreach ($clusterCounts as $c => $n): ?>
    <div class="stat">
      <div class="val" style="color:<?= $palette[$c % count($palette)] ?>;"><?= $n ?></div>
      <div class="lbl">Cluster <?= $c ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Légende -->
  <div class="legend">
    <span style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-right:.25rem;">Clusters :</span>
    <?php foreach ($uniqueClusters as $c): ?>
    <div class="legend-item">
      <div class="legend-dot" style="background:<?= $palette[$c % count($palette)] ?>;"></div>
      <span>Cluster <?= $c ?> — <?= $clusterCounts[$c] ?> arbre<?= $clusterCounts[$c]>1?'s':'' ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Carte -->
  <div id="map-clusters" style="height:500px; margin-bottom:1.5rem;"></div>

  <!-- Tableau -->
  <div class="card">
    <div class="table-bar">
      <h2>Détail par cluster</h2>
      <form method="POST" style="margin:0;">
        <button type="submit" name="predict" value="1" class="btn btn-ghost btn-sm">↺ Recalculer</button>
      </form>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th><th>Cluster</th><th>Espèce</th>
            <th>H. totale</th><th>H. tronc</th><th>Ø tronc</th>
            <th>État</th><th>Stade dev.</th><th>Remarquable</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($clusters as $a):
            $c   = $a['cluster'] ?? 0;
            $col = $palette[$c % count($palette)];
          ?>
          <tr>
            <td style="color:var(--muted);"><?= (int)($a['id']??0) ?></td>
            <td>
              <span style="display:inline-flex;align-items:center;gap:.3rem;padding:.18rem .55rem;border-radius:20px;font-size:.75rem;font-weight:600;background:<?= $col ?>18;color:<?= $col ?>;border:1px solid <?= $col ?>40;">
                <span style="width:7px;height:7px;border-radius:50%;background:<?= $col ?>;"></span>
                <?= $c ?>
              </span>
            </td>
            <td style="font-style:italic;"><?= htmlspecialchars($a['espece']??'—') ?></td>
            <td><?= isset($a['hauteur_totale']) ? number_format((float)$a['hauteur_totale'],1).' m' : '—' ?></td>
            <td><?= isset($a['hauteur_tronc'])  ? number_format((float)$a['hauteur_tronc'], 1).' m' : '—' ?></td>
            <td><?= isset($a['diametre_tronc']) ? number_format((float)$a['diametre_tronc'],1).' cm': '—' ?></td>
            <td><?= htmlspecialchars($a['etat']??($a['libelle_etat']??'—')) ?></td>
            <td><?= htmlspecialchars($a['stade_dev']??($a['libelle_stade']??'—')) ?></td>
            <td><?= !empty($a['remarquable']) ? '<span class="badge badge-green">Oui</span>' : '<span class="badge badge-gray">Non</span>' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php endif; ?>

</div>

<footer>© <?= date('Y') ?> ArboData</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.getElementById('predictBtn')?.addEventListener('click', function() {
  this.textContent = 'Analyse en cours…';
  this.disabled = true;
});

<?php if ($predicted): ?>
var clusters = <?= json_encode(array_values($clusters), JSON_UNESCAPED_UNICODE) ?>;
var palette  = <?= json_encode($palette) ?>;

(function() {
  const map = L.map('map-clusters');
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors', maxZoom: 19
  }).addTo(map);

  const pts = [];
  clusters.forEach(a => {
    const lat = parseFloat(a.latitude), lng = parseFloat(a.longitude);
    if (isNaN(lat) || isNaN(lng)) return;

    const c   = a.cluster ?? 0;
    const col = palette[c % palette.length];

    const icon = L.divIcon({
      className: '',
      html: `<div style="width:12px;height:12px;background:${col};border:2px solid white;border-radius:50%;box-shadow:0 1px 3px rgba(0,0,0,.3);"></div>`,
      iconSize: [12,12], iconAnchor: [6,6]
    });

    const rows = [
      ['Cluster', c],
      ['Espèce',  a.espece || '—'],
      ['H. tot.', a.hauteur_totale != null ? a.hauteur_totale+' m' : '—'],
      ['État',    a.etat || a.libelle_etat || '—'],
    ].map(([k,v]) => `<tr><td style="padding:.15rem .5rem .15rem 0;color:#6b7280;font-size:.78rem;">${k}</td><td>${v}</td></tr>`).join('');

    L.marker([lat,lng],{icon}).addTo(map).bindTooltip(
      `<div style="font-family:'Inter',sans-serif;font-size:.82rem;">
        <div style="font-weight:600;margin-bottom:.4rem;font-style:italic;">${a.espece||'Arbre #'+a.id}</div>
        <table style="border-collapse:collapse">${rows}</table>
       </div>`,
      { sticky:true, direction:'top', offset:[0,-8], opacity:1 }
    );
    pts.push([lat,lng]);
  });

  pts.length ? map.fitBounds(pts,{padding:[40,40]}) : map.setView([46.8,2.4],6);
})();
<?php endif; ?>
</script>
</body>
</html>
