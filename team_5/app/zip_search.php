<?php
// Same-origin proxy for Japanese postal code lookup.
// Primary: PostcodeJP API (requires API key): https://api-doc.postcode-jp.com/
// Fallback: ZipCloud (no API key): https://zipcloud.ibsnet.co.jp/doc/api

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function load_dotenv_if_present(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $eqPos = strpos($line, '=');
        if ($eqPos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $eqPos));
        $value = trim(substr($line, $eqPos + 1));
        if ($key === '') {
            continue;
        }

        // Strip surrounding quotes
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        // Do not override existing env
        if (getenv($key) !== false || array_key_exists($key, $_ENV)) {
            continue;
        }

        $_ENV[$key] = $value;
        @putenv($key . '=' . $value);
    }
}

function get_http_status_code_from_headers(?array $headers): ?int
{
    if (!is_array($headers) || count($headers) === 0) {
        return null;
    }
    $first = $headers[0] ?? '';
    if (preg_match('/^HTTP\/[0-9.]+\s+(\d{3})\b/', (string)$first, $m)) {
        return (int)$m[1];
    }
    return null;
}

$zipcode = isset($_GET['zipcode']) ? (string)$_GET['zipcode'] : '';
$zipcode = preg_replace('/[^0-9]/', '', $zipcode);

if ($zipcode === '' || strlen($zipcode) !== 7) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => '郵便番号は7桁で入力してください',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Load .env locally without docker-compose changes (file is gitignored by default)
load_dotenv_if_present(__DIR__ . '/.env');

$postcodeJpApiKey = getenv('POSTCODE_JP_API_KEY');
if ($postcodeJpApiKey === false || $postcodeJpApiKey === '') {
    $postcodeJpApiKey = getenv('POSTCODEJP_API_KEY');
}

if ($postcodeJpApiKey !== false && $postcodeJpApiKey !== '') {
    // PostcodeJP API
    $url = 'https://apis.postcode-jp.com/api/v6/postcodes/' . rawurlencode($zipcode) . '?fields=allAddress';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'header' =>
            "Accept: application/json\r\n" .
                "apikey: " . $postcodeJpApiKey . "\r\n" .
                "User-Agent: team5-app\r\n",
        ],
    ]);

    $http_response_header = null;
    $raw = @file_get_contents($url, false, $context);
    $status = get_http_status_code_from_headers($http_response_header);

    if ($raw === false) {
        http_response_code(502);
        echo json_encode([
            'ok' => false,
            'error' => '郵便番号検索API(PostcodeJP)に接続できませんでした',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        http_response_code(502);
        echo json_encode([
            'ok' => false,
            'error' => '郵便番号検索API(PostcodeJP)の応答が不正です',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Error response is usually an object with message/code/httpStatusCode
    $isAssoc = array_keys($data) !== range(0, count($data) - 1);
    if ($isAssoc) {
        $message = (string)($data['message'] ?? '検索に失敗しました');
        http_response_code($status && $status >= 400 ? $status : 502);
        echo json_encode([
            'ok' => false,
            'error' => $message,
            'raw' => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (count($data) < 1) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'error' => '住所が見つかりませんでした',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $r0 = $data[0];
    $address = (string)($r0['allAddress'] ?? (($r0['pref'] ?? '') . ($r0['city'] ?? '') . ($r0['town'] ?? '')));

    echo json_encode([
        'ok' => true,
        'zipcode' => $zipcode,
        'address' => $address,
        'raw' => $r0,
        'provider' => 'postcode-jp',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Fallback: ZipCloud
$url = 'https://zipcloud.ibsnet.co.jp/api/search?zipcode=' . rawurlencode($zipcode);
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 5,
        'header' => "User-Agent: team5-app\r\n",
    ],
]);

$raw = @file_get_contents($url, false, $context);
if ($raw === false) {
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'error' => '郵便番号検索APIに接続できませんでした',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'error' => '郵便番号検索APIの応答が不正です',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!empty($data['message'])) {
    http_response_code(404);
    echo json_encode([
        'ok' => false,
        'error' => (string)$data['message'],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$results = $data['results'] ?? null;
if (!is_array($results) || count($results) < 1) {
    http_response_code(404);
    echo json_encode([
        'ok' => false,
        'error' => '住所が見つかりませんでした',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$r0 = $results[0];
$address = (
    ($r0['address1'] ?? '') .
    ($r0['address2'] ?? '') .
    ($r0['address3'] ?? '')
);

echo json_encode([
    'ok' => true,
    'zipcode' => $zipcode,
    'address' => $address,
    'raw' => $r0,
    'provider' => 'zipcloud',
], JSON_UNESCAPED_UNICODE);
