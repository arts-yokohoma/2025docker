<?php
define('SHOP_LAT', 35.46373);
define('SHOP_LNG', 139.60975);
define('MAX_DELIVERY_KM', 5);
define('EARTH_RADIUS_KM', 6371);

function checkDeliveryArea($postal_code)
{
    $zip = preg_replace('/[^0-9]/', '', $postal_code);

    if (strlen($zip) !== 7) {
        return [
            'status' => 'error',
            'msg' => '郵便番号の形式が正しくありません'
        ];
    }

    $location = getLatLngFromPostal($zip);
    if ($location === false) {
        return [
            'status' => 'error',
            'msg' => 'Postal Code မှားယွင်းနေပါသည် သို့မဟုတ် API ဆက်သွယ်မရပါ'
        ];
    }

    $km = calculateDistance(
        SHOP_LAT,
        SHOP_LNG,
        $location['lat'],
        $location['lng']
    );

    if ($km > MAX_DELIVERY_KM) {
        return [
            'status' => 'out_of_area',
            'msg' => 'ပို့ဆောင်နိုင်သော ဧရိယာ မဟုတ်ပါ (ဆိုင်နှင့် ' . round($km, 2) . ' km ဝေးနေပါသည်)',
            'km' => round($km, 2),
            'address' => $location['address']
        ];
    }

    return [
        'status' => 'success',
        'km' => round($km, 2),
        'address' => $location['address']
    ];
}

function getLatLngFromPostal($zip)
{
    // ✅ FIXED METHOD
    $api_url = 'https://geoapi.heartrails.com/api/json?method=searchByPostal&postal=' . $zip;

    $context = stream_context_create([
        'http' => ['timeout' => 5],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    $json = file_get_contents($api_url, false, $context);
    if ($json === false) {
        return false;
    }

    $data = json_decode($json, true);
    if (!isset($data['response']['location'][0])) {
        return false;
    }

    $loc = $data['response']['location'][0];

    return [
        'lat' => (float)$loc['y'],
        'lng' => (float)$loc['x'],
        'address' => $loc['prefecture'] . $loc['city'] . $loc['town']
    ];
}

function calculateDistance($lat1, $lng1, $lat2, $lng2)
{
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat / 2) ** 2 +
         cos(deg2rad($lat1)) *
         cos(deg2rad($lat2)) *
         sin($dLng / 2) ** 2;

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return EARTH_RADIUS_KM * $c;
}
