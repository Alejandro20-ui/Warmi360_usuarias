<?php
declare(strict_types=1);

const NOMINATIM_BASE = 'https://nominatim.openstreetmap.org';
const USER_AGENT = 'WARMI360/1.0 (dev@warmi360.local)'; // puedes dejar uno "falso" sintáctico

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Vary: Accept-Encoding');

function json_out($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function nominatim_get(string $path, array $params, int $timeout = 8): ?array {
    $url = rtrim(NOMINATIM_BASE, '/') . $path . '?' . http_build_query($params);
    $opts = [
        'http' => [
            'header'  => "User-Agent: " . USER_AGENT . "\r\n" .
                         "Accept: application/json\r\n" .
                         "Accept-Language: es\r\n",
            'timeout' => $timeout
        ]
    ];
    $ctx = stream_context_create($opts);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) return null;
    $data = json_decode($resp, true);
    return is_array($data) ? $data : null;
}

function build_feature_from_search_item(array $item): array {
    $lon = isset($item['lon']) ? (float)$item['lon'] : null;
    $lat = isset($item['lat']) ? (float)$item['lat'] : null;
    $display = $item['display_name'] ?? '';
    $name = $item['namedetails']['name'] ?? (explode(',', $display)[0] ?? $display);
    return [
        'geometry' => ['coordinates' => [$lon, $lat]],
        'properties' => [
            'display_name' => $display,
            'name' => $name,
            'raw' => $item
        ]
    ];
}

// Entrypoint
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$lat = isset($_GET['lat']) ? $_GET['lat'] : '';
$lon = isset($_GET['lon']) ? $_GET['lon'] : '';

if ($q !== '') {
    // Sanitize simple
    if (mb_strlen($q) > 200) $q = mb_substr($q, 0, 200);

    $res = nominatim_get('/search', [
        'q' => $q,
        'format' => 'jsonv2',
        'addressdetails' => 1,
        'namedetails' => 1,
        'limit' => 8
    ], 6);

    if (!$res) {
        json_out(['features' => []]);
    }

    $features = [];
    foreach ($res as $item) {
        if (isset($item['lat'], $item['lon'])) {
            $features[] = build_feature_from_search_item($item);
        }
    }

    json_out(['features' => $features]);
}

if ($lat !== '' && $lon !== '') {
    // validate
    if (!is_numeric($lat) || !is_numeric($lon)) {
        json_out(['features' => []], 400);
    }
    $latF = (float)$lat;
    $lonF = (float)$lon;

    $res = nominatim_get('/reverse', [
        'lat' => $latF,
        'lon' => $lonF,
        'format' => 'jsonv2',
        'addressdetails' => 1
    ], 6);

    if (!$res) {
        json_out(['features' => []]);
    }

    // normalize to single feature with similar shape to search
    $display = $res['display_name'] ?? '';
    $name = $res['name'] ?? (explode(',', $display)[0] ?? $display);
    $feature = [
        'geometry' => ['coordinates' => [$lonF, $latF]],
        'properties' => [
            'display_name' => $display,
            'name' => $name,
            'raw' => $res
        ]
    ];

    json_out(['features' => [$feature]]);
}

// no params
json_out(['features' => []], 400);
