# Portlane Shipping

A complete website and shipment tracking system for a freight forwarding company, built
with Laravel. It contains two halves:

- **A public website** — home, about, services, tracking, quote requests, contact, FAQ,
  client reviews and legal pages.
- **An admin panel** at `/admin` — shipments, tracking events, customers, documents,
  customer messages, quote requests, website content, settings, staff accounts and an
  audit log.

Everything a customer sees comes from the database. Nothing on the tracking page is
invented by the application: statuses, locations and descriptions are entered by staff.

> **The company name is a placeholder.** "Portlane Shipping" is set in **Site settings →
> Company** and can be changed to your own name at any time without touching the code.

---

## Table of contents

1. [Project overview](#1-project-overview)
2. [Features](#2-features)
3. [Technology stack](#3-technology-stack)
4. [System requirements](#4-system-requirements)
5. [Installing PHP](#5-installing-php)
6. [Installing Composer](#6-installing-composer)
7. [Installing Node.js](#7-installing-nodejs)
8. [Installing npm packages](#8-installing-npm-packages)
9. [Creating the database](#9-creating-the-database)
10. [Configuring .env](#10-configuring-env)
11. [Generating the application key](#11-generating-the-application-key)
12. [Installing PHP dependencies](#12-installing-php-dependencies)
13. [Installing front end dependencies](#13-installing-front-end-dependencies)
14. [Running the database migrations](#14-running-the-database-migrations)
15. [Running the seeders](#15-running-the-seeders)
16. [Creating the first administrator](#16-creating-the-first-administrator)
17. [Running the development server](#17-running-the-development-server)
18. [Building the front end assets](#18-building-the-front-end-assets)
19. [Email configuration](#19-email-configuration)
20. [File storage configuration](#20-file-storage-configuration)
21. [Storage permissions](#21-storage-permissions)
22. [Creating shipments](#22-creating-shipments)
23. [Adding tracking events](#23-adding-tracking-events)
24. [Testing the public tracking page](#24-testing-the-public-tracking-page)
25. [Testing customer chat](#25-testing-customer-chat)
26. [Testing administrator login](#26-testing-administrator-login)
27. [Production build](#27-production-build)
28. [Production deployment](#28-production-deployment)
29. [Shared hosting deployment](#29-shared-hosting-deployment)
30. [Database setup on shared hosting](#30-database-setup-on-shared-hosting)
31. [Cron configuration](#31-cron-configuration)
32. [Queue configuration](#32-queue-configuration)
33. [SSL and HTTPS](#33-ssl-and-https)
34. [Email on the live server](#34-email-on-the-live-server)
35. [Backups](#35-backups)
36. [Updating the application](#36-updating-the-application)
37. [Troubleshooting](#37-troubleshooting)
38. [Security checklist](#38-security-checklist)
39. [Environment variable reference](#39-environment-variable-reference)
40. [Project directory structure](#40-project-directory-structure)

---

## 1. Project overview

The application is a normal Laravel project. Visitors browse the website and track
shipments; staff sign in at `/admin` and run the operation.

A shipment gets a tracking number such as `PLS-48291735` when it is created. As the cargo
moves, staff add **tracking events**: a status, a location, a date and time, and a
description written for the customer. Each event can also carry an internal note, which is
never shown outside the admin panel.

The customer enters the tracking number on `/track`, sees the current status, the progress
through the milestone timeline, the full event history and any documents that were marked
as visible to them, and can message the team handling the shipment from the same page.

The tracking prefix, the milestone list, the exception statuses, the services, the pages,
the FAQ entries, the reviews and all the company details are editable in the admin panel.

## 2. Features

**Public website**

- Home, About, Services (with a page per service), Track Shipment, Request a Quote,
  Contact, FAQ, Client Reviews, Privacy Policy, Terms of Service
- Shipment tracking with a milestone timeline, progress bar, event history, shipment
  details and downloadable documents
- Customer chat attached to the shipment, using lightweight polling
- Quote request and contact forms with validation, rate limiting and a bot trap
- SEO: titles, meta descriptions, canonical URLs, Open Graph tags, `sitemap.xml`,
  `robots.txt`, semantic HTML and image alt text
- Polished 403, 404, 419, 429, 500 and 503 pages

**Admin panel**

- Dashboard with shipment counts, recent shipments, tracking updates, conversations,
  quote requests and activity
- Shipments: create, edit, archive, restore, search, filter by status, method and date,
  sort and paginate
- Tracking events: add, edit, delete, choose public or internal, optionally email the
  customer
- Configurable tracking statuses: milestones with a timeline position, and exception
  statuses that require a written explanation
- Customers, documents, customer messages, quote requests, contact messages
- Website content: services, pages, FAQ entries, client reviews
- Site settings, staff accounts with roles, and an audit log of every administrative action

**Security**

- Session authentication with password hashing, CSRF protection and login rate limiting
- Role based authorisation through gates and policies, ready for finer permissions
- Columns in place for two factor authentication
- Private file storage outside the web root with authorised downloads only
- Validation through form requests, and administrator written copy rendered without raw HTML

## 3. Technology stack

| Layer | Choice |
| --- | --- |
| Language | PHP 8.4 (8.3 minimum) |
| Framework | Laravel 13 |
| Database | MySQL 8 or MariaDB 10.6+ (SQLite is used by the test suite) |
| Templates | Blade |
| CSS | Tailwind CSS 4, compiled with Vite |
| JavaScript | Alpine.js |
| Mail | Laravel notifications over SMTP |
| Queue | `sync` by default, `database` when a worker is available |

No paid services are required. There is no websocket server, no search service and no
external chat provider.

## 4. System requirements

- PHP 8.3 or later, with the extensions: `bcmath` or `gmp`, `ctype`, `curl`, `dom`,
  `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`,
  `session`, `tokenizer`, `xml`, `zip`, and `gd` (for image uploads)
- Composer 2
- MySQL 8 or MariaDB 10.6 or later
- Node.js 20 or later — only needed to rebuild the CSS and JavaScript. The compiled assets
  are committed in `public/build`, so the site runs on a server without Node.
- About 200 MB of disk space, plus room for uploaded documents

## 5. Installing PHP

**Ubuntu or Debian**

```sh
sudo apt update
sudo apt install php8.4-cli php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-mysql php8.4-gd php8.4-intl unzip
php -v
```

**macOS (Homebrew)**

```sh
brew install php
php -v
```

**Windows**

Install [Laravel Herd](https://herd.laravel.com) or XAMPP, then confirm in a new terminal:

```powershell
php -v
```

On shared hosting PHP is already installed. Choose the version in the control panel
(cPanel: *Select PHP Version*) and enable the extensions listed in section 4.

## 6. Installing Composer

```sh
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
php -r "unlink('composer-setup.php');"
composer -V
```

On macOS: `brew install composer`. On Windows use the installer from
[getcomposer.org](https://getcomposer.org/download/).

## 7. Installing Node.js

Only needed if you want to change the design. Download the LTS build from
[nodejs.org](https://nodejs.org), or:

```sh
# Ubuntu or Debian
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs

# macOS
brew install node

node -v
npm -v
```

## 8. Installing npm packages

npm comes with Node.js, so there is nothing extra to install. pnpm and yarn work too, but
the lock file in this project is `package-lock.json`, so npm is the simplest choice.

## 9. Creating the database

Create an empty database and a user with full rights on it.

```sh
mysql -u root -p
```

```sql
CREATE DATABASE portlane CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'portlane'@'localhost' IDENTIFIED BY 'choose-a-strong-password';
GRANT ALL PRIVILEGES ON portlane.* TO 'portlane'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

On shared hosting, do the same through *MySQL Databases* in the control panel. Note the
database name, user name and password: hosts usually add a prefix, so the real names look
like `myaccount_portlane`.

## 10. Configuring .env

```sh
cp .env.example .env
```

Open `.env` and set at least:

```dotenv
APP_NAME="Your Company Name"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=portlane
DB_USERNAME=portlane
DB_PASSWORD=choose-a-strong-password
```

For local development use `APP_ENV=local`, `APP_DEBUG=true`,
`APP_URL=http://localhost:8000` and `SESSION_SECURE_COOKIE=false`.

Never commit `.env` — it is in `.gitignore` for a reason.

## 11. Generating the application key

```sh
php artisan key:generate
```

This writes `APP_KEY` into `.env`. It encrypts session data and signed URLs. Changing it
later invalidates existing sessions.

## 12. Installing PHP dependencies

```sh
composer install
```

For a production server:

```sh
composer install --no-dev --optimize-autoloader
```

## 13. Installing front end dependencies

Only needed if you plan to change the CSS or JavaScript:

```sh
npm install
```

## 14. Running the database migrations

```sh
php artisan migrate
```

To start again from an empty database (this deletes everything):

```sh
php artisan migrate:fresh
```

## 15. Running the seeders

```sh
php artisan db:seed
```

This adds:

- the editable site settings, with their defaults
- the 14 milestone statuses and 12 exception statuses
- the six services, the About, Privacy Policy and Terms pages, and the FAQ entries

When `APP_ENV` is anything other than `production`, it also adds **sample data**: six
demonstration shipments, three sample customers, sample reviews and one sample enquiry.
Every sample record is flagged in the database and labelled on the website, so it can never
be mistaken for real customer data.

Add the sample data deliberately:

```sh
php artisan db:seed --class=Database\\Seeders\\DemoDataSeeder
```

Remove it again:

```sh
php artisan portlane:clear-demo-data
```

## 16. Creating the first administrator

There are no credentials in the source code. Create the first account from the command
line:

```sh
php artisan portlane:create-admin
```

You are prompted for a name, an email address and a password. Non-interactively:

```sh
php artisan portlane:create-admin --name="Ada Nwosu" --email="ada@example.com" --role=administrator
```

Roles:

| Role | Can do |
| --- | --- |
| `administrator` | Everything, including staff accounts, site settings and audit logs |
| `manager` | Shipments, customers, messages, enquiries and all website content |
| `agent` | Shipments, customers, documents, messages and enquiries |

Further accounts are created in the panel under **Admin users**.

## 17. Running the development server

```sh
php artisan serve
```

Visit `http://localhost:8000`, and the admin panel at `http://localhost:8000/admin/login`.

To work on the design with hot reloading, run this in a second terminal:

```sh
npm run dev
```

## 18. Building the front end assets

```sh
npm run build
```

This writes the compiled CSS, JavaScript and self hosted fonts into `public/build`. Those
files are committed, so a server without Node.js can still serve the site. Rebuild and
commit them whenever you change anything under `resources/css` or `resources/js`.

## 19. Email configuration

The application sends:

- shipment status updates to customers (only when enabled, see below)
- a copy of every quote request, contact message and new customer chat to your operations
  address
- password reset links for staff accounts

Put your SMTP details in `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourhost.com
MAIL_PORT=587
MAIL_USERNAME=no-reply@example.com
MAIL_PASSWORD=your-mailbox-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
MAIL_ADMIN_ADDRESS="operations@example.com"
```

Then in the admin panel under **Site settings → Notifications**, switch on
*Send shipment update emails to customers* and set the internal notification address.

Customer emails are only sent for statuses whose *Email the customer* box is ticked
(**Tracking statuses**), and only when the shipment itself has notifications enabled. To
try it without sending anything, set `MAIL_MAILER=log` and read `storage/logs`.

## 20. File storage configuration

Two disks are used:

- **`local`** (`storage/app/private`) — shipment documents and chat attachments. These are
  never served directly; they are streamed by a controller after an authorisation check.
- **`public`** (`storage/app/public`) — images uploaded for services, reviews, the logo and
  the hero. These are served through a symbolic link.

Create the link once:

```sh
php artisan storage:link
```

Upload limits are set by `UPLOAD_MAX_KB` in `.env` (8 MB by default) and by your server's
`upload_max_filesize` and `post_max_size`.

## 21. Storage permissions

The web server user must be able to write to two directories:

```sh
chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache   # Ubuntu or Debian
```

On shared hosting, `755` is usually correct because PHP runs as your own account. If you
see "failed to open stream: Permission denied", this is almost always the cause.

## 22. Creating shipments

1. Sign in at `/admin`.
2. Go to **Shipments → New shipment**.
3. Fill in what you know. Only the fields relevant to the movement are needed; a container
   number is not required for an air shipment.
4. Leave *Tracking number* empty and one is generated using the configured prefix.
5. Choose the current status, for example *Booking Confirmed*.
6. Save. The shipment page opens with two obvious next steps: **View tracking page** and
   **Add tracking update**.

The prefix and the number of digits are set under **Site settings → Tracking**. Existing
tracking numbers are never rewritten when you change the format.

## 23. Adding tracking events

On a shipment page, use **Add tracking update**:

- **Status** — a milestone (moves the timeline forward) or an exception
- **Location** — for example `Port of Rotterdam` or `Atlantic Ocean`
- **Date and time** — when it actually happened
- **Public description** — what the customer reads
- **Internal note** — never leaves the admin panel
- **Show on the tracking page** — untick to record something for staff only
- **Move the shipment to this status** — updates the current status and location
- **Email the customer** — sends an update if that status is set to notify

Exception statuses such as *Customs Hold* cannot be saved without a description, so a
customer never sees an unexplained exception. Events can be edited or deleted afterwards;
the shipment's current status and progress are recalculated from the remaining history.

## 24. Testing the public tracking page

1. Copy a tracking number from the shipment list.
2. Open `/track` and enter it. Spaces, lower case and a missing hyphen are all accepted.
3. Check that the status, location, estimated delivery, timeline and history look right.
4. Enter a made up number and confirm the message: *We couldn't find a shipment with that
   tracking number. Check the number and try again.*
5. Add an internal-only event and confirm it does **not** appear on the page.

## 25. Testing customer chat

1. On the tracking page, choose **Contact shipping team**.
2. Enter a name, an email address and a message, then send it.
3. In the admin panel, open **Messages**. The conversation is listed with an unread badge.
4. Reply. Within a few seconds the reply appears on the customer's tracking page without a
   page refresh; the poll interval is `CHAT_POLL_INTERVAL` (8 seconds by default).
5. Open the same tracking page in a different browser and confirm the earlier conversation
   is **not** visible: a conversation is tied to the session that created it, so a tracking
   number alone never exposes someone else's messages.

## 26. Testing administrator login

- Sign in at `/admin/login` with the account from section 16.
- Enter a wrong password five times and confirm you are rate limited.
- Use **Forgot password** and check that the reset email arrives (or appears in
  `storage/logs` when `MAIL_MAILER=log`).
- Sign out, then confirm `/admin/shipments` redirects you back to the login page.
- Check **Audit logs**: sign in, sign out and every change are recorded.

## 27. Production build

On your own machine, before uploading:

```sh
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan test
```

On the server, after the files are in place:

```sh
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

Clear those caches with `php artisan optimize:clear` whenever you change `.env` or the
code.

## 28. Production deployment

For a VPS with SSH access:

```sh
git clone <your-repository> /var/www/portlane
cd /var/www/portlane

composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
# edit .env: APP_URL, APP_ENV=production, APP_DEBUG=false, database and mail settings

php artisan migrate --force
php artisan db:seed --force        # settings, statuses, services, pages, FAQ
php artisan storage:link
php artisan portlane:create-admin

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Point the web server's document root at `public/`. An nginx server block:

```nginx
server {
    listen 443 ssl http2;
    server_name example.com;
    root /var/www/portlane/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }

    client_max_body_size 16m;
}
```

## 29. Shared hosting deployment

The application is designed to run on ordinary cPanel-style hosting. Two procedures
follow: one for hosting with SSH, one for hosting without it.

### Domain setup

Point your domain at the host and wait for DNS to resolve. In the control panel, add the
domain or subdomain that will serve the site and note the folder it was given, usually
`public_html` or `public_html/yourdomain`.

### A. Hosting with SSH

```sh
ssh youraccount@yourhost.com
cd ~

# 1. Get the code
git clone <your-repository> portlane
cd portlane

# 2. PHP dependencies (some hosts require the full path, e.g. /usr/local/bin/php8.4)
php -d memory_limit=-1 /usr/local/bin/composer install --no-dev --optimize-autoloader

# 3. Environment
cp .env.example .env
php artisan key:generate
nano .env      # APP_URL, APP_ENV=production, APP_DEBUG=false, DB_*, MAIL_*

# 4. Database
php artisan migrate --force
php artisan db:seed --force

# 5. Storage
php artisan storage:link
chmod -R 775 storage bootstrap/cache

# 6. First administrator
php artisan portlane:create-admin

# 7. Caches
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Now make the domain serve `public/`. Best option first:

1. **Change the document root.** In the control panel, set the domain's document root to
   `portlane/public`. Nothing else is needed.
2. **Symbolic link.** If the panel will not let you change it:
   ```sh
   rm -rf ~/public_html
   ln -s ~/portlane/public ~/public_html
   ```
3. **Split the files.** If neither is possible, see the last step of procedure B.

### B. Hosting without SSH

1. **Prepare everything on your own machine.**

   ```sh
   composer install --no-dev --optimize-autoloader
   npm install
   npm run build
   php artisan key:generate           # if .env does not exist yet
   ```

   Edit `.env` with the live database and mail settings, `APP_ENV=production`,
   `APP_DEBUG=false` and the live `APP_URL`.

2. **Upload.** Create a zip of the whole project *except* `node_modules`, upload it with
   the control panel's File Manager into a folder next to `public_html` (for example
   `~/portlane`), and extract it there. Make sure the hidden `.env` file was included; if
   your zip tool skipped it, upload it separately and check that File Manager is set to
   show hidden files.

3. **Serve the `public` folder.** Set the domain's document root to `portlane/public` in
   the control panel. If that is not possible:

   - Move everything inside `portlane/public` into `public_html`.
   - Edit `public_html/index.php` and change the two `__DIR__.'/../'` paths to point at the
     application folder:

     ```php
     require __DIR__.'/../portlane/vendor/autoload.php';
     $app = require_once __DIR__.'/../portlane/bootstrap/app.php';
     ```

   - Keep everything else (`app`, `config`, `storage`, `vendor`, `.env` …) **outside**
     `public_html`.

4. **Create the database.** See section 30.

5. **Run the migrations without a terminal.** Most panels include *Terminal* or *Cron
   jobs*. If there is no terminal, add a one-off cron job scheduled a minute ahead:

   ```
   cd ~/portlane && /usr/local/bin/php artisan migrate --force --no-interaction && /usr/local/bin/php artisan db:seed --force --no-interaction
   ```

   Delete that cron job once it has run. Create the administrator the same way:

   ```
   cd ~/portlane && /usr/local/bin/php artisan portlane:create-admin --name="Your Name" --email="you@example.com" --password="a-strong-password" --role=administrator
   ```

   Change that password in the panel afterwards, under **Your profile**, and remove the
   cron job so the password is not left in the panel.

6. **Storage link.** If `php artisan storage:link` cannot be run, create the folder
   `public_html/storage` and, using File Manager, make it a symbolic link to
   `~/portlane/storage/app/public`. Where links are not allowed, uploaded images will not
   display; everything else keeps working.

7. **Permissions.** Set `storage` and `bootstrap/cache` (and everything inside them) to
   `755`, or `775` if the host runs PHP as a different user.

## 30. Database setup on shared hosting

1. Control panel → **MySQL Databases**.
2. Create a database, for example `portlane`. The panel prefixes it: `myaccount_portlane`.
3. Create a user and a strong password, then add the user to the database with **All
   privileges**.
4. Put the full prefixed names in `.env`:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=myaccount_portlane
   DB_USERNAME=myaccount_portlane
   DB_PASSWORD=the-password-you-set
   ```

   Some hosts require `DB_HOST=localhost` rather than `127.0.0.1`; if the connection is
   refused, try the other one, and check the panel for a dedicated database hostname.

5. Run the migrations (section 29, step 5). To confirm, open phpMyAdmin: you should see
   `shipments`, `shipment_events`, `site_settings` and the rest.

## 31. Cron configuration

One cron entry runs Laravel's scheduler, which clears expired password reset tokens and
prunes old queue records:

```
* * * * * cd /home/youraccount/portlane && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Find the correct PHP path with `which php` over SSH, or in the control panel's PHP
selector. Many panels ask for the command without the leading `* * * * *`, in which case
paste only the part after it and choose "every minute".

## 32. Queue configuration

Out of the box `QUEUE_CONNECTION=sync`: emails are sent during the request. That is the
simplest setup and needs no worker.

If sending is slow, or you send a lot of updates, switch to the database queue:

```dotenv
QUEUE_CONNECTION=database
```

Then process the jobs. On a VPS, use a supervised worker:

```sh
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

On shared hosting, add a cron entry every minute instead:

```
* * * * * cd /home/youraccount/portlane && /usr/local/bin/php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

After deploying new code, restart workers with `php artisan queue:restart`.

## 33. SSL and HTTPS

1. Issue a certificate in the control panel — most hosts include Let's Encrypt (cPanel:
   *SSL/TLS Status* → *Run AutoSSL*).
2. Set `APP_URL=https://example.com` in `.env`.
3. Keep `SESSION_SECURE_COOKIE=true` so session cookies are only sent over HTTPS.
4. Force HTTPS at the server. In `public/.htaccess`, immediately after `RewriteEngine On`:

   ```apache
   RewriteCond %{HTTPS} !=on
   RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

   On nginx, redirect port 80 to 443 in the server block.
5. Run `php artisan config:cache` again after changing `.env`.

When `APP_ENV=production`, the application already generates all its own URLs over HTTPS.

## 34. Email on the live server

- Create a mailbox such as `no-reply@example.com` in the control panel and use its SMTP
  details in `.env`.
- Prefer the host's own SMTP server: mail sent from the server it is hosted on is far less
  likely to be treated as spam.
- Add SPF and DKIM records for the domain. Most panels offer both in the email section.
- Send a test after configuring:

  ```sh
  php artisan tinker
  >>> Mail::raw('Test from the shipping system', fn ($m) => $m->to('you@example.com')->subject('Test'));
  ```

- If nothing arrives, check `storage/logs`, then try port 465 with `MAIL_ENCRYPTION=ssl`.

## 35. Backups

Two things need backing up: the **database** and `storage/app` (uploaded documents and
images).

```sh
# Database
mysqldump -u portlane -p portlane > backup-$(date +%F).sql

# Files
tar -czf storage-$(date +%F).tar.gz storage/app
```

A nightly cron job:

```
0 2 * * * cd /home/youraccount/portlane && mysqldump -u DBUSER -pDBPASS DBNAME > ~/backups/db-$(date +\%F).sql
```

Most control panels also have a *Backup Wizard*. Download a copy off the server; a backup
that only exists on the server is not a backup. Restore with:

```sh
mysql -u portlane -p portlane < backup-2026-01-01.sql
```

## 36. Updating the application

```sh
php artisan down                      # maintenance page

git pull                              # or upload the changed files
composer install --no-dev --optimize-autoloader
php artisan migrate --force

php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart             # if you use the database queue

php artisan up
```

If you changed anything in `resources/css` or `resources/js`, run `npm run build` first and
upload `public/build` as well. Always take a database backup before an update that includes
migrations.

## 37. Troubleshooting

| Symptom | Cause and fix |
| --- | --- |
| **500 error, blank page** | Look in `storage/logs/laravel-*.log`. Nine times out of ten it is permissions on `storage` or a wrong database setting. |
| **"No application encryption key has been specified"** | Run `php artisan key:generate`. |
| **"Vite manifest not found"** | The compiled assets are missing. Run `npm install && npm run build`, or upload `public/build`. |
| **"SQLSTATE[HY000] [1045] Access denied"** | Wrong `DB_USERNAME` or `DB_PASSWORD`, or the user was not added to the database. |
| **"SQLSTATE[HY000] [2002] Connection refused"** | Try `DB_HOST=localhost` instead of `127.0.0.1`, or the hostname given by your host. |
| **Uploaded images do not show** | `php artisan storage:link` has not been run, or the host does not allow symbolic links. |
| **"Permission denied" writing to storage** | `chmod -R 775 storage bootstrap/cache` and check the owner. |
| **Changes to `.env` have no effect** | Configuration is cached. Run `php artisan config:cache` again. |
| **419 page expired** | The session expired or cookies are blocked. Reload the form. If it happens constantly, check `SESSION_DOMAIN` and that `SESSION_SECURE_COOKIE` is not `true` on a site served over plain HTTP. |
| **Emails are not arriving** | Check `MAIL_*` settings, then `storage/logs`. Confirm notifications are switched on in **Site settings → Notifications** and on the individual status. |
| **Admin panel styling looks wrong** | `public/build` is out of date. Rebuild and upload it. |
| **Locked out of the admin panel** | Create another administrator from the command line with `php artisan portlane:create-admin`. |

Watch the log while reproducing a problem:

```sh
tail -f storage/logs/laravel-*.log
```

## 38. Security checklist

Before going live:

- [ ] `APP_DEBUG=false` and `APP_ENV=production`
- [ ] `APP_KEY` generated, and `.env` never committed
- [ ] The domain serves `public/`, not the project root
- [ ] HTTPS working, with `SESSION_SECURE_COOKIE=true`
- [ ] A strong, unique administrator password; one account per member of staff
- [ ] Roles assigned by what people actually need
- [ ] Demo data removed: `php artisan portlane:clear-demo-data`
- [ ] `storage` and `bootstrap/cache` writable, and nothing else writable
- [ ] Database user limited to its own database
- [ ] Backups running and downloaded off the server
- [ ] Test suite passing: `php artisan test`
- [ ] Sensitive shipment documents uploaded as *Internal only* unless the customer needs them

Already handled by the application: CSRF protection on every form, hashed passwords,
parameter-bound queries, escaped output, mass assignment protection, authorisation checks
on every admin route, rate limiting on login, tracking, forms and chat, validated file
uploads stored outside the web root, and an audit log that never records credentials.

## 39. Environment variable reference

| Variable | Purpose | Example |
| --- | --- | --- |
| `APP_NAME` | Fallback company name, used until one is saved in the settings | `Portlane Shipping` |
| `APP_ENV` | `local` or `production` | `production` |
| `APP_KEY` | Encryption key, from `key:generate` | `base64:…` |
| `APP_DEBUG` | Detailed errors. Must be `false` in production | `false` |
| `APP_URL` | Public address, used for links, emails and the sitemap | `https://example.com` |
| `APP_TIMEZONE` | Timezone for dates and times | `UTC` |
| `LOG_CHANNEL` / `LOG_STACK` / `LOG_LEVEL` | Logging | `stack` / `daily` / `error` |
| `DB_CONNECTION` | `mysql` in production, `sqlite` for quick trials | `mysql` |
| `DB_HOST` / `DB_PORT` | Database server | `127.0.0.1` / `3306` |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | Database credentials | `portlane` |
| `SESSION_DRIVER` | Where sessions are stored | `database` |
| `SESSION_LIFETIME` | Minutes before a staff session expires | `120` |
| `SESSION_SECURE_COOKIE` | Send cookies over HTTPS only | `true` |
| `CACHE_STORE` | Cache backend | `database` |
| `QUEUE_CONNECTION` | `sync`, or `database` with a worker | `sync` |
| `FILESYSTEM_DISK` | Default disk | `local` |
| `MAIL_MAILER` | `smtp`, or `log` while testing | `smtp` |
| `MAIL_HOST` / `MAIL_PORT` | SMTP server | `smtp.yourhost.com` / `587` |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | Mailbox credentials | |
| `MAIL_ENCRYPTION` | `tls` or `ssl` | `tls` |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | Sender shown to customers | `no-reply@example.com` |
| `MAIL_ADMIN_ADDRESS` | Default address for internal alerts | `operations@example.com` |
| `TRACKING_PREFIX` | Default tracking prefix before one is saved in settings | `PLS` |
| `TRACKING_DIGITS` | Digits after the prefix | `8` |
| `CHAT_POLL_INTERVAL` | Milliseconds between chat polls | `8000` |
| `UPLOAD_MAX_KB` | Largest document or attachment, in kilobytes | `8192` |

## 40. Project directory structure

```
portlane/
├── app/
│   ├── Console/Commands/          portlane:create-admin, portlane:clear-demo-data
│   ├── Enums/                     Roles, shipping methods, statuses, visibility
│   ├── Http/
│   │   ├── Controllers/           Public controllers
│   │   │   ├── Admin/             Admin panel, including Auth/
│   │   │   └── Tracking/          Tracking page, customer chat, document downloads
│   │   ├── Middleware/            Staff guard, guest guard, security headers
│   │   └── Requests/              Form requests for validation
│   ├── Models/                    Shipment, ShipmentStatus, ShipmentEvent, Customer …
│   ├── Notifications/             Shipment updates, chat and enquiry alerts
│   ├── Policies/                  Shipment, document and user policies
│   ├── Providers/                 Gates, rate limiters, view composers
│   ├── Services/                  ShipmentService, ChatService, DocumentService,
│   │                              MediaService, TrackingNumberGenerator, AuditLogger
│   └── Support/                   Settings, SettingDefinitions, ContentFormatter
├── bootstrap/                     Application bootstrap and middleware registration
├── config/                        Laravel config, plus config/portlane.php
├── database/
│   ├── factories/                 Model factories used by the tests
│   ├── migrations/                Schema
│   └── seeders/                   Settings, statuses, services, pages, FAQ, demo data
├── public/                        Document root: index.php, favicon.svg, build/
├── resources/
│   ├── css/app.css                Tailwind theme and shared component classes
│   ├── js/                        Alpine bootstrap and the chat poller
│   └── views/
│       ├── admin/                 Admin panel screens
│       ├── components/            Blade components, including layouts/
│       ├── errors/                403, 404, 419, 429, 500, 503
│       └── public/                Website pages, including track/
├── routes/
│   ├── console.php                Scheduled maintenance tasks
│   └── web.php                    Every route in the application
├── storage/
│   ├── app/private/               Shipment documents and chat attachments (not public)
│   ├── app/public/                Uploaded images (served through the storage link)
│   └── logs/                      Application logs
└── tests/
    ├── Feature/Admin/             Authentication, shipments, documents, authorisation
    ├── Feature/Public/            Tracking, chat, quotes, contact, website
    └── Unit/                      Tracking numbers, settings, content formatting
```

### Database tables

`users`, `password_reset_tokens`, `sessions`, `cache`, `jobs`, `customers`, `shipments`,
`shipment_statuses`, `shipment_events`, `shipment_documents`, `chat_conversations`,
`chat_messages`, `notifications`, `quote_requests`, `contact_messages`, `reviews`,
`services`, `pages`, `faqs`, `site_settings`, `audit_logs`.

---

## Running the tests

```sh
php artisan test
```

The suite runs against an in-memory SQLite database and covers administrator
authentication and rate limiting, shipment creation and unique tracking numbers, tracking
lookups, tracking events and exception rules, customer chat and staff replies,
authorisation between roles, private document access, quote requests, the contact form,
review publishing, and the settings, tracking number and content formatting helpers.

## Licence

This application is released under the MIT licence, as is the Laravel framework it is
built on.
