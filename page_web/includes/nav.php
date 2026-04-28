<?php
// Determine current page for active nav link
$current = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav class="nav">
  <a href="index.php" class="nav-brand">
    <span class="leaf">🌳</span>
    <span>ArboData</span>
  </a>
  <ul class="nav-links">
    <li><a href="index.php" class="<?= $current === 'index' ? 'active' : '' ?>">Accueil</a></li>
    <li><a href="ajouter.php" class="<?= $current === 'ajouter' ? 'active' : '' ?>">Ajouter</a></li>
    <li><a href="visualisation.php" class="<?= $current === 'visualisation' ? 'active' : '' ?>">Visualisation</a></li>
    <li><a href="clusters.php" class="<?= $current === 'clusters' ? 'active' : '' ?>">Clusters</a></li>
  </ul>
</nav>
