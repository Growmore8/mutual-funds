# Deploy — GrowthCapital Mutual Funds (mutualfunds.growthcapitalltd.com)

Laravel 12 app, deployed as a CloudPanel **PHP site** on the existing VPS
(`187.127.106.13`), with its own MySQL database. The server's PHP has the zip
extension, so `composer install` works normally. Built front-end assets are
committed (`public/build`), so **Node is not required** on the server.

## 1. DNS
Add an A record:
```
A   funds   187.127.106.13
```

## 2. Create the site (CloudPanel → + Add Site → Create a PHP Site)
- Application: **Generic**  ·  Domain: **mutualfunds.growthcapitalltd.com**  ·  PHP: **8.3**
- Site User: **funds** (save the generated password)

## 3. Create the database (CloudPanel → site → Databases → Add)
- Name: `mutual_funds`  ·  User: `mutual_funds_user`  ·  save the password.

## 4. Deploy the code (SSH / Hostinger terminal)
```bash
cd /home/funds/htdocs/mutualfunds.growthcapitalltd.com
rm -rf ./* ./.[!.]* 2>/dev/null
git clone https://github.com/Growmore8/mutual-funds.git .
composer install --no-dev --optimize-autoloader
cp .env.example .env
```

## 5. Configure `.env`
```bash
nano .env
```
Set:
```
APP_NAME="GrowthCapital Funds"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mutualfunds.growthcapitalltd.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mutual_funds
DB_USERNAME=mutual_funds_user
DB_PASSWORD=YOUR_DB_PASSWORD

MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=mail.privateemail.com
MAIL_PORT=465
MAIL_USERNAME=support@growthcapitalltd.com
MAIL_PASSWORD="Kk200121@"
MAIL_FROM_ADDRESS="support@growthcapitalltd.com"
MAIL_FROM_NAME="GrowthCapital Mutual Funds"

POOL_API_URL=
POOL_API_KEY=ck_live_89343336313ff2c0e043583435d7a3fc7ff287cca0d19071
```

## 6. Initialise
```bash
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
php artisan config:cache && php artisan route:cache
chown -R funds:funds /home/funds/htdocs/mutualfunds.growthcapitalltd.com
chmod -R 775 storage bootstrap/cache
```

## 7. Web root → /public  (CloudPanel → site → Vhost)
Change the `{{root}}` / `root` to:
```
root /home/funds/htdocs/mutualfunds.growthcapitalltd.com/public;
```
The default Laravel Nginx rules (try_files … /index.php) are already present.

## 8. SSL
CloudPanel → site → SSL/TLS → New Let's Encrypt Certificate.

## 9. First login
Admin: `admin@growthcapitalltd.com` / `ChangeMe!2026` → **change the password** immediately (Profile).

## Updating later (Phases 4–5)
```bash
cd /home/funds/htdocs/mutualfunds.growthcapitalltd.com
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache
chown -R funds:funds .
```
