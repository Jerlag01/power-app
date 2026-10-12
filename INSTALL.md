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
| Node.js | 20+ with npm. Builds the front-end assets; needed on the server itself for updates from the admin panel (section 14.1) |
| Other | git, unzip, cron, certbot (Let's Encrypt) |
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
apt install -y nginx mysql-server certbot python3-certbot-nginx cron     # or: mariadb-server

# Composer
curl -sS https://getcomposer.org/installer | php8.4 -- --install-dir=/usr/local/bin --filename=composer
composer --version

# Node.js 22 LTS
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

The app has its own repository: **https://github.com/Jerlag01/power-app** (branch `main`).

```bash
mkdir -p /var/www
git clone https://github.com/Jerlag01/power-app.git /var/www/power-app
cd /var/www/power-app
git config --global --add safe.directory /var/www/power-app    # lets root run git in this folder later (it is owned by www-data)
```

The repository is private: use a GitHub deploy key (read-only, recommended for a server) or a personal access token for the clone.

Updates from the admin panel (section 14.1) run git as the `www-data` user, so that user needs read access too. For example, put the deploy key in `/var/www/.ssh/`, the home folder of `www-data`, owned by `www-data` with mode 600. Test it with `sudo -u www-data git -C /var/www/power-app ls-remote origin`.

Not in git (and therefore created during the install): `.env`, `vendor/`, `node_modules/`, `public/build/`, `public/storage` and everything uploaded to `storage/`.

> Older installs cloned the combined repository `JLTechologies/Stock-Rewrite` and linked its `power-app` folder. To move such an install to the new repository, see section 14.5.

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
- `QUEUE_CONNECTION=sync`: the app sends its e-mails (contact form, job applications) directly, so no queue worker is needed.
- Optional, for the updater (section 14): `UPDATER_REMOTE` (default `origin`), `UPDATER_BRANCH` (default: the branch that is checked out), `UPDATER_COMPOSER` and `UPDATER_NPM`. Set the last two to the full path only when composer or npm is not found in `/usr/local/bin` or `/usr/bin`.
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

# Public link for uploaded images (logo, projects, certificates, vacancies…)
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

The web server (PHP-FPM runs as `www-data`) must be able to write to `storage/` and `bootstrap/cache/`. The whole folder, `.git` included, must belong to `www-data`, because updates from the admin panel run as that user:

```bash
chown -R www-data:www-data /var/www/power-app
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

Certificate PDFs and the CVs sent with job applications can be up to 10 MB. The nginx config below raises the PHP limits per site. If you prefer to set them globally, edit `/etc/php/8.4/fpm/php.ini`:

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

## 11. Production optimisation and scheduler

### 11.1 Caches

```bash
cd /var/www/power-app
php8.4 artisan optimize          # config, routes, views, events cache
php8.4 artisan filament:optimize # Filament component + icon cache
chown -R www-data:www-data /var/www/power-app
systemctl restart php8.4-fpm     # clears OPcache
```

After **every** code or `.env` change, run these four commands again: with the caches on, changes do not show until the caches are rebuilt. An update through the admin panel or `app:update` (section 14) rebuilds them for you.

### 11.2 Scheduler (cron)

The Laravel scheduler runs the updates requested in the admin panel (section 14.1). Without it, **System update** can check for updates but never installs them. Add one cron line for the `www-data` user:

```bash
crontab -u www-data -e
```

```cron
* * * * * cd /var/www/power-app && php8.4 artisan schedule:run >> /dev/null 2>&1
```

Check it:

```bash
crontab -u www-data -l
sudo -u www-data php8.4 artisan schedule:list     # lists "updater-heartbeat" and "app:update --if-requested"
```

Within a minute, **System update** in the admin panel shows the scheduler as **Running**.

> Use the same PHP version as the website (`php8.4`), and put the line in the crontab of `www-data`, **not** root: as root, an update would leave root-owned files behind.

---

## 12. First steps in the admin panel

Go to `https://www.example.be/admin` and log in with the account from section 7.

The panel uses each user's own language (set per user, and in the profile menu). The labels below are the English ones.

1. **Settings > General:** site name, founding year, taglines and hero texts (NL/FR/EN), key figures.
2. **Settings > Contact details:**
   - address, phone, e-mail and VAT number;
   - the address that receives contact-form messages;
   - **Send applications to**: the default addresses for job applications (press Enter after each address);
   - social media links.
3. **Settings > Email:** SMTP server, port, encryption, user and password, sender. Click **Send test email** to check. Contact messages and job applications are only mailed once this works; they are always saved in the admin panel as well.
4. **Settings > Appearance:** colours, logo, favicon, the homepage background picture behind the slogan (optional – without it the default navy background stays), optional custom CSS.
5. **Content:** expertises, projects, news, clients and certificates. Replace or delete the example content if you seeded it.
6. **Content > Who is who:** the people on the "who is who" page: name, job title per language, phone, mobile, e-mail and a photo (without a photo the website logo is shown). Drag the rows to change their order. The page appears in the menu as soon as one person is visible; switch the whole page off in **Settings > General > Pages**.
7. **Content > Vacancies:** job openings.
   - **Content:** job title, short description, job description, requirements and "what we offer", per language.
   - **Details:** main photo plus a photo gallery, location, contract type, contact person (name, e-mail, phone) and an optional closing date.
   - **Send applications to:** the e-mail addresses that receive every application for this vacancy, with the CV attached. Empty = the addresses from Settings > Contact details, then the contact-form address.
   - **Visibility:** the menu and footer link **Vacancies** (NL "Vacatures", FR "Emplois") only appear while at least one vacancy is visible and not past its closing date. Without one, the vacancy pages return "not found". Drag the rows to change their order.
8. **Contact > Applications:** every application received through the website: name, e-mail, phone, motivation and the CV (button **Download CV**). Unread applications are bold, with a counter in the menu. Deleting an application also deletes its CV.
9. **Content > Footer links:** extra links (e.g. terms and conditions, a partner site) that appear in their own row at the bottom of every page.
   - The row only shows when at least one visible link exists.
   - Each link has a text per language, an address (`https://…`, a page path like `/nl/contact`, `mailto:` or `tel:`) and an option to open in a new tab.
   - Drag the rows to change their order.
10. **Users / Roles:** add editors.
    - The "Editor" role manages content but not users or settings.
    - "Who is who", "Footer links", "Vacancies" and "Applications" have their own permissions: tick them for every role that may manage them.
    - Applications contain CVs (personal data), so only give "Applications" to people who handle recruitment.
11. **System > System update** (super admins only): see section 14.1.
12. **System > Blocked IP addresses:** visitors whose IP address is on this list see an "Access denied" page (HTTP 410) instead of the public website. The admin panel stays reachable, so you cannot lock yourself out.
    - Each entry is one address (`203.0.113.7`, IPv6 works too) or a range (`203.0.113.0/24`; ranges wider than /16 for IPv4 or /32 for IPv6 are refused).
    - Optional: an internal reason, an end date after which the block stops by itself, and an on/off switch to pause a block without deleting it.
    - The list shows how many visits were refused and when the last one was. **Unblock** deletes the entry.
    - Every contact message stores the sender's IP address (shown under Sender, never in the e-mail). The **Block IP** button on a message opens this form already filled in.
    - Permission area "Blocked IP addresses"; not given to the Editor role.

The public website is at `https://www.example.be/nl` (also `/fr` and `/en`).

---

## 13. Moving an existing site to the new server

Use this instead of a clean install when the content, users and uploads have to come along.

On the **old** server:

```bash
cd /path/to/power-app
mysqldump --single-transaction --routines -u <user> -p power > /tmp/power.sql
tar czf /tmp/power-uploads.tgz -C storage/app public private   # uploaded logos, images, PDFs and the CVs
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

## 14. Updating to a new release

New releases are published on the `main` branch of https://github.com/Jerlag01/power-app. A release can also carry a tag (for example `v1.2.0`); then you can install exactly that release.

Before every update:
- Back up the database (`mysqldump`, see section 15) and keep the `.env`.
- See what the update brings: the **System update** page links to the changes on GitHub, or run `sudo -u www-data php8.4 artisan app:update --check`.
- Make sure nobody changed files on the server itself; every update method refuses to overwrite them:

  ```bash
  cd /var/www/power-app
  git status --short --untracked-files=no     # must print nothing
  ```

  If it lists files, move the change into the repository (commit and push from a development copy) or discard it with `git checkout -- <file>`. Never edit code directly on the server.

Sections 14.1 and 14.2 always install the newest commit of `main`. To install one tagged release instead, use section 14.3.

### 14.1 From the admin panel (recommended)

Requires the cron line from section 11.2.

1. Log in as a super admin and open **System > System update**.
2. The page shows:
   - the installed commit;
   - whether GitHub has a newer one (**Update available** / **Up to date**), with a link to the changes;
   - any local changes;
   - whether the scheduler is running;
   - the log of the last update.
3. **Check for updates** asks GitHub again. Otherwise the answer is cached for 10 minutes. A badge in the menu shows when an update is available.
4. **Update now** (confirm) queues the update; the scheduler starts it within a minute. The page refreshes itself and ends on **Succeeded**, **Was already up to date** or **Failed**, with the full log and the commits before and after (`abc1234 → def5678`).
5. **Cancel request** removes a queued update that has not started yet.

The website shows a maintenance page during the update, usually for less than a minute. A failed update brings the site back up and is not retried automatically: read the error on the page, fix the cause (section 16) and click **Update now** again.

The admin panel cannot update the code itself: PHP-FPM may not write the code folder (see the sandbox note in section 8). That is why it only queues the update and the scheduler, running as `www-data`, carries it out.

### 14.2 From the command line

The same update as 14.1, started by hand. Always run it as `www-data`:

```bash
cd /var/www/power-app
sudo -u www-data php8.4 artisan app:update --check   # read-only: installed and remote commit, update available or not
sudo -u www-data php8.4 artisan app:update           # update now
```

`app:update` performs these steps and stops at the first one that fails:

1. Refuse when tracked files were changed on the server.
2. `git fetch` the branch. Nothing new: stop here ("Already up to date").
3. `artisan down` (maintenance page).
4. `git merge --ff-only` to the new commit.
5. `composer install --optimize-autoloader` (with `--no-dev` when `APP_ENV=production`) and republish the Livewire assets.
6. `npm ci` when `package.json`/`package-lock.json` changed; `npm run build` when the front-end changed.
7. `artisan migrate --force`.
8. `artisan optimize` + `filament:optimize` in production, or clear those caches in development.
9. `artisan up`.

The result also appears on the **System update** page. The command refuses to run as root, because pulled files would become root-owned; `--allow-root` overrides that, but then run the `chown` from section 8 afterwards. A php8.4-fpm restart is not needed: OPcache notices the changed files by itself (unless `opcache.validate_timestamps` was switched off).

### 14.3 Manually

Without cron, without Node.js on the server, or to install one tagged release:

```bash
cd /var/www/power-app
git rev-parse --short HEAD                      # note the current version, for a rollback
php8.4 artisan down                             # maintenance page
git fetch --tags origin
git merge --ff-only origin/main                 # latest release
#   or, for one tagged release instead:  git checkout v1.2.0
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php8.4 artisan vendor:publish --tag=livewire:assets --force
php8.4 artisan migrate --force
php8.4 artisan optimize && php8.4 artisan filament:optimize
chown -R www-data:www-data /var/www/power-app
systemctl restart php8.4-fpm                    # clears OPcache
php8.4 artisan up
```

`git merge --ff-only` stops when files were changed on the server itself (`git status` shows them). Undo those changes (`git checkout -- <file>`) or move them into the repository first; never edit code directly on the server.

### 14.4 Going back to the previous version

The version before an update is in the log on the **System update** page (`abc1234 → def5678`: use `abc1234`), or the one you noted in 14.3.

```bash
cd /var/www/power-app
php8.4 artisan down
git reset --hard <the version noted before the update>    # stays on the main branch
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php8.4 artisan optimize && php8.4 artisan filament:optimize
chown -R www-data:www-data /var/www/power-app
systemctl restart php8.4-fpm
php8.4 artisan up
```

Database changes are **not** undone by this. When the update ran new migrations, either restore the database backup taken before the update, or run `php8.4 artisan migrate:rollback --step=<number of new migrations>` *before* the `git reset`, while the new code is still there.

`git reset --hard` keeps the folder on the `main` branch, so **System update** shows **Update available** again. Don't click it until the problem is fixed in the repository; the next update then moves forward normally. (Don't use `git checkout <version>` here: that leaves a detached HEAD, and the updater refuses to run until you go back to `main`.)

### 14.5 Moving an old install to the new repository

For installs that still come from `JLTechologies/Stock-Rewrite`:

```bash
cd /var/www
php8.4 power-app/artisan down
git clone https://github.com/Jerlag01/power-app.git power-app-new
cp power-app/.env power-app-new/.env                                      # keeps APP_KEY and the database settings
cp -a power-app/storage/app/. power-app-new/storage/app/                  # uploads
mv power-app power-app-old && mv power-app-new power-app                          # replaces the symlink/folder
cd power-app
git config --global --add safe.directory /var/www/power-app
```

Then run section 6 without `key:generate` and without `db:seed`, then sections 8 and 11. The nginx root (`/var/www/power-app/public`) stays the same. When everything works, remove `/var/www/power-app-old`.

---

## 15. Backups

Back up these things; everything else can be rebuilt from git:

| What | How |
|---|---|
| Database | `mysqldump --single-transaction -u power -p power > power-$(date +%F).sql` |
| Uploads | `storage/app/public/` (images, logos, PDFs) |
| CVs of job applicants | `storage/app/private/job-applications/` (personal data: keep the backup protected) |
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
| Contact form or application mail not arriving | Check Settings > Email and use **Send test email**; check the recipients (Settings > Contact details, and the vacancy itself). Errors are in `storage/logs/laravel.log`. The message or application is saved in the admin panel anyway |
| CV upload refused although it is a PDF under 10 MB | PHP upload limits too low (section 8) or nginx `client_max_body_size` (section 9) |
| No "Vacancies" link on the website | No vacancy is visible, or all are past their closing date |
| Logged out immediately / 419 errors | `SESSION_SECURE_COOKIE=true` on a site without HTTPS, or a wrong `APP_URL` |

| System update: scheduler **Not running** / stays "Waiting for the scheduler" | The cron line is missing or wrong (section 11.2); test with `sudo -u www-data php8.4 artisan schedule:run`. Or cancel the request and run `sudo -u www-data php8.4 artisan app:update` |
| Update **Failed**: *Local changes would be overwritten* | Files were edited on the server: see "Before every update" in section 14 |
| Update **Failed**: *Do not run the update as root* | Run it with `sudo -u www-data …`; the cron line belongs in the crontab of `www-data`, not root |
| Update **Failed** at *Fetching* / "Check failed" | No access to GitHub: network, or `www-data` has no deploy key / token (section 4) |
| Update **Failed** at *Pulling* with "Permission denied" or "unable to unlink" | Root-owned files: `chown -R www-data:www-data /var/www/power-app` (section 8) and update again |
| Update **Failed**: composer / npm *was not found* | Set `UPDATER_COMPOSER` / `UPDATER_NPM` in `.env` to the full path (`which composer`, `which npm`), then `php8.4 artisan optimize` |
| Everyone gets "Access denied" | A block covers the IP of a proxy or load balancer in front of the server: remove it in System > Blocked IP addresses, and configure Laravel's trusted proxies so the real visitor IP is used |
| Site still in maintenance after a crash | `sudo -u www-data php8.4 artisan up` |

Logs: `storage/logs/laravel.log` (application), `/var/log/nginx/power-app.error.log` (nginx), `journalctl -u php8.4-fpm` (PHP-FPM). The log of the last update is on the **System update** page and in `storage/app/updater/last-run.json`.

---

## 17. Final checklist

- [ ] `https://www.example.be/nl` shows the website, `/fr` and `/en` switch language
- [ ] `https://www.example.be/admin` logs in with the first admin
- [ ] Uploading a logo in Settings > Appearance works and the logo appears on the site
- [ ] Settings > Email > **Send test email** arrives
- [ ] The contact form on the site arrives at the configured address
- [ ] A test vacancy shows under "Vacancies"; an application with a PDF arrives at every recipient with the CV attached and shows under Contact > Applications (delete the test afterwards)
- [ ] **System update** shows the scheduler as **Running**, and `sudo -u www-data php8.4 artisan app:update --check` answers
- [ ] `APP_ENV=production` and `APP_DEBUG=false` in `.env`
- [ ] Backups of the database, `storage/app/public`, `storage/app/private` and `.env` are scheduled
