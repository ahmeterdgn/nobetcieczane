<?php
namespace nobetcieczane;
/**
 * Eczane Scraper Class
 * @author Ahmet Erdoğan
 * @web https://uzmoon.com
 */
include 'simple_html_dom.php';

class EczaneScraper {
    private $city;
    private $url;
    private $baseUrl = 'https://{city}.eczaneleri.org/';
    private $data = [];

    /**
     * EczaneScraper constructor.
     * @param string $city
     */
    public function __construct($city = 'istanbul') {
        $this->setCity($city);
    }

    /**
     * Set the city and build the URL
     * @param string $city
     */
    private function setCity($city) {
        $this->city = strtolower(trim($city));
        if (empty($this->city)) {
            throw new InvalidArgumentException("City name cannot be empty.");
        }
        $this->url = str_replace('{city}', $this->city, $this->baseUrl);
    }

    /**
     * Fetch HTML content using cURL
     * @param string $url
     * @return string|null
     * @throws Exception
     */
    private function fetchHtml($url) {
        $ch = curl_init($url);
        if (!$ch) {
            throw new Exception("Failed to initialize cURL session.");
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Accept-Charset: UTF-8',
        ]);

        $html = curl_exec($ch);

        if (curl_errno($ch)) {
            $errorMessage = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error: $errorMessage");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: Received status code $httpCode");
        }

        // Ensure UTF-8 encoding
        if (mb_detect_encoding($html, 'UTF-8', true) !== 'UTF-8') {
            $html = mb_convert_encoding($html, 'UTF-8', 'auto');
        }

        return $html;
    }

    /**
     * Scrape the data from the main page
     * @return array
     */
    public function scrape() {
        try {
            $htmlContent = $this->fetchHtml($this->url);
            $html = str_get_html($htmlContent);

            if (!$html) {
                throw new Exception("HTML Parsing Error: Could not parse the HTML content.");
            }

            $this->data['status'] = 'success';
            $this->data['info']['date'] = $html->find('.active .alert', 0)->plaintext ?? '';

            $this->data['list'] = [];
            foreach ($html->find('.active .media-list li') as $element) {
                $this->processElement($element);
            }

            $this->data['info']['count'] = count($this->data['list']);
            return $this->data;

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Process each element in the list
     * @param object $element
     * @throws Exception
     */
    private function processElement($element) {
        try {
            $mediaBody = $element->find('.media-body', 0);
            if (!$mediaBody) {
                throw new Exception("Missing media-body element in list item.");
            }

            $nameWithSpan = $mediaBody->find('a h4', 0)->innertext ?? '';
            $link = $mediaBody->find('a', 0)->href ?? '';
            preg_match_all('/<span[^>]*>(.*?)<\/span>/i', $nameWithSpan, $matches);
            $spanTexts = $matches[1] ?? [];
            $name = trim(preg_replace('/<span[^>]*>.*?<\/span>/i', '', $nameWithSpan));

            $address = trim($mediaBody->plaintext ?? '');
            foreach ($spanTexts as $spanText) {
                $address = str_replace($spanText, '', $address);
            }
            $address = trim(preg_replace('/\s+/', ' ', $address));

            $this->data['list'][] = [
                'name' => $name,
                'districts' => $spanTexts,
                'address' => $address,
                'link' => 'https://heyburada.arnclothing.tech/eczane/?city=mugla&link='.$link,
            ];
        } catch (Exception $e) {
            throw new Exception("Error processing list item: " . $e->getMessage());
        }
    }

    /**
     * Get detailed information for a specific URL
     * @param string $url
     * @return array
     */
    public function detail($url) {
        try {
            $u = $this->url . $url;
            $htmlContent = $this->fetchHtml($u);
            $html = str_get_html($htmlContent);

            if (!$html) {
                throw new Exception("Failed to parse HTML content.");
            }

            $infoDiv = $html->find('.pull-left', 0);
            $phone = $address = null;

            if ($infoDiv) {
                $textContent = $infoDiv->innertext;
                preg_match('/Telefon\s*:\s*([\d]+)/i', $textContent, $phoneMatch);
                $phone = $phoneMatch[1] ?? null;

                preg_match('/Adres\s*:\s*(.*?)<br>/i', $textContent, $addressMatch);
                $address = isset($addressMatch[1]) ? strip_tags($addressMatch[1]) : null;
            }

            $navigateButton = $html->find('#navigationRoadBtn', 0);
            $latitude = $longitude = null;

            if ($navigateButton) {
                $latitude = $navigateButton->getAttribute('lat') ?? null;
                $longitude = $navigateButton->getAttribute('lng') ?? null;
            }

            $image = null;
            $imageCanvas = $html->find('#map-canvas', 0);

            if ($imageCanvas) {
                $imageSrc = $imageCanvas->find('img', 0)->getAttribute('src');
                $image = strpos($imageSrc, 'http') === false ? $this->url . $imageSrc : $imageSrc;
            }

            return [
                'status' => 'success',
                'detail' => [
                    'phone' => $phone,
                    'address' => trim($address),
                    'navigate' => ($latitude && $longitude) ? "https://www.google.com/maps?daddr={$latitude},{$longitude}" : null,
                    'image' => $image,
                ]
            ];

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Return error response
     * @param string $message
     * @return array
     */
    private function errorResponse($message) {
        return [
            'status' => 'error',
            'message' => $message,
        ];
    }
}