document.addEventListener('DOMContentLoaded', function () {

  function fillSelect(sel, items, labelKey) {
    sel.innerHTML = '<option value="">— Sélectionner —</option>';
    items.forEach(item => {
      const opt = document.createElement('option');
      opt.value = item.id;
      opt.textContent = item[labelKey];
      sel.appendChild(opt);
    });
  }

  const alertOk     = document.getElementById('alert-ok');
  const alertErr    = document.getElementById('alert-err');
  const alertErrMsg = document.getElementById('alert-err-msg');

  function showOk() {
    alertErr.style.display = 'none';
    alertOk.style.display  = '';
    alertOk.scrollIntoView({ behavior: 'smooth' });
  }

  function showErr(msg) {
    alertOk.style.display   = 'none';
    alertErrMsg.textContent = msg;
    alertErr.style.display  = '';
    alertErr.scrollIntoView({ behavior: 'smooth' });
  }

  async function loadRefs() {
    try {
      const [esp, qua, sta, sit, eta] = await Promise.all([
        getEspeces(),
        getQuartiers(),
        getStadesDev(),
        getSituations(),
        getEtats(),
      ]);
      fillSelect(document.getElementById('sel-especes'),    esp.data, 'nom');
      fillSelect(document.getElementById('sel-quartiers'),  qua.data, 'quartier');
      fillSelect(document.getElementById('sel-stades'),     sta.data, 'libelle');
      fillSelect(document.getElementById('sel-situations'), sit.data, 'libelle');
      fillSelect(document.getElementById('sel-etats'),      eta.data, 'libelle');
    } catch (e) {
      showErr('Impossible de charger les références : ' + e.message);
    }
  }

  loadRefs();

  document.getElementById('arbreForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.textContent = 'Enregistrement…';

    const fd = new FormData(this);

    const payload = {
      X:             parseFloat(fd.get('X')),
      Y:             parseFloat(fd.get('Y')),
      haut_tot:      parseFloat(fd.get('haut_tot')),
      haut_tronc:    parseFloat(fd.get('haut_tronc')),
      diam_tronc:    parseFloat(fd.get('diam_tronc')),
      age_estime:    parseInt(fd.get('age_estime') || '0', 10),
      remarquable:   fd.get('remarquable') ? 1 : 0,
      id_stade_dev:  parseInt(fd.get('id_stade_dev'),  10),
      id_especes:    parseInt(fd.get('id_especes'),    10),
      id_etat:       parseInt(fd.get('id_etat'),       10),
      id_quartiers:  parseInt(fd.get('id_quartiers'),  10),
      id_situations: parseInt(fd.get('id_situations'), 10),
      nb_diagnostic: 0,
    };

    const invalid = Object.entries(payload)
      .filter(([, v]) => v === null || (typeof v === 'number' && isNaN(v)))
      .map(([k]) => k);

    if (invalid.length) {
      showErr('Champs invalides ou manquants : ' + invalid.join(', '));
      btn.disabled = false;
      btn.textContent = 'Enregistrer';
      return;
    }

    try {
      await createArbre(payload);
      showOk();
      this.reset();
      if (window._pickMarker && window._pickMap) {
        window._pickMap.removeLayer(window._pickMarker);
        window._pickMarker = null;
      }
    } catch (err) {
      showErr(err.message);
    } finally {
      btn.disabled = false;
      btn.textContent = 'Enregistrer';
    }
  });

});
