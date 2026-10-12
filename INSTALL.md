# power-app – installation guide

How to install the Power Installation website (public site in NL/FR/EN plus the `/admin` panel) on a new server, from an empty machine to a working HTTPS site.

- **Stack:** Laravel 13, Filament 5, PHP 8.4, MySQL/MariaDB, nginx, Vite/Tailwind for the front-end build.
- **Example names in this guide:** domain `www.example.be`, install path `/usr/share/nginx/html/power-app`, database `power`, database user `power`. Replace them with your own values.
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
mkdir -p /usr/share/nginx/html
git clone https://github.com/Jerlag01/power-app.git /usr/share/nginx/html/power-app
cd /usr/share/nginx/html/power-app
git config --global --add safe.directory /usr/share/nginx/html/power-app    # lets root run git in this folder later (it is owned by www-data)
```

The repository is private: use a GitHub deploy key (read-only, recommended for a server) or a personal access token for the clone.

Updates from the admin panel (section 14.1) run git as the `www-data` user, so that user needs read access too. With an SSH remote (`git@github.com:…`), put the deploy key inside the app and point the updater to it. The updater does not use the home folder of `www-data`:

```bash
install -d -o www-data -g www-data -m 700 /usr/share/nginx/html/power-app/storage/app/updater
install -o www-data -g www-data -m 600 deploy_key /usr/share/nginx/html/power-app/storage/app/updater/deploy_key
echo 'UPDATER_SSH_KEY=storage/app/updater/deploy_key' >> /usr/share/nginx/html/power-app/.env
```

`storage/app` is never served by the website. Test the access with `sudo -u www-data php8.4 artisan app:update --check` (section 14.2).

Not in git (and therefore created during the install): `.env`, `vendor/`, `node_modules/`, `public/build/`, `public/storage` and everything uploaded to `storage/`.

> Older installs cloned the combined repository `JLTechologies/Stock-Rewrite` and linked its `power-app` folder. To move such an install to the new repository, see section 14.5.

---

## 5. Configuration (`.env`)

```bash
cd /usr/share/nginx/html/power-app
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
- Optional, for the updater (section 14):

  | Key | Default | Meaning |
  |---|---|---|
  | `UPDATER_PHP` | `php8.4` | PHP binary for **every** update step (artisan, composer). Must be the version PHP-FPM serves the site with; the System update page warns when they differ. A name (`php8.4`) or a full path (`/usr/bin/php8.4`) |
  | `UPDATER_REMOTE` | `origin` | git remote to update from |
  | `UPDATER_BRANCH` | the checked-out branch | branch to follow |
  | `UPDATER_CACHE_PATH` | `storage/app/updater/cache` | home folder and composer/npm caches of the update steps. It stays inside the app, so the home folder of `www-data` (often a root-owned `/var/www`) is never used |
  | `UPDATER_SSH_KEY` | – | deploy key for an SSH remote (section 4) |
  | `UPDATER_COMPOSER`, `UPDATER_NPM` | found in `/usr/local/bin`, `/usr/bin` | full path, only when not found there |
- `SESSION_SECURE_COOKIE=true` requires HTTPS (step 10). While testing over plain HTTP, set it to `false`.
- `APP_KEY` is filled in by the next step. **Keep it safe**: the SMTP password stored in the admin settings is encrypted with it.

---

## 6. Install the application

```bash
cd /usr/share/nginx/html/power-app

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
cd /usr/share/nginx/html/power-app
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
chown -R www-data:www-data /usr/share/nginx/html/power-app
find /usr/share/nginx/html/power-app/storage /usr/share/nginx/html/power-app/bootstrap/cache -type d -exec chmod 775 {} \;
```

Run every later `artisan` command as root, then repeat the `chown` above. Alternatively run artisan as the web user (`sudo -u www-data php8.4 artisan …`), which avoids files owned by root.

### PHP-FPM sandbox (required for this install path)

Ubuntu and Debian run php8.4-fpm with systemd `ProtectSystem=full`, which makes everything under `/usr` read-only for PHP. Without this drop-in the site gives a 500 error ("read-only file system"). Allow writing to the two folders Laravel needs:

```bash
mkdir -p /etc/systemd/system/php8.4-fpm.service.d
cat > /etc/systemd/system/php8.4-fpm.service.d/power-app-writable.conf <<'EOF'
[Service]
ReadWritePaths=/usr/share/nginx/html/power-app/storage /usr/share/nginx/html/power-app/bootstrap/cache
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
    root /usr/share/nginx/html/power-app/public;

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
cd /usr/share/nginx/html/power-app
php8.4 artisan optimize          # config, routes, views, events cache
php8.4 artisan filament:optimize # Filament component + icon cache
chown -R www-data:www-data /usr/share/nginx/html/power-app
systemctl restart php8.4-fpm     # clears OPcache
```

After **every** code or `.env` change, run these four commands again: with the caches on, changes do not show until the caches are rebuilt. An update through the admin panel or `app:update` (section 14) rebuilds them for you.

### 11.2 Scheduler (cron)

The Laravel scheduler runs the updates requested in the admin panel (section 14.1). Without it, **System update** can check for updates but never installs them. Add one cron line for the `www-data` user:

```bash
crontab -u www-data -e
```

```cron
* * * * * cd /usr/share/nginx/html/power-app && php8.4 artisan schedule:run >> /dev/null 2>&1
```

Check it:

```bash
crontab -u www-data -l
sudo -u www-data php8.4 artisan schedule:list     # lists "updater-heartbeat" and "app:update --if-requested"
```

