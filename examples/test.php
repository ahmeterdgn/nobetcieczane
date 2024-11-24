<?php

use nobetcieczane\EczaneScraper;
include '../vendor/autoload.php';

try {
    $city = filter_input(INPUT_GET, 'city', FILTER_SANITIZE_STRING) ?? 'istanbul';

    $scraper = new EczaneScraper($city);

    header('Content-Type: application/json; charset=utf-8');

    if (isset($_GET['link'])) {
        $link = filter_input(INPUT_GET, 'link', FILTER_SANITIZE_URL);
        $response = $scraper->detail($link);
    }
    elseif (isset($_GET['city'])) {
        $domain = $_SERVER['HTTP_HOST'];

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $fullUrl = $protocol . "://" . $domain;

        $response = $scraper->scrape($fullUrl.'/eczane/?city=' . urlencode($city) . '&link=');
    }
    else {
        $cities = $scraper->cities();
        $response = generateCityListHtml($cities);
        header('Content-Type: text/html; charset=utf-8');
    }

    echo is_array($response) ? json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $response;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
    ]);
}

/**
 * Generate the city list in HTML format
 *
 * @param array $cities Array of city keys and values
 * @return string HTML output
 */
function generateCityListHtml(array $cities): string
{
    $html = '<h1>Şehir Listesi</h1>';
    $html .= '<ul>';
    foreach ($cities as $key => $item) {
        $html .= '<li><a href="?city=' . htmlspecialchars($key) . '">' . htmlspecialchars($item) . '</a></li>';
    }
    $html .= '</ul>';
    return $html;
}