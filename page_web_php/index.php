<?php
require_once 'includes/api.php';
$stats = getStats();
$total = $stats['total'] ?? 0;
$remarquables = $stats['remarquables'] ?? 0;
$especes = $stats['especes'] ?? 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Arbres - St Quentin</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include 'includes/nav.php'; ?>

<div class="page-header">
  <h1>Arbres - St Quentin</h1>
  <p>Gestion du patrimoine</p>
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
  <div class="card home-about-card">
    <h2 class="home-about-title">À propos du projet</h2>
    <p class="home-about-text">
      Cette application centralise les données des arbres de la ville de St Quentin.
      Inventoriez chaque arbre, suivez leur état, visualisez leur répartition géographique et analysez 
      les groupes d'arbres similaires par clustering automatique.
    </p>
  </div>

  <!-- Navigation cards -->
  <div class="home-nav-grid">

    <a href="ajouter.php" class="card home-nav-card">
      <div class="home-nav-icon">＋</div>
      <div class="home-nav-title">Ajouter un arbre</div>
      <div class="home-nav-desc">Enregistrez un nouvel arbre avec ses caractéristiques.</div>
    </a>

    <a href="visualisation.php" class="card home-nav-card">
      <div class="home-nav-icon">⊞</div>
      <div class="home-nav-title">Visualisation</div>
      <div class="home-nav-desc">Tableau complet et carte interactive de tous les arbres.</div>
    </a>

    <a href="clusters.php" class="card home-nav-card">
      <div class="home-nav-icon">◎</div>
      <div class="home-nav-title">Clusters</div>
      <div class="home-nav-desc">Prédisez les clusters d'arbres par machine learning.</div>
    </a>

  </div>

</div>


</body>
</html>
