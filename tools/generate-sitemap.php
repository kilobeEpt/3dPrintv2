#!/usr/bin/env php
<?php
/**
 * Sitemap Generator for 3D Print Pro
 * 
 * Генерирует sitemap.xml для статического сайта с учетом всех публичных страниц и якорей
 * Запуск: php tools/generate-sitemap.php [base_url]
 * 
 * @version 1.0.0
 */

// Определяем базовый URL
$baseUrl = $argv[1] ?? 'https://3dprint-omsk.ru';
$baseUrl = rtrim($baseUrl, '/');

// Определяем структуру сайта
$urls = [
    // Главная страница (с максимальным приоритетом)
    [
        'loc' => $baseUrl . '/',
        'changefreq' => 'daily',
        'priority' => '1.0',
        'lastmod' => date('Y-m-d')
    ],
    
    // Основные разделы через якоря
    [
        'loc' => $baseUrl . '/#home',
        'changefreq' => 'daily',
        'priority' => '1.0',
        'lastmod' => date('Y-m-d')
    ],
    [
        'loc' => $baseUrl . '/#services',
        'changefreq' => 'weekly',
        'priority' => '0.9',
        'lastmod' => date('Y-m-d')
    ],
    [
        'loc' => $baseUrl . '/#calculator',
        'changefreq' => 'weekly',
        'priority' => '0.8',
        'lastmod' => date('Y-m-d')
    ],
    [
        'loc' => $baseUrl . '/#portfolio',
        'changefreq' => 'weekly',
        'priority' => '0.9',
        'lastmod' => date('Y-m-d')
    ],
    [
        'loc' => $baseUrl . '/#about',
        'changefreq' => 'monthly',
        'priority' => '0.7',
        'lastmod' => date('Y-m-d')
    ],
    [
        'loc' => $baseUrl . '/#contact',
        'changefreq' => 'monthly',
        'priority' => '0.8',
        'lastmod' => date('Y-m-d')
    ],
];

// Проверка наличия файла index.html для определения даты последнего изменения
$indexFile = __DIR__ . '/../index.html';
if (file_exists($indexFile)) {
    $lastModified = date('Y-m-d', filemtime($indexFile));
    foreach ($urls as &$url) {
        $url['lastmod'] = $lastModified;
    }
}

// Генерация XML
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;

$urlset = $xml->createElement('urlset');
$urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
$xml->appendChild($urlset);

foreach ($urls as $urlData) {
    $url = $xml->createElement('url');
    
    $loc = $xml->createElement('loc', htmlspecialchars($urlData['loc']));
    $url->appendChild($loc);
    
    if (isset($urlData['lastmod'])) {
        $lastmod = $xml->createElement('lastmod', $urlData['lastmod']);
        $url->appendChild($lastmod);
    }
    
    if (isset($urlData['changefreq'])) {
        $changefreq = $xml->createElement('changefreq', $urlData['changefreq']);
        $url->appendChild($changefreq);
    }
    
    if (isset($urlData['priority'])) {
        $priority = $xml->createElement('priority', $urlData['priority']);
        $url->appendChild($priority);
    }
    
    $urlset->appendChild($url);
}

// Сохранение файла
$outputFile = __DIR__ . '/../sitemap.xml';
$xml->save($outputFile);

// Вывод информации
echo "✅ Sitemap generated successfully!\n";
echo "─────────────────────────────────────\n";
echo "Base URL: {$baseUrl}\n";
echo "Output file: {$outputFile}\n";
echo "Total URLs: " . count($urls) . "\n";
echo "Last modified: " . ($lastModified ?? date('Y-m-d')) . "\n";
echo "\n";
echo "📋 Generated URLs:\n";
foreach ($urls as $urlData) {
    echo "  - {$urlData['loc']} (priority: {$urlData['priority']})\n";
}
echo "\n";
echo "🔍 Validation:\n";
echo "  - Online: https://www.xml-sitemaps.com/validate-xml-sitemap.html\n";
echo "  - Google: https://search.google.com/search-console\n";
echo "  - Yandex: https://webmaster.yandex.ru/\n";
echo "\n";

exit(0);