Within a minute, **System update** in the admin panel shows the scheduler as **Running**.

> Put the line in the crontab of `www-data`, **not** root: as root, an update would leave root-owned files behind. The PHP in the cron line only starts the scheduler; the update steps themselves always use `UPDATER_PHP` (default `php8.4`).

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
tar xzf /tmp/power-uploads.tgz -C /usr/share/nginx/html/power-app/storage/app
cd /usr/share/nginx/html/power-app
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
  cd /usr/share/nginx/html/power-app
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
cd /usr/share/nginx/html/power-app
sudo -u www-data php8.4 artisan app:update --check   # read-only: installed and remote commit, update available or not
sudo -u www-data php8.4 artisan app:update           # update now
```

`app:update` performs these steps and stops at the first one that fails:

1. Refuse when tracked files were changed on the server.
2. Refuse when a file it has to replace is not owned by the owner of the app folder (`www-data`), e.g. a `vendor/` installed as root. This is checked **before** anything changes, so a permission problem never leaves a half-updated site. The error lists the files and the `chown` that fixes it.
3. `git fetch` the branch. Nothing new: stop here ("Already up to date").
4. `artisan down` (maintenance page).
5. `git merge --ff-only` to the new commit.
6. `composer install --optimize-autoloader` (with `--no-dev` when `APP_ENV=production`, with the dev tools otherwise) and republish the Livewire assets.
7. `npm ci` when `package.json`/`package-lock.json` changed; `npm run build` when the front-end changed.
8. `artisan migrate --force`.
9. `artisan optimize` + `filament:optimize` in production, or clear those caches in development.
10. `artisan up`.

Every `artisan` and `composer` step runs with the PHP from `UPDATER_PHP` (default `php8.4`), whichever PHP started the command. Composer and npm keep their caches in `storage/app/updater/cache`. `--check` also shows which PHP is used and any ownership problems.

The result also appears on the **System update** page. The command refuses to run as root, because pulled files would become root-owned; `--allow-root` overrides that, but then run the `chown` from section 8 afterwards. A php8.4-fpm restart is not needed: OPcache notices the changed files by itself (unless `opcache.validate_timestamps` was switched off).

### 14.3 Manually

Without cron, without Node.js on the server, or to install one tagged release:

```bash
cd /usr/share/nginx/html/power-app
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
chown -R www-data:www-data /usr/share/nginx/html/power-app
systemctl restart php8.4-fpm                    # clears OPcache
php8.4 artisan up
```

`git merge --ff-only` stops when files were changed on the server itself (`git status` shows them). Undo those changes (`git checkout -- <file>`) or move them into the repository first; never edit code directly on the server.

### 14.4 Going back to the previous version

The version before an update is in the log on the **System update** page (`abc1234 → def5678`: use `abc1234`), or the one you noted in 14.3.

```bash
cd /usr/share/nginx/html/power-app
php8.4 artisan down
git reset --hard <the version noted before the update>    # stays on the main branch
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php8.4 artisan optimize && php8.4 artisan filament:optimize
chown -R www-data:www-data /usr/share/nginx/html/power-app
systemctl restart php8.4-fpm
php8.4 artisan up
```

Database changes are **not** undone by this. When the update ran new migrations, either restore the database backup taken before the update, or run `php8.4 artisan migrate:rollback --step=<number of new migrations>` *before* the `git reset`, while the new code is still there.

`git reset --hard` keeps the folder on the `main` branch, so **System update** shows **Update available** again. Don't click it until the problem is fixed in the repository; the next update then moves forward normally. (Don't use `git checkout <version>` here: that leaves a detached HEAD, and the updater refuses to run until you go back to `main`.)

### 14.5 Moving an old install to the new repository

For installs that still come from `JLTechologies/Stock-Rewrite`:

```bash
cd /usr/share/nginx/html
php8.4 power-app/artisan down
git clone https://github.com/Jerlag01/power-app.git power-app-new
cp power-app/.env power-app-new/.env                                      # keeps APP_KEY and the database settings
cp -a power-app/storage/app/. power-app-new/storage/app/                  # uploads
mv power-app power-app-old && mv power-app-new power-app                          # replaces the symlink/folder
cd power-app
git config --global --add safe.directory /usr/share/nginx/html/power-app
```

Then run section 6 without `key:generate` and without `db:seed`, then sections 8 and 11. The nginx root (`/usr/share/nginx/html/power-app/public`) stays the same. When everything works, remove `/usr/share/nginx/html/power-app-old`.

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
| Update **Failed**: *These files are not owned by www-data* | Something was installed or edited as root (often `vendor/` or `node_modules/`). Run the `chown -R www-data:www-data /usr/share/nginx/html/power-app` from the message and update again. Nothing was changed yet |
| Update **Failed** at *Installing PHP packages* with "does not exist and could not be created" | Same cause, on an older version of the updater: `chown -R www-data:www-data /usr/share/nginx/html/power-app`, then update again |
| Update **Failed**: *PHP binary "php8.4" was not found* / page shows a PHP mismatch | Set `UPDATER_PHP` in `.env` to the PHP version of the website (`php8.4` or the full path from `which php8.4`) |
| Update **Failed**: composer / npm *was not found* | Set `UPDATER_COMPOSER` / `UPDATER_NPM` in `.env` to the full path (`which composer`, `which npm`), then `php8.4 artisan optimize` |
| "Cannot create cache directory /var/www/.cache/composer" | Older version of the updater: it now keeps its caches in `storage/app/updater/cache` (`UPDATER_CACHE_PATH`) |
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
