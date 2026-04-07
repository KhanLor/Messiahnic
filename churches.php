<?php
require __DIR__ . '/bootstrap.php';
ensure_church_locations_table();

$pageTitle = 'Churches Map';
$googleMapsApiKey = GOOGLE_MAPS_API_KEY;

$query = trim($_GET['q'] ?? '');
$city = trim($_GET['city'] ?? '');
$churches = [];
$cities = [];

try {
    $cities = db()->query('SELECT DISTINCT city FROM church_locations ORDER BY city ASC')->fetchAll();

    $sql = 'SELECT * FROM church_locations WHERE 1=1';
    $params = [];

    if ($query !== '') {
        $sql .= ' AND (name LIKE ? OR address LIKE ? OR pastor_name LIKE ? OR description LIKE ?)';
        $wildcard = '%' . $query . '%';
        $params = [$wildcard, $wildcard, $wildcard, $wildcard];
    }

    if ($city !== '') {
        $sql .= ' AND city = ?';
        $params[] = $city;
    }

    $sql .= ' ORDER BY name ASC';
    $statement = db()->prepare($sql);
    $statement->execute($params);
    $churches = $statement->fetchAll();
} catch (Throwable $exception) {
    flash('error', 'Church map data is not ready yet. Run the church_locations table migration first.');
}

$mapData = [];
foreach ($churches as $church) {
    $mapData[] = [
        'id' => (int) $church['id'],
        'name' => $church['name'],
        'city' => $church['city'],
        'pastor_name' => $church['pastor_name'],
        'address' => $church['address'],
        'lat' => (float) $church['latitude'],
        'lng' => (float) $church['longitude'],
        'contact' => $church['contact'],
        'photo' => !empty($church['photo_path']) ? app_url($church['photo_path']) : null,
    ];
}

$bannerImage = app_url('assets/images/church-map-banner.svg');
$bannerAlt = 'Church branches and location banner';

