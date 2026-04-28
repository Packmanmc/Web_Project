<?php
require_once 'includes/api.php';
$stats = getStats();
echo '<script>console.log(' . json_encode($stats) . ');</script>';
$total = $stats['total'] ?? 0;
$remarquables = $stats['remarquables'] ?? 0;
$especes = $stats['especes'] ?? 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ArboData</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include 'includes/nav.php'; ?>

<div class="page-header">
  <h1>ArboData</h1>
  <p>Gestion du patrimoine arboré urbain</p>
</div>

<div class="container">

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat">
      <div class="val"><?= $total ?></div>
      <div class="lbl">Arbres</div>
    </div>
    <div class="stat">
      <div class="val"><?= $remarquables ?></div>
      <div class="lbl">Remarquables</div>
    </div>
    <div class="stat">
      <div class="val"><?= $especes ?></div>
      <div class="lbl">Espèces</div>
    </div>
  </div>

  <!-- Description -->
  <div class="card" style="padding:2rem; margin-bottom:1.5rem;">
    <h2 style="font-family:'Lora',serif; font-size:1.2rem; margin-bottom:.75rem;">À propos du projet</h2>
    <p style="color:var(--muted); max-width:640px; line-height:1.75;">
      ArboData centralise les données du parc arboré urbain. Inventoriez chaque arbre, 
      suivez leur état sanitaire, visualisez leur répartition géographique et analysez 
      les groupes d'arbres similaires par clustering automatique.
    </p>
  </div>

  <!-- Navigation cards -->
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1rem;">

    <a href="ajouter.php" class="card" style="padding:1.5rem; text-decoration:none; display:block; transition:border-color .15s;">
      <div style="font-size:1.4rem; margin-bottom:.75rem;">＋</div>
      <div style="font-weight:600; margin-bottom:.35rem;">Ajouter un arbre</div>
      <div style="font-size:.85rem; color:var(--muted);">Enregistrez un nouvel arbre avec ses caractéristiques.</div>
    </a>

    <a href="visualisation.php" class="card" style="padding:1.5rem; text-decoration:none; display:block; transition:border-color .15s;">
      <div style="font-size:1.4rem; margin-bottom:.75rem;">⊞</div>
      <div style="font-weight:600; margin-bottom:.35rem;">Visualisation</div>
      <div style="font-size:.85rem; color:var(--muted);">Tableau complet et carte interactive de tous les arbres.</div>
    </a>

    <a href="clusters.php" class="card" style="padding:1.5rem; text-decoration:none; display:block; transition:border-color .15s;">
      <div style="font-size:1.4rem; margin-bottom:.75rem;">◎</div>
      <div style="font-weight:600; margin-bottom:.35rem;">Clusters</div>
      <div style="font-size:.85rem; color:var(--muted);">Prédisez les clusters d'arbres par machine learning.</div>
    </a>

  </div>

</div>


</body>
</html>
