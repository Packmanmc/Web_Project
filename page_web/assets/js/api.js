function apiCall(endpoint, options) {
  const API_URL = process.env.API_URL;
  return fetch(API_URL + endpoint, options);
}

function apiGet(endpoint) {
  return apiCall(endpoint).then(res => {
    if (!res.ok) throw new Error(`API error: ${res.status} ${res.statusText}`);
    return res.json();
  });
}

function apiPost(endpoint, data) {
    return apiCall(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    }).then(res => res.json().then(body => {
        if (!res.ok) throw new Error(body.error || `API error: ${res.status}`);
        return body;
    }));
}

function apiDelete(endpoint) {
    return apiCall(endpoint, { method: 'DELETE' }).then(res => {
        if (!res.ok) throw new Error(`API error: ${res.status} ${res.statusText}`);
        return res.json();
    });
}

function apiPut(endpoint, data) {
    return apiCall(endpoint, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    }).then(res => {
        if (!res.ok) throw new Error(`API error: ${res.status} ${res.statusText}`);
        return res.json();
    });
}

function getAllArbres() {
    return apiGet('/arbres');
}

function getArbre(id) {
    return apiGet(`/arbres/${id}`);
}

function createArbre(data) {
    return apiPost('/arbres', data);
}

function updateArbre(id, data) {
    return apiPut(`/arbres/${id}`, data);
}

function deleteArbre(id) {
    return apiDelete(`/arbres/${id}`);
}

function getStats() {
    return apiGet('/stats');
}

function getEspeces() {
    return apiGet('/especes');
}

function getQuartiers() {
    return apiGet('/quartiers');
}

function getSituations() {
    return apiGet('/situations');
}

function getStadesDev() {
    return apiGet('/stades');
}

function getEtats() {
    return apiGet('/etats');
}

function getRemarquables() {
    return apiGet('/remarquables');
}

function predictTreeSize(haut_tot, diam_tronc, k) {
    return apiPost('/predict', { haut_tot, diam_tronc, k });
}

function predictClusters(k=3) {
    return apiPost('/predict-clusters', { k });
}