include __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="section-title">
        <h2>Church locations map</h2>
        <span class="muted">Find churches by name, area, or city</span>
    </div>

    <div class="church-banner">
        <img src="<?= e($bannerImage) ?>" alt="<?= e($bannerAlt) ?>">
    </div>

    <form class="panel form" method="get">
        <div class="field-grid">
            <label>
                Search church
                <input type="search" name="q" value="<?= e($query) ?>" placeholder="Church name, address, or keyword">
            </label>
            <label>
                City
                <select name="city">
                    <option value="">All cities</option>
                    <?php foreach ($cities as $option): ?>
                        <option value="<?= e($option['city']) ?>" <?= $city === $option['city'] ? 'selected' : '' ?>><?= e($option['city']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <button class="btn btn-primary" type="submit">Search map</button>
    </form>
</section>

<section class="section grid cols-2">
    <div class="panel">
        <?php if ($googleMapsApiKey === ''): ?>
            <div class="alert alert-info">Google Maps API key is missing. Set <strong>MESSIAH_GOOGLE_MAPS_API_KEY</strong> in your environment to display the map.</div>
        <?php endif; ?>
        <div id="churchMap" class="map-canvas" aria-label="Church map"></div>
    </div>

    <aside class="panel">
        <div class="section-title">
            <h2>Locations</h2>
            <span class="muted"><?= count($churches) ?> found</span>
        </div>
        <div class="list-stack">
            <?php foreach ($churches as $church): ?>
                <article class="church-card" data-church-id="<?= (int) $church['id'] ?>" tabindex="0" role="button" aria-label="Show <?= e($church['name']) ?> on map">
                    <?php if (!empty($church['photo_path'])): ?>
                        <img class="church-photo" src="<?= e(app_url($church['photo_path'])) ?>" alt="<?= e($church['name']) ?> photo">
                    <?php endif; ?>
                    <h3><?= e($church['name']) ?></h3>
                    <p><strong>City:</strong> <?= e($church['city']) ?></p>
                    <?php if (!empty($church['pastor_name'])): ?>
                        <p><strong>Pastor:</strong> <?= e($church['pastor_name']) ?></p>
                    <?php endif; ?>
                    <p><strong>Address:</strong> <?= e($church['address']) ?></p>
                    <?php if (!empty($church['contact'])): ?>
                        <p><strong>Contact:</strong> <?= e($church['contact']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($church['description'])): ?>
                        <p class="muted"><?= e($church['description']) ?></p>
                    <?php endif; ?>
                    <div class="actions church-actions">
                        <button class="btn btn-ghost" type="button" data-open-directions="<?= (int) $church['id'] ?>">Directions from my location</button>
                        <a class="btn btn-ghost" href="https://www.google.com/maps/search/?api=1&query=<?= e(urlencode((string) $church['latitude'] . ',' . (string) $church['longitude'])) ?>" target="_blank" rel="noopener">Open in Google Maps</a>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (!$churches): ?>
                <p class="muted">No churches found for your search.</p>
            <?php endif; ?>
        </div>
    </aside>
</section>

<script>
    const churches = <?= json_encode($mapData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function initChurchMap() {
        const defaultCenter = { lat: 14.5995, lng: 120.9842 };
        const map = new google.maps.Map(document.getElementById('churchMap'), {
            center: defaultCenter,
            zoom: 6,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
        });

        if (churches.length === 0) {
            return;
        }

        const infoWindow = new google.maps.InfoWindow();
        const bounds = new google.maps.LatLngBounds();
        const markersByChurchId = new Map();
        const churchesById = new Map();

        function openDirectionsInGoogleMaps(church, origin) {
            const destination = `${Number(church.lat)},${Number(church.lng)}`;
            let url = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(destination) + '&travelmode=driving';
            url += '&origin=' + encodeURIComponent(origin);

            window.open(url, '_blank', 'noopener');
        }

        function openDirectionsFromMyLocation(church) {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported on this device/browser.');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const origin = `${position.coords.latitude},${position.coords.longitude}`;
                    openDirectionsInGoogleMaps(church, origin);
                },
                () => {
                    alert('Please allow location permission to get directions from your actual location.');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
            );
        }

        window.openDirectionsByChurchId = (churchId) => {
            const church = churchesById.get(Number(churchId));
            if (!church) {
                return false;
            }

            openDirectionsFromMyLocation(church);
            return false;
        };

        const buildPopup = (church) => {
            const destination = encodeURIComponent(`${Number(church.lat)},${Number(church.lng)}`);
            const mapUrl = 'https://www.google.com/maps/search/?api=1&query=' + destination;

            return (
                (church.photo ? '<img src="' + escapeHtml(church.photo) + '" alt="' + escapeHtml(church.name) + ' photo" style="width:100%;max-width:240px;border-radius:10px;margin-bottom:8px;display:block;">' : '') +
                '<strong>' + escapeHtml(church.name) + '</strong><br>' +
                (church.pastor_name ? 'Pastor: ' + escapeHtml(church.pastor_name) + '<br>' : '') +
                escapeHtml(church.address) + '<br>' +
                escapeHtml(church.city) + (church.contact ? '<br>' + escapeHtml(church.contact) : '') +
                '<div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;">' +
                '<a href="#" onclick="return window.openDirectionsByChurchId(' + Number(church.id) + ');" style="padding:6px 10px;border-radius:10px;border:1px solid #ccc;text-decoration:none;">Directions</a>' +
                '<a href="' + escapeHtml(mapUrl) + '" target="_blank" rel="noopener" style="padding:6px 10px;border-radius:10px;border:1px solid #ccc;text-decoration:none;">Open Map</a>' +
                '</div>'
            );
        };

        churches.forEach((church) => {
            churchesById.set(Number(church.id), church);
            const position = { lat: Number(church.lat), lng: Number(church.lng) };
            const marker = new google.maps.Marker({
                position,
                map,
                title: church.name,
            });

            marker.addListener('click', () => {
                infoWindow.setContent(buildPopup(church));
                infoWindow.open({ anchor: marker, map });
            });

            markersByChurchId.set(Number(church.id), { marker, church, position });

            bounds.extend(position);
        });

        document.querySelectorAll('.church-card[data-church-id]').forEach((card) => {
            const churchId = Number(card.getAttribute('data-church-id'));

            const focusChurchOnMap = () => {
                const entry = markersByChurchId.get(churchId);
                if (!entry) {
                    return;
                }

                map.panTo(entry.position);
                map.setZoom(Math.max(map.getZoom() || 6, 14));
                infoWindow.setContent(buildPopup(entry.church));
                infoWindow.open({ anchor: entry.marker, map });
            };

            card.addEventListener('click', focusChurchOnMap);
            card.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    focusChurchOnMap();
                }
            });
        });

        document.querySelectorAll('[data-open-directions]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                const churchId = Number(button.getAttribute('data-open-directions'));
                const church = churchesById.get(churchId);
                if (!church) {
                    return;
                }

                openDirectionsFromMyLocation(church);
            });
        });

        if (churches.length === 1) {
            map.setCenter(bounds.getCenter());
            map.setZoom(14);
        } else {
            map.fitBounds(bounds, 40);
        }
    }

    window.initChurchMap = initChurchMap;
</script>
<?php if ($googleMapsApiKey !== ''): ?>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= e(urlencode($googleMapsApiKey)) ?>&callback=initChurchMap&v=weekly"></script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
