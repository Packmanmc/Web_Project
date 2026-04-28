document.addEventListener('DOMContentLoaded', function () {

  const PALETTE       = ['#3b82f6','#ef4444','#f59e0b','#8b5cf6','#10b981','#f97316','#06b6d4','#84cc16'];
  const CLUSTER_LABELS = { 0: 'Petit', 1: 'Moyen', 2: 'Grand' };
  const SAINT_QUENTIN  = [49.8489, 3.2874];

  proj4.defs('EPSG:3949', '+proj=lcc +lat_0=49 +lon_0=3 +lat_1=48.25 +lat_2=49.75 +x_0=1700000 +y_0=8200000 +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs +type=crs');

  let arbres      = [];
  let clusterMap  = null;

  // --- Alerts ---

  const alertErr    = document.getElementById('alert-err');
  const alertErrMsg = document.getElementById('alert-err-msg');

  function showErr(msg) {
    alertErrMsg.textContent = msg;
    alertErr.style.display  = '';
    alertErr.scrollIntoView({ behavior: 'smooth' });
  }

  function hideErr() {
    alertErr.style.display = 'none';
  }

  // --- Utility ---

  function esc(str) {
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function fmt(val, unit) {
    return val != null ? Number(val).toFixed(1) + ' ' + unit : '—';
  }

  // --- Load arbres for select ---

  async function loadArbres() {
    try {
      const resp = await getAllArbres();
      arbres = resp.data || [];
      const sel = document.getElementById('existing_tree_id');
      sel.innerHTML = '<option value="">-- Sélectionner un arbre --</option>';
      arbres.forEach(a => {
        const opt   = document.createElement('option');
        opt.value   = a.id_arbre;
        opt.textContent = `ID ${a.id_arbre} - ${a.espece_nom || 'Arbre'} | H: ${Number(a.haut_tot || 0).toFixed(1)} m | Ø: ${Number(a.diam_tronc || 0).toFixed(1)} cm`;
        sel.appendChild(opt);
      });
    } catch (e) {
      showErr('Impossible de charger les arbres : ' + e.message);
    }
  }

  // --- Prediction result box ---

  function renderPredictionResult(result, sourceId) {
    const box = document.getElementById('prediction-result');
    if (result.status !== 'success') {
      box.innerHTML = `<div class="alert alert-err">✕ ${esc(result.error || 'Erreur inconnue')}</div>`;
      box.style.display = '';
      return;
    }
    const sourceHtml = sourceId
      ? `<div class="result-item"><span class="result-label">Arbre source :</span><span class="result-value">ID ${parseInt(sourceId)}</span></div>`
      : '';
    box.innerHTML = `
      <div class="prediction-box">
        <h3>✓ Résultat de la prédiction</h3>
        ${sourceHtml}
        <div class="result-item"><span class="result-label">Catégorie :</span><span class="result-value result-success">${esc(result.categorie)}</span></div>
        <div class="result-item"><span class="result-label">Hauteur :</span><span class="result-value">${Number(result.hauteur_tot).toFixed(1)} m</span></div>
        <div class="result-item"><span class="result-label">Diamètre :</span><span class="result-value">${Number(result.tronc_diam).toFixed(1)} cm</span></div>
      </div>`;
    box.style.display = '';
    box.scrollIntoView({ behavior: 'smooth' });
  }

  function setBtn(btn, label, disabled) {
    btn.disabled    = disabled;
    btn.textContent = label;
  }

  // --- Manual form ---

  document.getElementById('form-manual').addEventListener('submit', async function (e) {
    e.preventDefault();
    hideErr();
    const fd        = new FormData(this);
    const haut_tot  = parseFloat(fd.get('haut_tot'));
    const diam_tronc = parseFloat(fd.get('diam_tronc'));
    const k         = parseInt(fd.get('k'), 10);
    const btn       = this.querySelector('[type=submit]');
    setBtn(btn, 'Prédiction…', true);
    try {
      renderPredictionResult(await predictTreeSize(haut_tot, diam_tronc, k), null);
    } catch (err) {
      showErr(err.message);
      console.error('Prediction error:', err);
    } finally {
      setBtn(btn, 'Prédire la saisie', false);
    }
  });

  // --- Existing tree form ---

  document.getElementById('form-existing').addEventListener('submit', async function (e) {
    e.preventDefault();
    hideErr();
    const fd     = new FormData(this);
    const treeId = parseInt(fd.get('existing_tree_id'), 10);
    const k      = parseInt(fd.get('existing_k'), 10);
    if (!treeId) { showErr('Veuillez sélectionner un arbre.'); return; }
    const tree   = arbres.find(a => Number(a.id_arbre) === treeId);
    if (!tree)   { showErr('Arbre introuvable.'); return; }
    const haut_tot   = parseFloat(tree.haut_tot);
    const diam_tronc = parseFloat(tree.diam_tronc);
    if (!(haut_tot > 0) || !(diam_tronc > 0)) {
      showErr("L'arbre sélectionné n'a pas de dimensions exploitables.");
      return;
    }
    const btn = this.querySelector('[type=submit]');
    setBtn(btn, 'Prédiction…', true);
    try {
      renderPredictionResult(await predictTreeSize(haut_tot, diam_tronc, k), treeId);
    } catch (err) {
      showErr(err.message);
    } finally {
      setBtn(btn, "Prédire l'arbre sélectionné", false);
    }
  });

  // --- Batch analysis ---

  document.getElementById('btn-batch').addEventListener('click', async function () {
    hideErr();
    const k = parseInt(document.getElementById('batch_k').value, 10);
    setBtn(this, 'Analyse en cours…', true);
    try {
      const result = await predictClusters(arbres, k);
      if (result.status !== 'success') throw new Error(result.error || "Erreur lors de l'analyse");
      renderBatchResults(result.data);
      document.getElementById('batch-form-section').style.display = 'none';
      document.getElementById('batch-results').style.display = '';
      initClusterMap(result.data);
    } catch (err) {
      showErr(err.message);
    } finally {
      setBtn(this, 'Analyser tous les arbres', false);
    }
  });

  document.getElementById('btn-recalc').addEventListener('click', function () {
    document.getElementById('batch-results').style.display     = 'none';
    document.getElementById('batch-form-section').style.display = '';
    if (clusterMap) { clusterMap.remove(); clusterMap = null; }
  });

  // --- Batch rendering ---

  function clusterMeta(clusters) {
    const counts = {}, colors = {};
    clusters.forEach(a => {
      const label = a.cluster_label || CLUSTER_LABELS[a.cluster] || ('Cluster ' + a.cluster);
      if (!colors[label]) colors[label] = PALETTE[Object.keys(colors).length % PALETTE.length];
      counts[label] = (counts[label] || 0) + 1;
    });
    return { counts, colors };
  }

  function renderBatchResults(clusters) {
    const { counts, colors } = clusterMeta(clusters);

    // Stats
    let statsHtml = `
      <div class="stat"><div class="val">${clusters.length}</div><div class="lbl">Arbres analysés</div></div>
      <div class="stat"><div class="val">${Object.keys(counts).length}</div><div class="lbl">Clusters</div></div>`;
    Object.entries(counts).forEach(([label, n]) => {
      statsHtml += `<div class="stat"><div class="val" style="--color:${colors[label]}">${n}</div><div class="lbl">${esc(label)}</div></div>`;
    });
    document.getElementById('batch-stats').innerHTML = statsHtml;

    // Legend
    let legendHtml = '<span class="legend-label">Clusters :</span>';
    Object.entries(counts).forEach(([label, n]) => {
      legendHtml += `<div class="legend-item"><div class="legend-dot" style="--bg:${colors[label]};"></div><span>${esc(label)} — ${n} arbre${n > 1 ? 's' : ''}</span></div>`;
    });
    document.getElementById('batch-legend').innerHTML = legendHtml;

    // Table
    document.getElementById('batch-tbody').innerHTML = clusters.map(a => {
      const c     = a.cluster ?? 0;
      const col   = PALETTE[c % PALETTE.length];
      const label = a.cluster_label || CLUSTER_LABELS[c] || ('Cluster ' + c);
      return `<tr>
        <td class="table-id-cell">${a.id_arbre ?? ''}</td>
        <td><span class="table-cluster-badge" style="--bg:${col};--txt:${col};">
          <span class="table-badge-dot" style="--dot-bg:${col};"></span>${esc(label)}
        </span></td>
        <td class="table-species-cell">${esc(a.espece_nom || '—')}</td>
        <td>${fmt(a.haut_tot,  'm')}</td>
        <td>${fmt(a.haut_tronc,'m')}</td>
        <td>${fmt(a.diam_tronc,'cm')}</td>
        <td>${esc(a.etat      || '—')}</td>
        <td>${esc(a.stade_dev || '—')}</td>
        <td>${a.remarquable ? '<span class="badge badge-green">Oui</span>' : '<span class="badge badge-gray">Non</span>'}</td>
      </tr>`;
    }).join('');
  }

  // --- Cluster map ---

  function initClusterMap(clusters) {
    if (clusterMap) { clusterMap.remove(); }
    clusterMap = L.map('map-clusters');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors', maxZoom: 19,
    }).addTo(clusterMap);
    clusterMap.setView(SAINT_QUENTIN, 13);

    const pts = [];
    clusters.forEach(a => {
      const x = parseFloat(a.X), y = parseFloat(a.Y);
      if (isNaN(x) || isNaN(y)) return;
      const [lng, lat] = proj4('EPSG:3949', 'EPSG:4326', [x, y]);
      if (isNaN(lat) || isNaN(lng)) return;

      const c     = a.cluster ?? 0;
      const label = a.cluster_label || CLUSTER_LABELS[c] || ('Cluster ' + c);
      const col   = PALETTE[c % PALETTE.length];

      const icon = L.divIcon({
        className: '',
        html: `<div class="map-marker-icon" style="--marker-bg:${col};"></div>`,
        iconSize: [12, 12], iconAnchor: [6, 6],
      });

      const rows = [
        ['Catégorie', label],
        ['Espèce',    a.espece_nom || '—'],
        ['H. tot.',   fmt(a.haut_tot, 'm')],
        ['État',      a.etat || '—'],
      ].map(([k, v]) => `<tr><td class="map-tooltip-cell">${k}</td><td>${esc(String(v))}</td></tr>`).join('');

      L.marker([lat, lng], { icon }).addTo(clusterMap).bindTooltip(
        `<div class="map-tooltip">
           <div class="map-tooltip-title">${esc(a.espece_nom || 'Arbre #' + (a.id_arbre || ''))}</div>
           <table class="map-tooltip-table">${rows}</table>
         </div>`,
        { sticky: true, direction: 'top', offset: [0, -8], opacity: 1 },
      );
      pts.push([lat, lng]);
    });

    if (pts.length) clusterMap.fitBounds(pts, { padding: [40, 40] });
  }
  

  loadArbres();

});



