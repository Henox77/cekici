# cekici

## 
Yol Destek, harita destekli bir çekici randevu sistemidir. Hızlıca kurup çalıştırabilirsiniz; harita olarak Google Maps veya ücretsiz Leaflet+OSM kullanır, mesafe hesaplamada OSRM'den gerçek rota mesafesi alır.

## Öne çıkanlar
- PHP + PDO, XAMPP/LAMP uyumlu
- Haritadan alış/bırakış seçimi (Google Maps / Leaflet+OSM)
- Gerçek rota mesafesi için OSRM (public) entegrasyonu
- Fiyatlama: km başına ücret (varsayılan 40 TL/km)
- Basit admin panel, raporlama ve dışa aktarma

## Hızlı kurulum
1) Projeyi `c:\xampp\htdocs` altına kopyalayın.
2) `config/db.php` içindeki DB bilgilerini ayarlayın.
3) Tarayıcıda `http://localhost/index.php` açın. Admin: `http://localhost/admin/index.php` (kullanıcı: admin / admin123).

- Google Maps API anahtarı yoksa otomatik Leaflet + OSM + Nominatim çalışır.
- Varsayılan OSRM: `https://router.project-osrm.org` (üretimde kendi OSRM sunucunuzu kurmanızı tavsiye ederim).
- Fiyatı değiştirmek için `index.php` içindeki `PER_KM_RATE` ve `config/security.php`'deki fonksiyonu güncelleyin.
