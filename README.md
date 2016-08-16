# Indonesia Logo

Merupakan package pendukung dari [Laravolt\Indonesia](https://github.com/laravolt/indonesia)  
Dengan package ini, lokasi-lokasi dari `Laravolt\Indonesia` dapat menampilkan logonya.  
Lokasi yang memiliki logo: Province, City.  
Data logo didapatkan dengan crawling ke `Wikipedia`.

### Instalasi

`composer require laravolt/indonesia-logo`

`php artisan vendor:publish --provider="Laravolt\IndonesiaLogo\ServiceProvider" --force`

### Crawling manual

Untuk melakukan crawling sendiri, panggil
`php artisan laravolt:indonesia-logo:crawl`  
File logo akan langsung ditambahkan ke folder `public/indonesia-logo`
