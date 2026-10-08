# power-app – installation guide

How to install the Power Installation website (public site in NL/FR/EN plus the `/admin` panel) on a new server, from an empty machine to a working HTTPS site.

- **Stack:** Laravel 13, Filament 5, PHP 8.4, MySQL/MariaDB, nginx, Vite/Tailwind for the front-end build.
- **Example names in this guide:** domain `www.example.be`, install path `/var/www/power-app`, database `power`, database user `power`. Replace them with your own values.
- All commands run as `root` unless they start with `sudo -u www-data`.

---

## 1. Requirements

| Component | Version / note |
|---|---|
| OS | Ubuntu 24.04 LTS or Debian 12 (other Linux works, package names may differ) |
| PHP | **8.4** (8.3 minimum) with FPM |
| PHP extensions | bcmath, ctype, curl, dom, fileinfo, gd, intl, mbstring, mysql (pdo_mysql), openssl, tokenizer, xml, zip |
| Database | MySQL 8.0+ or MariaDB 10.6+ |
| Web server | nginx |
| Composer | 2.x |
| Node.js | 20+ with npm – only needed to build the front-end assets (can be done on another machine) |
| Other | git, unzip, certbot (Let's Encrypt) |
| Network | Outbound internet during the build: `npm run build` downloads the Inter / JetBrains Mono fonts from fonts.bunny.net |

`intl` is required by Filament (the admin panel will not load without it).

---

## 2. Install the server packages

### 2.1 PHP 8.4

Ubuntu 24.04 ships PHP 8.3; PHP 8.4 comes from the ondrej PPA:

```bash
apt update
apt install -y software-properties-common ca-certificates curl unzip git
add-apt-repository -y ppa:ondrej/php        # Debian: use https://packages.sury.org/php instead
apt update
apt install -y php8.4-fpm php8.4-cli php8.4-bcmath php8.4-curl php8.4-gd php8.4-intl \
               php8.4-mbstring php8.4-mysql php8.4-xml php8.4-zip php8.4-opcache
php8.4 -v
php8.4 -m | grep -E 'intl|pdo_mysql|mbstring|gd|zip'
```

### 2.2 nginx, database, Composer, Node.js, certbot

```bash
apt install -y nginx mysql-server certbot python3-certbot-nginx     # or: mariadb-server

# Composer
curl -sS https://getcomposer.org/installer | php8.4 -- --install-dir=/usr/local/bin --filename=composer
composer --version

# Node.js 22 LTS (only on the machine that builds the assets)
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt install -y nodejs
node -v && npm -v
```

---

## 3. Database

```bash
mysql -u root
```

```sql
CREATE DATABASE power CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'power'@'localhost' IDENTIFIED BY 'CHANGE-ME-strong-password';
GRANT ALL PRIVILEGES ON power.* TO 'power'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

If the database runs on another server, create the user as `'power'@'<web server IP>'` and allow the web server through the database firewall (port 3306).

> Use a database of its own for this app. Never run `migrate:fresh` against a database shared with other applications: it drops **all** tables in it.

---

## 4. Get the code

The app lives in the `power-app` folder of the repository.

```bash
mkdir -p /var/www
cd /var/www
git clone https://github.com/JLTechologies/Stock-Rewrite.git stock-rewrite
ln -s /var/www/stock-rewrite/power-app /var/www/power-app     # or move/copy the folder
```

The repository is private: use a GitHub deploy key or a personal access token for the clone.

Not in git (and therefore created during the install): `.env`, `vendor/`, `node_modules/`, `public/build/`, `public/storage` and everything uploaded to `storage/`.

---

## 5. Configuration (`.env`)

```bash
cd /var/www/power-app
cp .env.example .env
nano .env
```

Set at least these values:

```dotenv
APP_NAME="Power Installation NV"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.example.be
APP_LOCALE=nl
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=nl_BE

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=power
DB_USERNAME=power
DB_PASSWORD="CHANGE-ME-strong-password"

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

# Mail: can stay on "log" here; the real SMTP settings are entered in the admin panel
# (Settings > Email) and override these.
MAIL_MAILER=log
MAIL_FROM_ADDRESS="info@example.be"
MAIL_FROM_NAME="${APP_NAME}"
```

Notes:
- `QUEUE_CONNECTION=sync`: the app sends its e-mails (contact form) directly, so no queue worker is needed.
- `SESSION_SECURE_COOKIE=true` requires HTTPS (step 10). While testing over plain HTTP, set it to `false`.
- `APP_KEY` is filled in by the next step. **Keep it safe**: the SMTP password stored in the admin settings is encrypted with it.

---

## 6. Install the application

```bash
cd /var/www/power-app

# PHP dependencies (production: without dev tools)
composer install --no-dev --optimize-autoloader --no-interaction

# Application key (only on a new install – never on a moved site, see section 13)
php8.4 artisan key:generate --force

# Front-end assets (Tailwind/Vite) -> public/build
npm ci
npm run build

# Livewire scripts are served as static files (see the nginx note in section 8)
php8.4 artisan vendor:publish --tag=livewire:assets --force

# Database tables (also creates the roles "Administrator" and "Editor")
php8.4 artisan migrate --force

# Public link for uploaded images (logo, projects, certificates…)
php8.4 artisan storage:link
```

**Example content (optional).** The seeders add sample expertises, projects (slugs `voorbeeld-*`) and a news post, which are handy to see the site filled in. Skip them for a clean site and enter the content in the admin panel instead:

```bash
php8.4 artisan db:seed --force
```

**No Node.js on the server?** Run `npm ci && npm run build` on another machine with the same code, then copy `public/build/` to the server.

---

## 7. First administrator

The app has no sign-up page. Create the first admin with the `power:create-user` command:

```bash
cd /var/www/power-app
php8.4 artisan power:create-user
```

The command asks for:
- the **role**: choose **Administrator** (full access, including users, roles and settings);
- the name and the e-mail address used to log in;
- the password;
- the language of the admin panel (NL/FR/EN).

All questions except the password can also be given as options, e.g. in a script:

```bash
php8.4 artisan power:create-user --name="Your Name" --email=you@example.be --role=Administrator --locale=nl
```

The password is always asked for interactively, so it never ends up in the shell history. Use `--role=Editor` for someone who only manages the content. More users can be added later in `/admin` > Users.

---

## 8. File permissions

The web server (PHP-FPM runs as `www-data`) must be able to write to `storage/` and `bootstrap/cache/`:

```bash
chown -R www-data:www-data /var/www/stock-rewrite/power-app
find /var/www/power-app/storage /var/www/power-app/bootstrap/cache -type d -exec chmod 775 {} \;
```

Run every later `artisan` command as root, then repeat the `chown` above. Alternatively run artisan as the web user (`sudo -u www-data php8.4 artisan …`), which avoids files owned by root.

### PHP-FPM sandbox (only when you hit a 500 with "read-only file system")

Some setups run php-fpm with systemd `ProtectSystem=full`, which makes `/usr` read-only. That is a problem when the app lives under `/usr/share/nginx/html/…`. Under `/var/www` it does not apply. If you do use such a path, allow writing to the two folders:

```bash
mkdir -p /etc/systemd/system/php8.4-fpm.service.d
cat > /etc/systemd/system/php8.4-fpm.service.d/power-app-writable.conf <<'EOF'
[Service]
ReadWritePaths=/usr/share/nginx/html/devops/power-app/storage /usr/share/nginx/html/devops/power-app/bootstrap/cache
EOF
systemctl daemon-reload && systemctl restart php8.4-fpm
```

### PHP upload limits

Certificates accept PDFs up to 10 MB. The nginx config below raises the PHP limits per site. If you prefer to set them globally, edit `/etc/php/8.4/fpm/php.ini`:

```ini
upload_max_filesize = 20M
post_max_size = 25M
```

then `systemctl restart php8.4-fpm`.

---

## 9. nginx

Create `/etc/nginx/sites-available/power-app`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name www.example.be example.be;
    root /var/www/power-app/public;

    index index.php;
    charset utf-8;
    client_max_body_size 100m;

    access_log /var/log/nginx/power-app.access.log;
    error_log  /var/log/nginx/power-app.error.log;

    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options SAMEORIGIN;
    add_header Referrer-Policy same-origin;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param PHP_VALUE "upload_max_filesize=100M \n post_max_size=100M";
    }

    # Static files: cache long, but fall back to Laravel when the file does not exist
    location ~* \.(?:ico|css|js|mjs|map|jpe?g|png|webp|gif|svg|woff2?)$ {
        try_files $uri /index.php?$query_string;
        expires 365d;
        access_log off;
    }

    # Keep Let's Encrypt working, block every other dot-file (.env, .git…)
    location ^~ /.well-known/acme-challenge/ { allow all; }
    location ~ /\.(?!well-known) { deny all; }
}
```

```bash
ln -s /etc/nginx/sites-available/power-app /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

> **Why the `try_files` in the static block matters:** without it, `.js`/`.css` URLs that Laravel or Livewire generate (instead of real files) return 404. That is also why section 6 publishes the Livewire scripts into `public/vendor/livewire`.

---

## 10. HTTPS

Point the DNS A/AAAA records of the domain to the server, then:

```bash
certbot --nginx -d www.example.be -d example.be --redirect
```

certbot adds the certificate lines and the HTTP→HTTPS redirect to the nginx config, and renews the certificate automatically.

---

## 11. Production optimisation

```bash
cd /var/www/power-app
php8.4 artisan optimize          # config, routes, views, events cache
php8.4 artisan filament:optimize # Filament component + icon cache
chown -R www-data:www-data /var/www/stock-rewrite/power-app
systemctl restart php8.4-fpm     # clears OPcache
```

After **every** code or `.env` change, run these four commands again: with the caches on, changes do not show until the caches are rebuilt.

---

## 12. First steps in the admin panel

Go to `https://www.example.be/admin` and log in with the account from section 7.

The panel uses each user's own language (set per user, and in the profile menu). The labels below are the English ones.

1. **Settings > General:** site name, founding year, taglines and hero texts (NL/FR/EN), key figures.
2. **Settings > Contact details:** address, phone, e-mail, VAT number, the address that receives contact-form messages, social media links.
3. **Settings > Email:** SMTP server, port, encryption, user and password, sender. Click **Send test email** to check.
4. **Settings > Appearance:** colours, logo, favicon, the homepage background picture behind the slogan (optional – without it the default navy background stays), optional custom CSS.
5. **Content:** expertises, projects, news, clients and certificates. Replace or delete the example content if you seeded it.
6. **Content > Who is who:** the people on the "who is who" page: name, job title per language, phone, mobile, e-mail and a photo (without a photo the website logo is shown). Drag the rows to change their order. The page appears in the menu as soon as one person is visible; switch the whole page off in **Settings > General > Pages**.
7. **Content > Footer links:** extra links (e.g. terms and conditions, vacancies, a partner site) that appear in their own row at the bottom of every page. The row only shows when at least one visible link exists. Each link has a text per language, an address (`https://…`, a page path like `/nl/contact`, `mailto:` or `tel:`) and an option to open in a new tab. Drag the rows to change their order.
8. **Users / Roles:** add editors. The "Editor" role manages content but not users or settings. "Who is who" and "Footer links" have their own permissions: tick them for every role that may manage them.

The public website is at `https://www.example.be/nl` (also `/fr` and `/en`).

---

## 13. Moving an existing site to the new server

Use this instead of a clean install when the content, users and uploads have to come along.

On the **old** server:

```bash
cd /path/to/power-app
mysqldump --single-transaction --routines -u <user> -p power > /tmp/power.sql
tar czf /tmp/power-uploads.tgz -C storage/app public      # uploaded logos, images, PDFs
grep ^APP_KEY= .env                                       # copy this value
```

On the **new** server, follow sections 1–6 but:
- **do not** run `key:generate`: put the old `APP_KEY` in `.env` (otherwise the stored SMTP password cannot be decrypted);
- import the database instead of `migrate`/`db:seed`, then run `migrate` anyway to apply any newer migrations:

```bash
mysql -u power -p power < /tmp/power.sql
tar xzf /tmp/power-uploads.tgz -C /var/www/power-app/storage/app
cd /var/www/power-app
php8.4 artisan migrate --force
php8.4 artisan storage:link
php8.4 artisan cache:clear
```

Then continue with sections 8–11. Change `APP_URL` if the domain changes.

---

## 14. Updating to a new version

```bash
cd /var/www/stock-rewrite
php8.4 /var/www/power-app/artisan down          # maintenance page
git pull
cd power-app
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php8.4 artisan vendor:publish --tag=livewire:assets --force
php8.4 artisan migrate --force
php8.4 artisan optimize && php8.4 artisan filament:optimize
chown -R www-data:www-data /var/www/stock-rewrite/power-app
systemctl restart php8.4-fpm
php8.4 artisan up
```

Back up the database before an update (`mysqldump`, see section 13).

---

## 15. Backups

Back up these three things; everything else can be rebuilt from git:

| What | How |
|---|---|
| Database | `mysqldump --single-transaction -u power -p power > power-$(date +%F).sql` |
| Uploads | `storage/app/public/` |
| Configuration | `.env` (contains `APP_KEY` and the database password) |

---

## 16. Troubleshooting

| Symptom | Cause / fix |
|---|---|
| HTTP 500, nothing in `storage/logs` | PHP cannot write to `storage/`: fix ownership (section 8), or add the systemd `ReadWritePaths` drop-in |
| 500 with *The "intl" PHP extension is required…* | `php8.4-intl` is missing: `apt install php8.4-intl && systemctl restart php8.4-fpm` |
| Admin pages load without styling / buttons do nothing | Livewire/Filament assets give 404: check the static `location` block (section 9), run `php8.4 artisan vendor:publish --tag=livewire:assets --force` and `php8.4 artisan filament:assets` |
| "Vite manifest not found" | The front-end was not built: `npm ci && npm run build` (or copy `public/build/`) |
| Uploaded images give 404 | `php8.4 artisan storage:link` is missing |
| Changes do not show up | Caches are on: `php8.4 artisan optimize:clear`, then `optimize` + `filament:optimize` again, then restart php8.4-fpm |
| Contact form mail not arriving | Check Settings > Email and use **Send test email**; errors are in `storage/logs/laravel.log` |
| Logged out immediately / 419 errors | `SESSION_SECURE_COOKIE=true` on a site without HTTPS, or a wrong `APP_URL` |

Logs: `storage/logs/laravel.log` (application), `/var/log/nginx/power-app.error.log` (nginx), `journalctl -u php8.4-fpm` (PHP-FPM).

---

## 17. Final checklist

- [ ] `https://www.example.be/nl` shows the website, `/fr` and `/en` switch language
- [ ] `https://www.example.be/admin` logs in with the first admin
- [ ] Uploading a logo in Settings > Appearance works and the logo appears on the site
- [ ] Settings > Email > **Send test email** arrives
- [ ] The contact form on the site arrives at the configured address
- [ ] `APP_ENV=production` and `APP_DEBUG=false` in `.env`
- [ ] Backups of the database, `storage/app/public` and `.env` are scheduled
