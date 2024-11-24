<?php

namespace nobetcieczane;

use Exception;
use InvalidArgumentException;

/**
 * Eczane Scraper Class
 * Provides functionality to scrape and process pharmacy data.
 *
 * @author Ahmet Erdoğan
 * @license MIT
 * @link https://uzmoon.com
 */
class EczaneScraper
{
    private string $city;
    private string $url;
    private string $baseUrl = 'https://{city}.eczaneleri.org/';
    private array $data = [];

    /**
     * Constructor to initialize the scraper.
     *
     * @param string $city Default city is 'istanbul'.
     * @throws InvalidArgumentException
     */
    public function __construct(string $city = 'istanbul')
    {
        $this->setCity($city);
    }

    /**
     * Sets the city and updates the base URL.
     *
     * @param string $city
     * @throws InvalidArgumentException
     */
    private function setCity(string $city): void
    {
        $city = strtolower(trim($city));
        if (empty($city)) {
            throw new InvalidArgumentException("City name cannot be empty.");
        }
        $this->city = $city;
        $this->url = str_replace('{city}', $this->city, $this->baseUrl);
    }

    /**
     * Fetches HTML content using cURL.
     *
     * @param string $url
     * @return string
     * @throws Exception
     */
    private function fetchHtml(string $url): string
    {
        $ch = curl_init($url);
        if (!$ch) {
            throw new Exception("Failed to initialize cURL session.");
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'] ?? 'EczaneScraperBot/1.0',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
                'Accept-Charset: UTF-8',
            ],
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

        return mb_convert_encoding($html, 'UTF-8', 'auto');
    }

    /**
     * Scrapes the pharmacy data from the main page.
     *
     * @param string $detailUrl
     * @return array
     */
    public function scrape(string $detailUrl): array
    {
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
                $this->processElement($element, $detailUrl);
            }

            $this->data['info']['count'] = count($this->data['list']);
            return $this->data;

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Processes a single pharmacy list item.
     *
     * @param object $element
     * @param string $detailUrl
     * @throws Exception
     */
    private function processElement($element, string $detailUrl): void
    {
        $mediaBody = $element->find('.media-body', 0);
        if (!$mediaBody) {
            throw new Exception("Missing media-body element in list item.");
        }

        $nameWithSpan = $mediaBody->find('a h4', 0)->innertext ?? '';
        $link = $mediaBody->find('a', 0)->href ?? '';

        $name = trim(preg_replace('/<span[^>]*>.*?<\/span>/i', '', $nameWithSpan));
        preg_match_all('/<span[^>]*>(.*?)<\/span>/i', $nameWithSpan, $matches);
        $spanTexts = $matches[1] ?? [];

        $address = trim(preg_replace('/\s+/', ' ', str_replace($spanTexts, '', $mediaBody->plaintext ?? '')));

        $this->data['list'][] = [
            'name' => $name,
            'districts' => $spanTexts,
            'address' => $address,
            'link' => $detailUrl . $link,
        ];
    }

    /**
     * Retrieves detailed information for a specific URL.
     *
     * @param string $url
     * @return array
     */
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
     * Returns a list of Turkish cities.
     *
     * @return array
     */
    public function cities(): array
    {
        return [
            'adana' => 'Adana',
            'adiyaman' => 'Adıyaman',
            'afyon' => 'Afyon',
            'agri' => 'Ağrı',
            'amasya' => 'Amasya',
            'ankara' => 'Ankara',
            'antalya' => 'Antalya',
            'artvin' => 'Artvin',
            'aydin' => 'Aydın',
            'balikesir' => 'Balıkesir',
            'bilecik' => 'Bilecik',
            'bingol' => 'Bingöl',
            'bitlis' => 'Bitlis',
            'bolu' => 'Bolu',
            'burdur' => 'Burdur',
            'bursa' => 'Bursa',
            'canakkale' => 'Çanakkale',
            'cankiri' => 'Çankırı',
            'corum' => 'Çorum',
            'denizli' => 'Denizli',
            'diyarbakir' => 'Diyarbakır',
            'edirne' => 'Edirne',
            'elazig' => 'Elazığ',
            'erzincan' => 'Erzincan',
            'erzurum' => 'Erzurum',
            'eskisehir' => 'Eskişehir',
            'gaziantep' => 'Gaziantep',
            'giresun' => 'Giresun',
            'gumushane' => 'Gümüşhane',
            'hakkari' => 'Hakkâri',
            'hatay' => 'Hatay',
            'isparta' => 'Isparta',
            'mersin' => 'Mersin',
            'istanbul' => 'İstanbul',
            'izmir' => 'İzmir',
            'kars' => 'Kars',
            'kastamonu' => 'Kastamonu',
            'kayseri' => 'Kayseri',
            'kirklareli' => 'Kırklareli',
            'kirsehir' => 'Kırşehir',
            'kocaeli' => 'Kocaeli',
            'konya' => 'Konya',
            'kutahya' => 'Kütahya',
            'malatya' => 'Malatya',
            'manisa' => 'Manisa',
            'kahramanmaras' => 'Kahramanmaraş',
            'mardin' => 'Mardin',
            'mugla' => 'Muğla',
            'mus' => 'Muş',
            'nevsehir' => 'Nevşehir',
            'nigde' => 'Niğde',
            'ordu' => 'Ordu',
            'rize' => 'Rize',
            'sakarya' => 'Sakarya',
            'samsun' => 'Samsun',
            'siirt' => 'Siirt',
            'sinop' => 'Sinop',
            'sivas' => 'Sivas',
            'tekirdag' => 'Tekirdağ',
            'tokat' => 'Tokat',
            'trabzon' => 'Trabzon',
            'tunceli' => 'Tunceli',
            'sanliurfa' => 'Şanlıurfa',
            'usak' => 'Uşak',
            'van' => 'Van',
            'yozgat' => 'Yozgat',
            'zonguldak' => 'Zonguldak',
            'aksaray' => 'Aksaray',
            'bayburt' => 'Bayburt',
            'karaman' => 'Karaman',
            'kirikkale' => 'Kırıkkale',
            'batman' => 'Batman',
            'sirnak' => 'Şırnak',
            'bartin' => 'Bartın',
            'ardahan' => 'Ardahan',
            'igdir' => 'Iğdır',
            'yalova' => 'Yalova',
            'karabuk' => 'Karabük',
            'kilis' => 'Kilis',
            'osmaniye' => 'Osmaniye',
            'duzce' => 'Düzce'
        ];
    }

    /**
     * Returns a standard error response.
     *
     * @param string $message
     * @return array
     */
    private function errorResponse(string $message): array
    {
        return [
            'status' => 'error',
            'message' => $message,
        ];
    }
}