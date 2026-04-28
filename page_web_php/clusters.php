<?php
require_once 'includes/client_1.php';
require_once 'includes/api.php';


$arbres    = getAllArbres();
$total     = count($arbres);
$predicted = false;
$clusters  = [];
$error     = null;
$prediction_result = null;
$batch_k = 3;
$manual_haut_tot = '';
$manual_diam_tronc = '';
$manual_k = 3;
$existing_tree_id = 0;
$existing_k = 3;

// Traiter la prédiction personnalisée manuelle
if (isset($_POST['predict_manual'])) {
  $manual_haut_tot = $_POST['haut_tot'] ?? '';
  $manual_diam_tronc = $_POST['diam_tronc'] ?? '';
  $manual_k = (int) ($_POST['k'] ?? 3);

  $haut_tot = (float) $manual_haut_tot;
  $diam_tronc = (float) $manual_diam_tronc;

  if ($haut_tot > 0 && $diam_tronc > 0) {
    $prediction_result = predictTreeSize($haut_tot, $diam_tronc, $manual_k);
  } else {
    $error = "La hauteur totale et le diamètre du tronc doivent être positifs.";
  }
}

// Traiter la prédiction personnalisée à partir d'un arbre existant
if (isset($_POST['predict_existing'])) {
  $existing_tree_id = (int) ($_POST['existing_tree_id'] ?? 0);
  $existing_k = (int) ($_POST['existing_k'] ?? 3);

  $selected_tree = null;
  foreach ($arbres as $arbre) {
    if ((int) ($arbre['id_arbre'] ?? 0) === $existing_tree_id) {
      $selected_tree = $arbre;
      break;
    }
  }

  if (!$selected_tree) {
    $error = "Veuillez sélectionner un arbre existant.";
  } else {
    $haut_tot = (float) ($selected_tree['haut_tot'] ?? 0);
    $diam_tronc = (float) ($selected_tree['diam_tronc'] ?? 0);

    if ($haut_tot > 0 && $diam_tronc > 0) {
      $prediction_result = predictTreeSize($haut_tot, $diam_tronc, $existing_k);
      if ($prediction_result && ($prediction_result['status'] ?? null) === 'success') {
        $prediction_result['arbre_source'] = $selected_tree;
      }
    } else {
      $error = "L'arbre sélectionné n'a pas de dimensions exploitables.";
    }
  }
}

// Traiter la prédiction globale
if (isset($_POST['predict'])) {
  $batch_k = (int) ($_POST['batch_k'] ?? 3);
  if ($batch_k !== 2 && $batch_k !== 3) {
    $batch_k = 3;
  }

  $result = predictClusters($arbres, $batch_k);
    if (!empty($result)) {
        $predicted = true;
        $clusters  = $result;
    } else {
        $error = "La prédiction a échoué ou n'a retourné aucun résultat.";
    }
}

$palette = ['#3b82f6','#ef4444','#f59e0b','#8b5cf6','#10b981','#f97316','#06b6d4','#84cc16'];
$clusterLabels = [
  0 => 'Petit',
  1 => 'Moyen',
  2 => 'Grand',
];

