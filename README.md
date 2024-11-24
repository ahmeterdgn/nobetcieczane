# Eczane Scraper Kütüphanesi

**Eczane Scraper**, Türkiye'deki eczanelerin listesini ve detaylı bilgilerini kolayca çekebilmenizi sağlayan bir PHP kütüphanesidir.

---

## Özellikler (Features)

- **Eczane Listesi Çekimi**: Seçtiğiniz şehirdeki nöbetçi eczanelerin listesini alabilirsiniz.
- **Detaylı Bilgi**: Bir eczanenin telefon, adres, harita konumu gibi detaylarını kolayca çekin.
- **Hızlı ve Güvenilir**: Verileri çekerken cURL kullanır, UTF-8 desteklidir ve hataları düzgün bir şekilde ele alır.

---

## Kurulum (Installation)

Bu kütüphaneyi projelerinize dahil etmek için aşağıdaki adımları izleyin:

### Composer ile Yükleme (Install with Composer)
```bash
composer require ahmeterdgn/nobetcieczane
```
### Manuel Kurulum (Manual Installation)
#### 1.	Bu depoyu indirin:
```bash
git clone https://github.com/uzmoon/eczane-scraper.git
```
#### 2.	Projenize dahil edin:
```php
require 'path/to/EczaneScraper.php';
```

## Kullanım (Usage)

#### Eczane Listesi Çekme (Fetching Pharmacy List)
```php
require 'vendor/autoload.php';

use Uzmoon\EczaneScraper;

$scraper = new EczaneScraper('istanbul');
$data = $scraper->scrape('https://example.com/?city=istanbul&link=');
print_r($data);
```
#### Detaylı Bilgi Çekme (Fetching Detailed Info)
```php
require 'vendor/autoload.php';

use Uzmoon\EczaneScraper;

$scraper = new EczaneScraper('ankara');
$details = $scraper->detail('eczane-link');
print_r($details);
```
## Geri Bildirim (Feedback)

Kütüphaneyi kullanırken karşılaştığınız hataları bildirmek veya geliştirme önerilerinde bulunmak için [GitHub Issues](https://github.com/ahmeterdgn/nobetcieczane/issues) sayfasını kullanabilirsiniz.

- **Hata Raporlama**: Karşılaştığınız hataları GitHub Issues üzerinden bildirebilirsiniz. Hata raporlarınızı daha hızlı çözebilmemiz için aşağıdaki bilgileri eklemeyi unutmayın:
    - Hata mesajı veya ekran görüntüsü
    - Adım adım nasıl tekrar edileceği (bug’un nasıl tekrarlanabileceğine dair talimatlar)
    - Kullandığınız PHP versiyonu ve işletim sistemi

- **Özellik İstekleri ve İyileştirme Teklifleri**: Kütüphaneye yeni özellikler eklemek veya mevcut özellikleri geliştirmek için GitHub Issues sayfasına önerilerinizi yazabilirsiniz.
## Lisans (License)
MIT License

Copyright (c) 2024 Ahmet Erdoğan

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

## İletişim (Contact)

**Yazar**: Ahmet Erdoğan  
**Websitesi**: [uzmoon.com](https://uzmoon.com)  
**GitHub**: [Ahmeterdgn](https://github.com/ahmeterdgn)  
**E-posta**: [ahmet@uzmoon.com](mailto:ahmet@uzmoon.com)
