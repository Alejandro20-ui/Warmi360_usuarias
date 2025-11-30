document.addEventListener("DOMContentLoaded", function () {
    let cantidad = 1;
    const PRECIO = 40;
    const inputCantidad = document.getElementById('cantidad');
    const totalSpan = document.getElementById('total');

    function actualizarTotal() {
        const total = cantidad * PRECIO;
        totalSpan.textContent = `S/. ${total.toFixed(2)}`;
        inputCantidad.value = cantidad;
    }

    document.querySelector('[data-action="plus"]').addEventListener('click', () => {
        cantidad++;
        actualizarTotal();
    });

    document.querySelector('[data-action="minus"]').addEventListener('click', () => {
        if (cantidad > 1) {
            cantidad--;
            actualizarTotal();
        }
    });

    const form = document.getElementById('form-pago');
    const modalYape = new bootstrap.Modal(document.getElementById('modalYape'));
    const modalPlin = new bootstrap.Modal(document.getElementById('modalPlin'));
    const modalTarjeta = new bootstrap.Modal(document.getElementById('modalTarjeta'));
    const qrOutput = document.getElementById('qr');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const nombre = document.getElementById('nombre').value.trim();
        const direccion = document.getElementById('direccion').value.trim();
        const metodo = document.getElementById('metodo-pago').value;

        if (!nombre || !direccion || !metodo) {
            alert('Completa todos los campos');
            return;
        }
        if (metodo === 'yape') {
            modalYape.show();
        } else if (metodo === 'plin') {
            modalPlin.show();
        } else if (metodo === 'tarjeta') {
            modalTarjeta.show();
        }
    });
    document.querySelectorAll('.btn-generar-pago').forEach(btn => {
        btn.addEventListener('click', async () => {
            const metodo = btn.dataset.metodo;
            const nombre = document.getElementById('nombre').value.trim();
            const direccion = document.getElementById('direccion').value.trim();

            let datosAdicionales = {};
            if (metodo === 'tarjeta') {
                const num = document.getElementById('numTarjeta').value;
                const exp = document.getElementById('expTarjeta').value;
                const cvv = document.getElementById('cvvTarjeta').value;
                if (!num || !exp || !cvv) {
                    alert('Completa los datos de la tarjeta');
                    return;
                }
                datosAdicionales = { num, exp, cvv };
            }
            try {
                const res = await fetch('../php/guardar_pedido.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        cantidad,
                        nombre_completo: nombre,
                        direccion,
                        metodo_pago: metodo,
                        ...datosAdicionales
                    })
                });

                const data = await res.json();

                if (data.success) {
                    modalYape.hide();
                    modalPlin.hide();
                    modalTarjeta.hide();
                    if (metodo === 'yape') {
                        qrOutput.textContent = `Escanea el código\n\nNombre: WARMI360\nMonto: S/. ${(cantidad * PRECIO).toFixed(2)}`;
                    } else if (metodo === 'plin') {
                        qrOutput.textContent = `Escanea el código Plin\n\nMonto: S/. ${(cantidad * PRECIO).toFixed(2)}`;
                    } else if (metodo === 'tarjeta') {
                        qrOutput.textContent = `Pago con tarjeta procesado.\nMonto: S/. ${(cantidad * PRECIO).toFixed(2)}\nEstado: Pendiente de confirmación`;
                    }

                    alert('Pedido registrado correctamente');
                } else {
                    alert('Error al guardar el pedido: ' + (data.message || 'Desconocido'));
                }
            } catch (err) {
                console.error('Error completo:', err);
                alert('Error de conexión. Revisa la consola del navegador.');
            }
        });
    });

    // =============================
    // MAPA, AUTOCOMPLETE Y GEOLOCALIZACIÓN
    // =============================
    const DIRECCION_ENDPOINT = "../php/direccion.php";
    // -----------------------

    // elementos
    const inputDireccion = document.getElementById("direccion");
    const suggestionsBox = document.getElementById("suggestions");
    const latInput = document.getElementById("lat");
    const lonInput = document.getElementById("lon");

    // mapa / marcadores
    let map;
    let markerLayerPermanent;
    let markerLayerTemp;
    let permanentFeature = null;

    // Autocomplete control
    let autocompleteController = null;
    let debounceTimer = null;
    let selectedSuggestionIndex = -1;

    // Estado: marcador sólo se mueve cuando el usuario lo "confirma"
    // (click en mapa, seleccionar sugerencia o Enter)
    let markerPlaced = false;

    // ===================================
    // Estilos de marcador
    // ===================================
    function createPermanentStyle() {
        return new ol.style.Style({
            image: new ol.style.Circle({
                radius: 10,
                fill: new ol.style.Fill({ color: 'rgba(255,20,147,0.95)' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 3 })
            })
        });
    }

    function createTempStyle() {
        return new ol.style.Style({
            image: new ol.style.Circle({
                radius: 7,
                fill: new ol.style.Fill({ color: 'rgba(0,123,255,0.85)' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 2 })
            })
        });
    }

    // ===================================
    // Init map
    // ===================================
    function initMap(lon = -76.1527, lat = -13.4214) {
        markerLayerPermanent = new ol.layer.Vector({
            source: new ol.source.Vector(),
            style: createPermanentStyle()
        });
        markerLayerTemp = new ol.layer.Vector({
            source: new ol.source.Vector(),
            style: createTempStyle()
        });

        map = new ol.Map({
            target: 'map',
            layers: [
                new ol.layer.Tile({ source: new ol.source.OSM() }),
                markerLayerTemp, // temp encima para preview
                markerLayerPermanent
            ],
            view: new ol.View({
                center: ol.proj.fromLonLat([lon, lat]),
                zoom: 15
            }),
            controls: [
                new ol.control.Zoom(),
                new ol.control.Attribution()
            ]
        });

        // click en mapa -> mueve marcador permanente y reverse geocode
        map.on('click', async (evt) => {
            const [lonC, latC] = ol.proj.toLonLat(evt.coordinate);

            // Colocar/actualizar marcador permanente
            setMarkerPermanent(lonC, latC);

            // actualizar inputs lat/lon
            latInput.value = latC;
            lonInput.value = lonC;
            markerPlaced = true;

            // reverse geocode y aplicar al input direccion
            const feat = await reverseGeocode(latC, lonC);
            if (feat) {
                inputDireccion.value = feat.properties.display_name || feat.properties.name || inputDireccion.value;
            }

            // limpiar sugerencias
            clearSuggestions();
            clearTempMarker();
        });

        // Si sólo navega (pan/zoom) no mover marcador: no hacemos nada en 'moveend' ni 'pointerdrag'
    }

    // ===================================
    // Marcadores: permanente / temporal (preview)
    // ===================================
    function setMarkerPermanent(lon, lat) {
        markerLayerPermanent.getSource().clear();
        const f = new ol.Feature({
            geometry: new ol.geom.Point(ol.proj.fromLonLat([lon, lat]))
        });
        markerLayerPermanent.getSource().addFeature(f);
        permanentFeature = f;
    }

    function setMarkerTemp(lon, lat) {
        markerLayerTemp.getSource().clear();
        const f = new ol.Feature({
            geometry: new ol.geom.Point(ol.proj.fromLonLat([lon, lat]))
        });
        markerLayerTemp.getSource().addFeature(f);
    }

    function clearTempMarker() {
        markerLayerTemp.getSource().clear();
    }

    // ===================================
    // Fetch helpers (autocomplete / reverse)
    // ===================================
    async function fetchAutocomplete(query) {
        if (autocompleteController) {
            autocompleteController.abort();
        }
        autocompleteController = new AbortController();

        const url = `${DIRECCION_ENDPOINT}?q=${encodeURIComponent(query)}`;
        try {
            const res = await fetch(url, { signal: autocompleteController.signal });
            if (!res.ok) return null;
            const data = await res.json();
            return data;
        } catch (err) {
            if (err.name === 'AbortError') return null;
            console.error('Autocomplete fetch error', err);
            return null;
        }
    }

    async function reverseGeocode(lat, lon) {
        try {
            const res = await fetch(`${DIRECCION_ENDPOINT}?lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lon)}`);
            if (!res.ok) return null;
            const data = await res.json();
            // direccion.php devuelve features[]
            if (data && Array.isArray(data.features) && data.features[0]) {
                return data.features[0];
            }
            return null;
        } catch (err) {
            console.error('Reverse geocode error', err);
            return null;
        }
    }

    // ===================================
    // Autocomplete UI
    // ===================================
    function clearSuggestions() {
        suggestionsBox.innerHTML = '';
        suggestionsBox.style.display = 'none';
        selectedSuggestionIndex = -1;
    }

    function showSuggestions() {
        suggestionsBox.style.display = 'block';
    }

    function createSuggestionItem(text, feature) {
        const li = document.createElement('li');
        li.className = 'list-group-item suggestion-item';
        li.setAttribute('role', 'option');
        li.setAttribute('aria-selected', 'false');
        li.style.cursor = 'pointer';
        li.style.userSelect = 'none';
        li.tabIndex = -1;
        li.textContent = text;
        // guardar coords
        li.dataset.lon = feature.geometry.coordinates[0];
        li.dataset.lat = feature.geometry.coordinates[1];
        // evento hover
        li.addEventListener('mouseenter', () => {
            li.classList.add('active');
        });
        li.addEventListener('mouseleave', () => {
            li.classList.remove('active');
        });
        li.addEventListener('click', () => {
            // Al seleccionar: mover marcador permanente y aplicar dirección
            applySuggestion(feature);
            clearSuggestions();
        });
        return li;
    }

    function applySuggestion(feature) {
        const coords = feature.geometry && feature.geometry.coordinates;
        if (!coords || coords.length < 2) return;
        const lon = coords[0];
        const lat = coords[1];

        // mover marcador permanente
        setMarkerPermanent(lon, lat);
        markerPlaced = true;

        // actualizar inputs
        latInput.value = lat;
        lonInput.value = lon;

        // centrar mapa
        map.getView().animate({ center: ol.proj.fromLonLat([lon, lat]), zoom: 17, duration: 300 });

        // set direccion
        const props = feature.properties || {};
        inputDireccion.value = props.display_name || props.name || inputDireccion.value;

        // limpiar temp
        clearTempMarker();
    }

    // ===================================
    // Input events: typing, keyboard navigation, Enter
    // ===================================
    inputDireccion.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const q = inputDireccion.value.trim();
        if (q.length < 3) {
            clearSuggestions();
            clearTempMarker();
            return;
        }
        inputDireccion.classList.add('loading');

        debounceTimer = setTimeout(async () => {
            const data = await fetchAutocomplete(q);
            inputDireccion.classList.remove('loading');
            if (!data || !Array.isArray(data.features) || data.features.length === 0) {
                clearSuggestions();
                clearTempMarker();
                return;
            }
            // render suggestions (hasta 8)
            suggestionsBox.innerHTML = '';
            data.features.slice(0, 8).forEach(feat => {
                const name = (feat.properties && (feat.properties.display_name || feat.properties.name)) || '';
                const li = createSuggestionItem(name, feat);
                suggestionsBox.appendChild(li);
            });
            showSuggestions();

            // preview: temp marker on FIRST suggestion, but NOT permanent
            const first = data.features[0];
            if (first && first.geometry && first.geometry.coordinates) {
                const [lonF, latF] = first.geometry.coordinates;
                setMarkerTemp(lonF, latF);
                map.getView().animate({ center: ol.proj.fromLonLat([lonF, latF]), zoom: 16, duration: 250 });
            }
        }, 350);
    });

    inputDireccion.addEventListener('keydown', (ev) => {
        const items = Array.from(suggestionsBox.querySelectorAll('.suggestion-item'));
        if (!items.length) {
            if (ev.key === 'Enter') {
                ev.preventDefault();
                // Si no hay sugerencias visibles: intentar buscar y usar la primera del servidor
                processEnterOnDireccion();
            }
            return;
        }

        switch (ev.key) {
            case 'ArrowDown':
                ev.preventDefault();
                selectedSuggestionIndex = Math.min(selectedSuggestionIndex + 1, items.length - 1);
                highlightSuggestion(items, selectedSuggestionIndex);
                break;
            case 'ArrowUp':
                ev.preventDefault();
                selectedSuggestionIndex = Math.max(selectedSuggestionIndex - 1, 0);
                highlightSuggestion(items, selectedSuggestionIndex);
                break;
            case 'Enter':
                ev.preventDefault();
                if (selectedSuggestionIndex >= 0 && items[selectedSuggestionIndex]) {
                    items[selectedSuggestionIndex].click();
                } else {
                    // usar la primera sugerencia si existe
                    if (items[0]) items[0].click();
                    else processEnterOnDireccion();
                }
                break;
            case 'Escape':
                clearSuggestions();
                clearTempMarker();
                break;
            default:
                break;
        }
    });

    function highlightSuggestion(items, index) {
        items.forEach((it, i) => {
            if (i === index) {
                it.classList.add('active');
                it.setAttribute('aria-selected', 'true');
                it.scrollIntoView({ block: 'nearest' });
                const lon = parseFloat(it.dataset.lon);
                const lat = parseFloat(it.dataset.lat);
                if (!isNaN(lon) && !isNaN(lat)) {
                    setMarkerTemp(lon, lat);
                    map.getView().animate({ center: ol.proj.fromLonLat([lon, lat]), zoom: 16, duration: 200 });
                }
            } else {
                it.classList.remove('active');
                it.setAttribute('aria-selected', 'false');
            }
        });
    }

    // Si usuario presiona Enter cuando no hay sugerencias visibles:
    async function processEnterOnDireccion() {
        const q = inputDireccion.value.trim();
        if (!q) return;
        inputDireccion.classList.add('loading');
        const data = await fetchAutocomplete(q);
        inputDireccion.classList.remove('loading');
        if (!data || !Array.isArray(data.features) || data.features.length === 0) {
            return;
        }
        const first = data.features[0];
        applySuggestion(first);
        clearSuggestions();
    }

    // click fuera -> cerrar sugerencias y limpiar temp marker
    document.addEventListener('click', (e) => {
        if (!suggestionsBox.contains(e.target) && e.target !== inputDireccion) {
            clearSuggestions();
            clearTempMarker();
        }
    });

    // ===================================
    // Geolocalización al cargar (autofill)
    // ===================================
    function detectUserLocationAndApply() {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(async (pos) => {
            const lat = pos.coords.latitude;
            const lon = pos.coords.longitude;

            // centrar mapa y añadir marcador permanente
            map.getView().animate({ center: ol.proj.fromLonLat([lon, lat]), zoom: 17, duration: 300 });
            setMarkerPermanent(lon, lat);
            latInput.value = lat;
            lonInput.value = lon;
            markerPlaced = true;

            // reverse geocode y aplicar direccion al campo
            const feat = await reverseGeocode(lat, lon);
            if (feat) {
                inputDireccion.value = feat.properties.display_name || feat.properties.name || inputDireccion.value;
            }
        }, (err) => {
            console.warn('Geolocation falló o fue denegada', err);
            // no hacemos nada si falla
        }, { enableHighAccuracy: true, maximumAge: 60000, timeout: 7000 });
    }

    // ===================================
    // Inicialización bootstrap
    // ===================================
    (function bootstrap() {
        initMap();
        detectUserLocationAndApply();
    })();

    // Exponer utilidades para debugging (opcional)
    window.__warmi_map = {
        setMarkerPermanent: (lon, lat) => setMarkerPermanent(lon, lat),
        setMarkerTemp: (lon, lat) => setMarkerTemp(lon, lat),
        reverseGeocode: (lat, lon) => reverseGeocode(lat, lon)
    };
});
