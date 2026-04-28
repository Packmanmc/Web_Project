<?php
require_once 'includes/api.php';

$success = null;
$error   = null;

$especes    = getAllEspeces();
$quartiers  = getAllQuartiers();
$stades     = getAllStadeDev();
$situations = getAllSituations();
$etats      = getAllEtat();
#$portes     = getAllPortes();
#$pieds      = getAllPieds();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'X'      => $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null,
        'Y'       => $_POST['latitude']  !== '' ? (float)$_POST['latitude']  : null,
        'haut_tot' => $_POST['hauteur_totale'] !== '' ? (float)$_POST['hauteur_totale'] : null,
        'haut_tronc'  => $_POST['hauteur_tronc']  !== '' ? (float)$_POST['hauteur_tronc']  : null,
        'diam_tronc' => $_POST['diametre_tronc'] !== '' ? (float)$_POST['diametre_tronc'] : null,
        'age_estime' => ($_POST['age_estime'] ?? '') !== '' ? (int)$_POST['age_estime'] : null,
        'remarquable'    => isset($_POST['remarquable']),
        'id_stade_dev'   => (int)($_POST['id_stade_dev']  ?? 0) ?: null,
        'id_especes'      => (int)($_POST['id_espece'] ?? 0) ?: null,
        'id_etat'        => (int)($_POST['id_etat']       ?? 0) ?: null,
        'id_quartiers'    => (int)($_POST['id_quartier']   ?? 0) ?: null,
        'id_situations'   => (int)($_POST['id_situation']  ?? 0) ?: null,
    ];
    $payload = array_filter($payload, fn($v) => $v !== null);
    $result  = createArbre($payload);
    if ($result) {
        $success = "Arbre ajouté avec succès !";
    } else {
        $error = "Erreur lors de l'ajout. Vérifiez les données et réessayez.";
    }
}