$clusterCounts = [];
$clusterColors = [];
foreach ($clusters as $a) {
  $label = $a['cluster_label'] ?? ($clusterLabels[(int) ($a['cluster'] ?? 0)] ?? 'Cluster');
  if (!isset($clusterColors[$label])) {
    $clusterColors[$label] = $palette[count($clusterColors) % count($palette)];
  }
  $clusterCounts[$label] = ($clusterCounts[$label] ?? 0) + 1;
}
$uniqueClusters = array_keys($clusterCounts);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Clusters</title>
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

  <div class="card card-primary">
    <h2 class="section-title">Prédiction personnalisée</h2>
    <p class="card-description">Choisissez soit une saisie manuelle, soit un arbre déjà présent dans la base.</p>

  <!-------------------------------- Section: saisie manuelle -------------------------------------->
    <div class="card card-nested">
      <h3 class="section-title">Saisie manuelle</h3>
      <form method="POST" action="clusters.php" class="form-no-margin">
        <div class="form-row">
          <div class="form-group">
            <label for="haut_tot">Hauteur totale (m)</label>
            <input type="number" id="haut_tot" name="haut_tot" step="0.1" min="0.1" required placeholder="15.5" value="<?= htmlspecialchars((string) $manual_haut_tot) ?>">
          </div>
          <div class="form-group">
            <label for="diam_tronc">Diamètre du tronc (cm)</label>
            <input type="number" id="diam_tronc" name="diam_tronc" step="0.1" min="0.1" required placeholder="85.5" value="<?= htmlspecialchars((string) $manual_diam_tronc) ?>">
          </div>
          <div class="form-group">
            <label for="k">Nombre de clusters</label>
            <select id="k" name="k" required>
              <option value="2" <?= $manual_k === 2 ? 'selected' : '' ?>>Petit vs Grand</option>
              <option value="3" <?= $manual_k === 3 ? 'selected' : '' ?>>Petit vs Moyen vs Grand</option>
            </select>
          </div>
        </div>
        <button type="submit" name="predict_manual" value="1" class="btn btn-primary">Prédire la saisie</button>
      </form>
    </div>

    <!-------------------------------- Section: Prédiction arbre existant -------------------------------------->
    <div class="card card-nested">
      <h3 class="section-title">Arbre existant</h3>
      <form method="POST" action="clusters.php" class="form-no-margin">
        <div class="form-row">
          <div class="form-group form-group-full">
            <label for="existing_tree_id">Choisir un arbre</label>
            <select id="existing_tree_id" name="existing_tree_id" required>
              <option value="">-- Sélectionner un arbre --</option>
              <?php foreach ($arbres as $arbre): ?>
                <?php
                  $treeId = (int) ($arbre['id_arbre'] ?? 0);
                  $treeLabel = 'ID ' . $treeId . ' - ' . ($arbre['espece_nom'] ?? 'Arbre');
                  $treeLabel .= ' | H: ' . number_format((float) ($arbre['haut_tot'] ?? 0), 1) . ' m';
                  $treeLabel .= ' | Ø: ' . number_format((float) ($arbre['diam_tronc'] ?? 0), 1) . ' cm';
                ?>
                <option value="<?= $treeId ?>" <?= $existing_tree_id === $treeId ? 'selected' : '' ?>><?= htmlspecialchars($treeLabel) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="existing_k">Nombre de clusters</label>
            <select id="existing_k" name="existing_k" required>
              <option value="2" <?= $existing_k === 2 ? 'selected' : '' ?>>Petit vs Grand</option>
              <option value="3" <?= $existing_k === 3 ? 'selected' : '' ?>>Petit vs Moyen vs Grand</option>
            </select>
          </div>
        </div>
        <button type="submit" name="predict_existing" value="1" class="btn btn-primary">Prédire l'arbre sélectionné</button>
      </form>
    </div>

    <?php if ($prediction_result): ?>
      <?php if (($prediction_result['status'] ?? null) === 'success'): ?>
        <div class="prediction-box">
          <h3>✓ Résultat de la prédiction</h3>
          <?php if (!empty($prediction_result['arbre_source']['id_arbre'])): ?>
            <div class="result-item">
              <span class="result-label">Arbre source:</span>
              <span class="result-value">ID <?= (int) $prediction_result['arbre_source']['id_arbre'] ?></span>
            </div>
          <?php endif; ?>
          <div class="result-item">
            <span class="result-label">Catégorie:</span>
            <span class="result-value result-success"><?= htmlspecialchars($prediction_result['categorie']) ?></span>
          </div>
          <div class="result-item">
            <span class="result-label">Hauteur:</span>
            <span class="result-value"><?= number_format($prediction_result['hauteur_tot'], 1) ?> m</span>
          </div>
          <div class="result-item">
            <span class="result-label">Diamètre:</span>
            <span class="result-value"><?= number_format($prediction_result['tronc_diam'], 1) ?> cm</span>
          </div>
        </div>
      <?php else: ?>
        <div class="alert alert-err">✕ <?= htmlspecialchars($prediction_result['error'] ?? 'Erreur inconnue') ?></div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- Séparateur -->
  <hr class="divider-spaced">

  <!-------------------------------- Section: Prédiction globale -------------------------------------->
  <div class="card card-primary">
    <h2 class="section-title">Analyse de tous les arbres</h2>
    
    <?php if (!$predicted): ?>
    <form method="POST" action="clusters.php">
      <div class="form-group form-group-sm">
        <label for="batch_k">Nombre de clusters</label>
        <select id="batch_k" name="batch_k" required>
          <option value="2" <?= $batch_k === 2 ? 'selected' : '' ?>>2 - Petit vs Grand</option>
          <option value="3" <?= $batch_k === 3 ? 'selected' : '' ?>>3 - Petit vs Moyen vs Grand</option>
        </select>
      </div>
      <button type="submit" name="predict" value="1" class="btn btn-primary btn-lg">
        Analyser tous les arbres
      </button>
    </form>

    <?php else: ?>

    <!-- Résultats -->
    <div class="stats-row">
      <div class="stat"><div class="val"><?= count($clusters) ?></div><div class="lbl">Arbres analysés</div></div>
      <div class="stat"><div class="val"><?= count($uniqueClusters) ?></div><div class="lbl">Clusters</div></div>
      <?php foreach ($clusterCounts as $label => $n): ?>
      <div class="stat">
        <div class="val" style="--color: <?= $clusterColors[$label] ?>;"><?= $n ?></div>
        <div class="lbl"><?= htmlspecialchars($label) ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Légende -->
    <div class="legend">
      <span class="legend-label">Clusters :</span>
      <?php foreach ($clusterCounts as $label => $n): ?>
      <div class="legend-item">
        <div class="legend-dot" style="--bg: <?= $clusterColors[$label] ?>;"></div>
        <span><?= htmlspecialchars($label) ?> — <?= $n ?> arbre<?= $n>1?'s':'' ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Carte -->
    <div id="map-clusters" class="map-container"></div>

    <!-- Tableau -->
    <div class="card">
      <div class="table-bar">
        <h2>Détail par cluster</h2>
        <form method="POST" class="form-reset-margin">
          <input type="hidden" name="batch_k" value="<?= (int) $batch_k ?>">
          <button type="submit" name="predict" value="1" class="btn btn-ghost btn-sm">↺ Recalculer</button>
        </form>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Catégorie</th>
              <th>Espèce</th>
              <th>H. totale</th>
              <th>H. tronc</th>
              <th>Ø tronc</th>
              <th>État</th>
              <th>Stade dev.</th>
              <th>Remarquable</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($clusters as $a):
              $c   = $a['cluster'] ?? 0;
              $col = $palette[$c % count($palette)];
              $label = $a['cluster_label'] ?? ($clusterLabels[(int) $c] ?? ('Cluster ' . $c));
            ?>
            <tr>
              <td class="table-id-cell"><?= (int)($a['id_arbre']??0) ?></td>
              <td>
                <span class="table-cluster-badge" style="--bg: <?= $col ?>;--txt: <?= $col ?>;">
                  <span class="table-badge-dot" style="--dot-bg: <?= $col ?>;"></span>
                  <?= htmlspecialchars($label) ?>
                </span>
              </td>
              <td class="table-species-cell"><?= htmlspecialchars($a['espece_nom']??'—') ?></td>
              <td><?= isset($a['haut_tot']) ? number_format((float)$a['haut_tot'],1).' m' : '—' ?></td>
              <td><?= isset($a['haut_tronc'])  ? number_format((float)$a['haut_tronc'], 1).' m' : '—' ?></td>
              <td><?= isset($a['diam_tronc']) ? number_format((float)$a['diam_tronc'],1).' cm': '—' ?></td>
              <td><?= htmlspecialchars($a['etat']??'—') ?></td>
              <td><?= htmlspecialchars($a['stade_dev']??'—') ?></td>
              <td><?= !empty($a['remarquable']) ? '<span class="badge badge-green">Oui</span>' : '<span class="badge badge-gray">Non</span>' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php endif; ?>
  </div>

