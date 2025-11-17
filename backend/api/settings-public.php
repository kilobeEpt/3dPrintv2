<?php

$db = Database::getInstance();

$settings = $db->fetchOne('SELECT * FROM site_settings LIMIT 1');

if (!$settings) {
    Response::success([
        'materialPrices' => [],
        'servicePrices' => [],
        'qualityMultipliers' => [],
        'discounts' => []
    ]);
    return;
}

// Parse JSON fields if they exist
$result = [
    'materialPrices' => [],
    'servicePrices' => [],
    'qualityMultipliers' => [],
    'discounts' => []
];

// If social_links or notifications exist as JSON, decode them
if (isset($settings['social_links'])) {
    $result['social_links'] = json_decode($settings['social_links'], true);
}
if (isset($settings['notifications'])) {
    $result['notifications'] = json_decode($settings['notifications'], true);
}

Response::success($result);