function opt(array $list, string $key, $current): string {
    $html = '<option value="">—</option>';
    foreach ($list as $item) {
        $id  = (int)$item['id'];
    $lbl = htmlspecialchars($item['libelle'] ?? $item['nom'] ?? $item['quartier'] ?? $item['name'] ?? '');
        $sel = ((int)$current === $id) ? ' selected' : '';
        $html .= "<option value=\"$id\"$sel>$lbl</option>";
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ajouter un arbre</title>
  <link rel="stylesheet" href="assets/style.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
</head>
<body>

<?php include 'includes/nav.php'; ?>

<div class="page-header">
  <h1>Ajouter un arbre</h1>
  <p>Renseignez les informations de l'arbre à enregistrer.</p>
</div>

<div class="container">

  <?php if ($success): ?>
    <div class="alert alert-ok">✓ <?= htmlspecialchars($success) ?> — <a href="visualisation.php" class="alert-link">Voir tous les arbres</a></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-err">✕ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card form-wrap">
    <form method="POST" action="ajouter.php" novalidate>

      <p class="form-section">Identification</p>
      <div class="form-body">
        <div class="form-grid">

          <!-- Espèce -->
          <div class="field">
            <label>Espèce *</label>
            <?php if (!empty($especes)): ?>
              <select name="id_espece" required>
                <?= opt($especes, 'id', $_POST['id_espece'] ?? 0) ?>
              </select>
            <?php else: ?>
              <input type="text" name="espece" placeholder="ex : Quercus robur"
                value="<?= htmlspecialchars($_POST['espece'] ?? '') ?>" required>
            <?php endif; ?>
          </div>

          <!-- Quartier -->
          <div class="field">
            <label>Quartier</label>
            <?php if (!empty($quartiers)): ?>
              <select name="id_quartier">
                <?= opt($quartiers, 'id', $_POST['id_quartier'] ?? 0) ?>
              </select>
            <?php else: ?>
              <input type="text" name="quartier" placeholder="Nom du quartier"
                value="<?= htmlspecialchars($_POST['quartier'] ?? '') ?>">
            <?php endif; ?>
          </div>
        </div>
      </div>


      <p class="form-section">Dimensions</p>
      <div class="form-body">
        <div class="form-grid g3">

         <!-- Hauteur totale, hauteur tronc, diamètre tronc -->
          <div class="field">
            <label>Hauteur totale (m)</label>
            <input type="number" name="hauteur_totale" step="0.1" min="0" placeholder="ex : 12.5"
              value="<?= htmlspecialchars($_POST['hauteur_totale'] ?? '') ?>">
          </div>
          <div class="field">
            <label>Hauteur tronc (m)</label>
            <input type="number" name="hauteur_tronc" step="0.1" min="0" placeholder="ex : 3.2"
              value="<?= htmlspecialchars($_POST['hauteur_tronc'] ?? '') ?>">
          </div>
          <div class="field">
            <label>Diamètre tronc (cm)</label>
            <input type="number" name="diametre_tronc" step="0.1" min="0" placeholder="ex : 45"
              value="<?= htmlspecialchars($_POST['diametre_tronc'] ?? '') ?>">
          </div>
        </div>
      </div>

      <p class="form-section">État & classification</p>
      <div class="form-body">
        <div class="form-grid">

           <!-- Age estimée -->
          <div class="field">
            <label>Age estimée</label>
            <input type="number" name="age_estime" step="1" min="0" placeholder="ex : 80"
              value="<?= htmlspecialchars($_POST['age_estime'] ?? '') ?>">
          </div>

          <!-- Stade de développement -->
          <div class="field">
            <label>Stade de développement</label>
            <?php if (!empty($stades)): ?>
              <select name="id_stade_dev"><?= opt($stades, 'id', $_POST['id_stade_dev'] ?? 0) ?></select>
            <?php else: ?>
              <input type="text" name="stade_dev" placeholder="ex : Adulte" value="<?= htmlspecialchars($_POST['stade_dev'] ?? '') ?>">
            <?php endif; ?>
          </div>

          <!-- Situation -->
          <div class="field">
            <label>Situation</label>
            <?php if (!empty($situations)): ?>
              <select name="id_situation"><?= opt($situations, 'id', $_POST['id_situation'] ?? 0) ?></select>
            <?php else: ?>
              <input type="text" name="situation" placeholder="ex : Alignement" value="<?= htmlspecialchars($_POST['situation'] ?? '') ?>">
            <?php endif; ?>
          </div>

          <!-- Etat  -->
          <div class="field">
            <label>État</label>
            <?php if (!empty($etats)): ?>
              <select name="id_etat"><?= opt($etats, 'id', $_POST['id_etat'] ?? 0) ?></select>
            <?php else: ?>
              <input type="text" name="etat" placeholder="ex : Bon" value="<?= htmlspecialchars($_POST['etat'] ?? '') ?>">
            <?php endif; ?>
          </div>

          <!-- Remarquable -->
          <div class="field">
            <label>&nbsp;</label>
            <label class="checkbox-row">
              <input type="checkbox" name="remarquable" value="1"
                <?= !empty($_POST['remarquable']) ? 'checked' : '' ?>>
              <span>Arbre remarquable</span>
            </label>
          </div>

       
        </div>
      </div>

      <!-- Localisation GPS -->
      <p class="form-section">Localisation GPS</p>
      <div class="form-body">
        <div class="form-grid form-grid-margin">
          <div class="field">
            <label>Y</label>
            <input type="number" name="latitude" id="lat" step="0.01"
              placeholder="ex : 9197856.12"
              value="<?= htmlspecialchars($_POST['latitude'] ?? '') ?>">
          </div>
          <div class="field">
            <label>X</label>
            <input type="number" name="longitude" id="lng" step="0.01"
              placeholder="ex : 1702456.44"
              value="<?= htmlspecialchars($_POST['longitude'] ?? '') ?>">
          </div>
        </div>
        <div id="map-pick" class="map-picker"></div>
      </div>

      <div class="form-footer">
        <a href="index.php" class="btn btn-ghost">Annuler</a>
        <button type="reset" class="btn btn-ghost">Réinitialiser</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
      </div>

    </form>
  </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.11.0/proj4.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function() {
  proj4.defs('EPSG:3949', '+proj=lcc +lat_1=48.25 +lat_2=49.75 +lat_0=49 +lon_0=3 +x_0=1700000 +y_0=8200000 +ellps=GRS80 +units=m +no_defs +type=crs');

  const latEl = document.getElementById('lat');
  const lngEl = document.getElementById('lng');
  const SAINT_QUENTIN = [49.8489, 3.2870];
  const map   = L.map('map-pick').setView(SAINT_QUENTIN, 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map);

  let marker = null;

  function toEpsg3949(ll) {
    const projected = proj4('EPSG:4326', 'EPSG:3949', [ll.lng, ll.lat]);
    return { x: projected[0], y: projected[1] };
  }

  function place(ll) {
    if (marker) map.removeLayer(marker);
    marker = L.marker(ll).addTo(map);

    const projected = toEpsg3949(ll);
    latEl.value = projected.y.toFixed(2);
    lngEl.value = projected.x.toFixed(2);
  }

  map.on('click', e => place(e.latlng));

  const iY = parseFloat(latEl.value), iX = parseFloat(lngEl.value);
  if (!isNaN(iY) && !isNaN(iX)) {
    const geo = proj4('EPSG:3949', 'EPSG:4326', [iX, iY]);
    const ll = L.latLng(geo[1], geo[0]);
    map.setView(ll, 14);
    place(ll);
  }
})();
</script>
</body>
</html>