</div>

<script src="https://unpkg.com/proj4@2.10.0/dist/proj4.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.getElementById('predictBtn')?.addEventListener('click', function() {
  this.textContent = 'Analyse en cours…';
  this.disabled = true;
});

<?php if ($predicted): ?>
(function() {
  const palette = <?= json_encode($palette) ?>;
  const clusters = <?= json_encode(array_values($clusters), JSON_UNESCAPED_UNICODE) ?>;
  const saintQuentin = [49.8489, 3.2874];
  const labels = <?= json_encode($clusterLabels, JSON_UNESCAPED_UNICODE) ?>;

  proj4.defs('EPSG:3949', '+proj=lcc +lat_0=49 +lon_0=3 +lat_1=48.25 +lat_2=49.75 +x_0=1700000 +y_0=8200000 +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs +type=crs');

  const map = L.map('map-clusters');
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors', maxZoom: 19
  }).addTo(map);

  map.setView(saintQuentin, 13);
  const pts = [];
  clusters.forEach(a => {
    const x = parseFloat(a.X);
    const y = parseFloat(a.Y);
    if (isNaN(x) || isNaN(y)) return;

    const [lng, lat] = proj4('EPSG:3949', 'EPSG:4326', [x, y]);
    if (isNaN(lat) || isNaN(lng)) return;

    const c   = a.cluster ?? 0;
    const label = a.cluster_label || labels[c] || ('Cluster ' + c);
    const col = palette[c % palette.length];

    const icon = L.divIcon({
      className: '',
      html: `<div class="map-marker-icon" style="--marker-bg: ${col};"></div>`,
      iconSize: [12,12], iconAnchor: [6,6]
    });

    const rows = [
      ['Catégorie', label],
      ['Espèce',  a.espece_nom || '—'],
      ['H. tot.', a.haut_tot != null ? a.haut_tot+' m' : '—'],
      ['État',    a.etat || '—'],
    ].map(([k,v]) => `<tr><td class="map-tooltip-cell">${k}</td><td>${v}</td></tr>`).join('');

    L.marker([lat,lng],{icon}).addTo(map).bindTooltip(
      `<div class="map-tooltip">
        <div class="map-tooltip-title">${a.espece_nom||'Arbre #'+a.id_arbre}</div>
        <table class="map-tooltip-table">${rows}</table>
       </div>`,
      { sticky:true, direction:'top', offset:[0,-8], opacity:1 }
    );
    pts.push([lat,lng]);
  });

  pts.length ? map.fitBounds(pts,{padding:[40,40]}) : map.setView(saintQuentin, 13);
})();
<?php endif; ?>
</script>
</body>
</html>

