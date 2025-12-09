# Rješenje problema s veličinom fajlova pri zaprimanju

## Problem

Trenutno PHP postavke dozvoljavljaju upload fajlova do maksimalno **8 MB**. Pokušaji uploada većih fajlova rezultiraju greškom:

```
POST Content-Length of 9107465 bytes exceeds the limit of 8388608 bytes
```

## Rješenje: Povećanje PHP limita

### Opcija 1: PHP-FPM Konfiguracija (preporučeno)

1. **Pronađi php.ini fajl:**
   ```bash
   # Za PHP 8.1
   sudo nano /etc/php/8.1/fpm/php.ini

   # Za PHP 8.0
   sudo nano /etc/php/8.0/fpm/php.ini

   # Za PHP 7.4
   sudo nano /etc/php/7.4/fpm/php.ini
   ```

2. **Promijeni sljedeće postavke:**
   ```ini
   upload_max_filesize = 50M
   post_max_size = 50M
   max_execution_time = 300
   max_input_time = 300
   memory_limit = 256M
   ```

3. **Restartaj PHP-FPM:**
   ```bash
   # Za PHP 8.1
   sudo systemctl restart php8.1-fpm

   # Za PHP 8.0
   sudo systemctl restart php8.0-fpm

   # Za PHP 7.4
   sudo systemctl restart php7.4-fpm
   ```

4. **Restartaj web server (opciono):**
   ```bash
   sudo systemctl restart nginx
   # ili
   sudo systemctl restart apache2
   ```

### Opcija 2: .htaccess (za Apache)

Dodaj u root direktoriju `.htaccess` fajl:

```apache
php_value upload_max_filesize 50M
php_value post_max_size 50M
php_value max_execution_time 300
php_value max_input_time 300
php_value memory_limit 256M
```

**Napomena:** Ovo može ne raditi ako je `AllowOverride` isključen u Apache konfiguraciji.

### Opcija 3: Nginx konfiguracija (ako koristiš Nginx)

Dodaj u Nginx server block:

```nginx
client_max_body_size 50M;
```

Primjer:
```nginx
server {
    listen 80;
    server_name example.com;

    client_max_body_size 50M;

    # ostale postavke...
}
```

Zatim restartaj Nginx:
```bash
sudo systemctl restart nginx
```

## Provjera

Nakon promjena, možeš provjeriti da li su postavke primijenjene:

1. **Kreiraj `phpinfo.php` fajl:**
   ```php
   <?php phpinfo(); ?>
   ```

2. **Otvori u browseru:**
   ```
   https://seup-demo.8core.hr/phpinfo.php
   ```

3. **Traži sljedeće vrijednosti:**
   - `upload_max_filesize`
   - `post_max_size`
   - `max_execution_time`
   - `memory_limit`

4. **Obriši `phpinfo.php` nakon provjere** (sigurnosni razlog)

## Klijentska validacija

Sistem sada ima klijentsku validaciju koja upozorava korisnika prije uploada fajlova većih od 8 MB. Nakon što povećaš PHP limite, možeš promijeniti limit u JavaScript fajlu:

**Fajl:** `verzija_5-2-4/seup/js/zaprimanja.js`

```javascript
// Promijeni ovu liniju (trenutno je 8 MB):
const maxFileSize = 8 * 1024 * 1024;

// Na željenu veličinu (npr. 50 MB):
const maxFileSize = 50 * 1024 * 1024;
```

## Testiranje

Nakon promjena:

1. Osvježi stranicu (Ctrl+Shift+R)
2. Pokušaj uploadati fajl veći od 8 MB
3. Sistem bi trebao uspješno zaprimiti dokument

Ako i dalje imaš problema, provjeri error log:
```bash
tail -f /var/log/apache2/error.log
# ili
tail -f /var/log/nginx/error.log
```
