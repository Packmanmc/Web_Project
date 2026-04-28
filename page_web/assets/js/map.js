document.addEventListener('DOMContentLoaded', function () {

  proj4.defs('EPSG:3949', '+proj=lcc +lat_1=48.25 +lat_2=49.75 +lat_0=49 +lon_0=3 +x_0=1700000 +y_0=8200000 +ellps=GRS80 +units=m +no_defs +type=crs');

  const latEl = document.getElementById('lat');
  const lngEl = document.getElementById('lng');
  const SAINT_QUENTIN = [49.8489, 3.2870];
  const map = L.map('map-pick').setView(SAINT_QUENTIN, 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map);

  window._pickMap = map;
  window._pickMarker = null;

  function place(ll) {
    if (window._pickMarker) map.removeLayer(window._pickMarker);
    window._pickMarker = L.marker(ll).addTo(map);
    const [x, y] = proj4('EPSG:4326', 'EPSG:3949', [ll.lng, ll.lat]);
    latEl.value = y.toFixed(2);
    lngEl.value = x.toFixed(2);
  }

  map.on('click', e => place(e.latlng));

  const iY = parseFloat(latEl.value), iX = parseFloat(lngEl.value);
  if (!isNaN(iY) && !isNaN(iX)) {
    const [lng, lat] = proj4('EPSG:3949', 'EPSG:4326', [iX, iY]);
    map.setView(L.latLng(lat, lng), 14);
    place(L.latLng(lat, lng));
  }

});


