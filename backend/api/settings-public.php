<?php

$db = Database::getInstance();

$settings = $db->fetchOne('SELECT calculator_config FROM site_settings LIMIT 1');

if (!$settings) {
    Response::success([
        'materialPrices' => [],
        'servicePrices' => [],
        'qualityMultipliers' => [],
        'discounts' => []
    ]);
}

$config = json_decode($settings['calculator_config'], true);

Response::success($config);
