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

> **Business details are configuration, not code.** "Portlane Shipping" is a working name set
> in **Site settings → Company**. The application deliberately ships with no address, no
> telephone number, no registration number and no operating lanes: those sections stay hidden
> on the website until you enter real values, and the dashboard checklist tracks what is
> still missing. The privacy policy and terms of service are plain-language drafts that need
> your own legal adviser's review before launch.

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

**Operating the application**

41. [Admin workflow](#41-admin-workflow)
42. [Shipment workflow](#42-shipment-workflow)
43. [Tracking workflow](#43-tracking-workflow)
44. [Chat workflow and configuration](#44-chat-workflow-and-configuration)
45. [Changing the company branding](#45-changing-the-company-branding)
46. [Replacing the logo](#46-replacing-the-logo)
47. [Replacing the images](#47-replacing-the-images)
48. [Changing the tracking prefix](#48-changing-the-tracking-prefix)
49. [Changing the shipment statuses](#49-changing-the-shipment-statuses)
50. [Adding staff accounts](#50-adding-staff-accounts)
51. [Configuring notification emails](#51-configuring-notification-emails)
52. [Removing the demo data](#52-removing-the-demo-data)
53. [Database structure](#53-database-structure)
54. [Preparing the application for production](#54-preparing-the-application-for-production)
55. [Quote requests: receiving and answering](#55-quote-requests-receiving-and-answering)
56. [Adding more contact details](#56-adding-more-contact-details)
57. [Managing the client reviews section](#57-managing-the-client-reviews-section)
58. [The country list](#58-the-country-list)

**User manual**

59. [Staff roles at a glance](#59-staff-roles-at-a-glance)
60. [Manual: master admin](#60-manual-master-admin)
61. [Manual: customer representative](#61-manual-customer-representative)

**Operating on SQLite**

62. [Running on SQLite in production](#62-running-on-sqlite-in-production)

**Putting it online**

63. [A free preview deployment](#63-a-free-preview-deployment)
64. [What it costs to run](#64-what-it-costs-to-run)

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
- Customer chat attached to the shipment, using lightweight polling. The chat holds no
  files and is cleared 24 hours after the last message
- A structured quote request form: contact details, route (country and city, chosen from a
  full country list), preferred method and incoterm, then cargo type, weight, dimensions,
  packages, commercial value and ready date
- Contact form, both with validation, rate limiting and a bot trap
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
- Customers, documents, customer messages and contact messages
- Quote requests that arrive in the dashboard **and** the operations mailbox, and can be
  answered from either: send a quotation from the admin panel with rate, transit time and
  validity, kept on the record, or press Reply in webmail because the alert is addressed to
  reply to the customer
- Website content: services, pages, FAQ entries, client reviews
- Site settings, staff accounts and an audit log of every administrative action
- Two staff roles: a **master admin** who runs everything, and any number of **customer
  representatives** who only answer customer messages. Each representative works from a
  private dashboard showing the conversations assigned to them, with the tracking details
  of the shipment each conversation is about

**Getting it live**

- A "Before you go live" checklist on the admin dashboard that lists what a fresh
  installation still needs: contact details, operating lanes, mail configuration, legal
  details, sample data removal and debug mode
- A complete brand kit in `public/assets/brand` (primary, horizontal and compact logo, on
  light and dark, in SVG and PNG), a favicon set and a social sharing card
- Original artwork for every service in `public/assets/illustrations`, used until you
  upload your own photography
- Brand colours, company details, page copy, tracking format, chat and upload limits all
  editable from Site settings

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
| Database | SQLite, or MySQL 8 / MariaDB 10.6+ (see sections 9 and 62) |
| Templates | Blade |
| CSS | Tailwind CSS 4, compiled with Vite |
| JavaScript | Alpine.js |
| Mail | Laravel notifications over SMTP |
| Queue | `sync` by default, `database` when a worker is available |

No paid services are required. There is no websocket server, no search service and no
external chat provider.

The logo, favicon set, social card and service artwork are original files that ship with the
project in `public/assets`, so there is no stock imagery licence to buy and nothing to
replace before launch — though you should swap the artwork for photographs of your own
operation when you have them (section 47).

## 4. System requirements

- PHP 8.3 or later, with the extensions: `bcmath` or `gmp`, `ctype`, `curl`, `dom`,
  `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_sqlite` or `pdo_mysql`,
  `session`, `tokenizer`, `xml`, `zip`, and `gd` (for image uploads)
- Composer 2
- SQLite (bundled with PHP through `pdo_sqlite`), or MySQL 8 or MariaDB 10.6 or later
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

Two databases are supported, and the choice is yours to make before you install.

**SQLite** is one file on disk. There is no server to install, no user or password to
create, and nothing to configure. It is a sound choice for a freight desk creating up to a
few hundred shipments a month with a handful of staff signed in — the shipment, event and
message tables stay small, and reads far outnumber writes. Choose it if you want one less
moving part.

```sh
touch database/database.sqlite
chmod 660 database/database.sqlite
```

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/home/youraccount/portlane/database/database.sqlite
SESSION_DRIVER=file
CACHE_STORE=file
```

Use the **absolute** path; a relative one is resolved against the working directory and will
bite you the first time cron runs. The two driver lines keep session and cache writes out of
the database file, so ordinary page views never queue behind each other. Everything else
about SQLite is already configured in `config/database.php` — see section 62 for what is set
and why, and for when to move to MySQL.

**MySQL 8 or MariaDB 10.6+** is the choice when several people are writing all day, when you
want the host's own backup tooling to cover the database, or when you expect the archive to
run to tens of thousands of shipments. Create an empty database and a user with full rights
on it.

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

On SQLite those six lines are replaced by two:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/home/youraccount/portlane/database/database.sqlite
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
Every sample record is flagged in the database and labelled on the website, and sample
reviews are created unpublished, so none of it can be mistaken for real customer data.

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
| `administrator` | **Master admin.** Everything: shipments, tracking updates, statuses, documents, customers, website content, settings, staff accounts and audit logs |
| `representative` | **Customer representative.** Answers the customer conversations assigned to them, and nothing else. Cannot create or change a shipment |

Create a representative the same way:

```sh
php artisan portlane:create-admin --name="Ada Okonjo" --email="ada@example.com" --role=representative
```

Further accounts are created in the panel under **Admin users**, which is the usual way once
the first master admin exists.

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
MAIL_FROM_ADDRESS="no-reply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
MAIL_ADMIN_ADDRESS="operations@example.com"
```

On port 587 the connection is upgraded to TLS automatically, so no encryption setting is
needed. Only if your provider insists on the older port 465 add `MAIL_SCHEME=smtps`. (This
framework reads `MAIL_SCHEME`; the `MAIL_ENCRYPTION` line you may have seen in older Laravel
guides is ignored.)

### Recommended: send through Brevo

Your host's own mailbox will send mail, but shared hosting IPs are shared with everyone else
on the server, so a quote reply can land in spam through no fault of yours. A dedicated
sending service fixes that, and at this volume it costs nothing: Brevo's free plan allows
300 emails a day, which is far beyond what a freight desk sends, and includes the SMTP relay,
domain authentication and a log of everything sent. No card is required.

1. Create an account at [brevo.com](https://www.brevo.com) and verify your email address.
2. **Senders, Domains & Dedicated IPs → Domains → Add a domain.** Enter your domain and add
   the DNS records it gives you (a DKIM record, a Brevo verification record, and an SPF
   entry) at whoever runs your DNS. Wait for all three to show as verified. Skipping this is
   the single most common reason mail from a new site goes to spam.
3. **SMTP & API → SMTP → Generate a new SMTP key.** Copy it once; it is not shown again.
4. Put it in `.env` (the username is your Brevo account login, the password is the SMTP key,
   never your account password):

   ```dotenv
   MAIL_MAILER=smtp
   MAIL_HOST=smtp-relay.brevo.com
   MAIL_PORT=587
   MAIL_USERNAME=you@yourdomain.com      # your Brevo login
   MAIL_PASSWORD=xsmtpsib-…              # the SMTP key from step 3
   MAIL_FROM_ADDRESS="no-reply@yourdomain.com"
   MAIL_FROM_NAME="${APP_NAME}"
   MAIL_ADMIN_ADDRESS="operations@yourdomain.com"
   ```

   `MAIL_FROM_ADDRESS` must be on the domain you authenticated in step 2.
5. Clear the cached configuration and send a test:

   ```sh
   php artisan config:clear
   php artisan tinker --execute="Mail::raw('Test from Portlane.', fn (\$m) => \$m->to('you@yourdomain.com')->subject('Portlane test'));"
   ```

   The mail should arrive within a few seconds, and appear under **Transactional → Logs** in
   Brevo. If it does not, the log there tells you why, which is more than a shared host will.

**Receiving mail** is a separate job. Brevo sends; it does not give you an inbox. For
`info@yourdomain.com` either use the mailboxes included with your hosting plan, or point your
domain's MX records at Cloudflare Email Routing, which forwards to a Gmail address for free.

Customer update emails are **off out of the box**. Once mail is working, go to
**Site settings → Notifications**, set the internal notification address and switch on
*Send shipment update emails to customers*. Section 51 covers the per status, per shipment
and per update controls.

Customer emails are only sent for statuses whose *Email the customer* box is ticked
(**Tracking statuses**), and only when the shipment itself has notifications enabled. To
try it without sending anything, set `MAIL_MAILER=log` and read `storage/logs`.

## 20. File storage configuration

Two disks are used:

- **`local`** (`storage/app/private`) — shipment documents, uploaded by staff. These are
  never served directly; they are streamed by a controller after an authorisation check.
  Nothing from the customer chat is stored here: files cannot be sent through the chat.
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
6. Confirm there is no file field anywhere in the chat. Files are deliberately not accepted.
7. Check the retention behaviour:

   ```sh
   php artisan portlane:purge-chat
   ```

   It reports how many conversations were older than the retention window. To see it remove
   something, set a conversation's `last_message_at` to more than 24 hours ago and run it
   again; the conversation and its messages are deleted.

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

One cron entry runs Laravel's scheduler, which clears the customer chat, removes expired
password reset tokens and prunes old queue records:

```
* * * * * cd /home/youraccount/portlane && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Find the correct PHP path with `which php` over SSH, or in the control panel's PHP
selector. Many panels ask for the command without the leading `* * * * *`, in which case
paste only the part after it and choose "every minute".

Set this up: it is what runs `portlane:purge-chat` every hour. If the cron entry is missing,
conversations past the retention window are still unreadable — nobody, customer or staff,
can open one — and the application clears them itself while the chat is being used, but the
scheduled run is what guarantees it happens on a quiet site.

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
# Database, on MySQL
mysqldump -u portlane -p portlane > backup-$(date +%F).sql

# Database, on SQLite (safe while the site is running; never plain cp)
php artisan portlane:backup-database

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
| `DB_CONNECTION` | `sqlite` or `mysql`; both are supported in production | `mysql` |
| `DB_JOURNAL_MODE` | SQLite journal mode; leave on WAL | `WAL` |
| `DB_BUSY_TIMEOUT` | Milliseconds a SQLite write waits for the lock | `5000` |
| `DB_SYNCHRONOUS` | SQLite durability setting | `NORMAL` |
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
| `BRAND_PRIMARY_COLOUR` | Default primary colour before one is saved in settings | `#0c1f2e` |
| `BRAND_ACCENT_COLOUR` | Default accent colour before one is saved in settings | `#ab4c17` |
| `TRACKING_PREFIX` | Default tracking prefix before one is saved in settings | `PLS` |
| `TRACKING_DIGITS` | Digits after the prefix | `8` |
| `CHAT_POLL_INTERVAL` | Milliseconds between chat polls | `8000` |
| `CHAT_RETENTION_HOURS` | Hours a conversation lives after its last message | `24` |
| `UPLOAD_MAX_KB` | Largest shipment document, in kilobytes | `8192` |

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
├── public/                        Document root: index.php, build/, favicon set
│   └── assets/
│       ├── brand/                 Logo suite (SVG and PNG) and the social sharing card
│       └── illustrations/         Service and port artwork used until photographs are added
├── resources/
│   ├── css/app.css                Tailwind theme and shared component classes
│   ├── js/                        Alpine bootstrap and the chat poller
│   └── views/
│       ├── admin/                 Admin panel screens
│       ├── components/            Blade components, including layouts/
│       ├── errors/                403, 404, 419, 429, 500, 503
│       ├── public/                Website pages, including track/
│       └── vendor/mail/           Branded email templates
├── routes/
│   ├── console.php                Scheduled maintenance tasks
│   └── web.php                    Every route in the application
├── storage/
│   ├── app/private/               Shipment documents (not public; the chat stores no files)
│   ├── app/public/                Uploaded images (served through the storage link)
│   └── logs/                      Application logs
└── tests/
    ├── Feature/Admin/             Authentication, shipments, documents, authorisation, launch checklist
    ├── Feature/Public/            Tracking, chat, quotes, contact, website, branding
    └── Unit/                      Tracking numbers, settings, content formatting
```

### Database tables

`users`, `password_reset_tokens`, `sessions`, `cache`, `jobs`, `customers`, `shipments`,
`shipment_statuses`, `shipment_events`, `shipment_documents`, `chat_conversations`,
`chat_messages`, `notifications`, `quote_requests`, `contact_messages`, `reviews`,
`services`, `pages`, `faqs`, `site_settings`, `audit_logs`.


---

## 41. Admin workflow

Sign in at `/admin`. The dashboard shows the shipment counts, anything unread, and — until
you have finished setting the site up — a **Before you go live** checklist.

A normal working day looks like this:

1. **Shipments → New shipment.** Fill in the customer, route, cargo and dates, choose the
   opening status and save. The tracking number is generated for you.
2. **Add tracking update** as the cargo moves. Each update carries a status, a location, a
   date, the wording the customer reads, and an optional internal note.
3. **Messages** when a customer writes in from the tracking page. Replies appear on their
   tracking page within a few seconds.
4. **Quote requests** and **Contact messages** for new enquiries; mark them contacted,
   quoted or closed as you work through them.
5. **Documents** to see everything uploaded across all shipments, and which of them the
   customer can download.
6. **Audit logs** to see who changed what.

Roles decide what each person sees. A **master admin** gets everything above. A **customer
representative** signs in to the same panel but lands on their own dashboard: the
conversations assigned to them, the queue of customers nobody has picked up, and the
tracking details of the shipment behind each one. They cannot open the shipment, customer,
status, content, settings or audit screens at all — those addresses return 403.

## 42. Shipment workflow

```
Create shipment
  → assign the customer, origin, destination, cargo details and estimated delivery
  → choose the opening status (usually Booking Confirmed)
Save
  → the tracking number is issued and the opening status is recorded as the first event
Add tracking events as the shipment progresses
  → Cargo Received → Processing → Shipped → In Transit → Arrived at Port
  → Customs Clearance → Released → Out for Delivery → Delivered
Customer follows it on the public tracking page and can message the team
Staff reply from Messages
Shipment reaches Delivered, then is archived when the file is closed
```

Notes worth knowing:

- Setting an **exception** status (Delayed, Customs Hold, Damaged Cargo …) requires a written
  explanation. The application will not save an unexplained exception, so a customer never
  sees a red status with no reason.
- **Show on the tracking page** can be unticked to record something for staff only.
- **Move the shipment to this status** updates the current status and location; leave it
  unticked to log a historic event without moving the shipment.
- Editing or deleting an event recalculates the shipment's current status and progress from
  what is left.
- Archiving hides a shipment from the active list. It stays trackable and can be restored.

## 43. Tracking workflow

A customer opens `/track`, types the number from their booking confirmation — spacing, case
and a missing hyphen are all accepted — and sees:

- the tracking number, current status and last update time
- the current location, estimated delivery and shipping method
- a progress bar and the milestone timeline
- the full public event history
- the shipment details that are safe to show: route, cargo, packages, weight, container,
  vessel, voyage, air waybill and flight numbers
- documents you marked as visible to the customer
- a message box that reaches the team handling the shipment

No account is needed. What a tracking number never exposes: internal notes, internal-only
events, internal documents, other customers' conversations, or anything from the admin panel.
Customer-visible documents can only be downloaded in a browser session that has actually
looked the shipment up.

## 44. Chat workflow and configuration

```
Customer opens the tracking page → Contact shipping team
  → gives a name, email address and message (no files: the chat does not accept them)
Conversation is created against that shipment, unassigned
  → a copy goes to your internal notification address
A representative picks it up, or replies to it, which assigns it to them
  → from then on it is theirs, and other representatives cannot open it
  → later customer messages also go to that representative's own email address
They reply from Messages, with the shipment's tracking details beside the thread
  → the customer sees the reply on the tracking page and gets an email telling them so
Conversation is closed when the question is answered, and reopens if the customer writes again
  → 24 hours after the last message the whole conversation is deleted
```

**The chat is a window, not a record**

This is deliberate, and it is the part to understand before you change anything here:

- **Nothing can be uploaded.** There is no file field on either side of the chat. A customer
  who needs to send paperwork is pointed at your operations email address instead, and staff
  attach documents to the shipment, where they belong and where access is checked.
- **A conversation lives for 24 hours after its last message**, then it is deleted with every
  message in it. Change the window with `CHAT_RETENTION_HOURS` in `.env` if you have a
  reason to; it is configuration rather than a site setting, because it is a commitment to
  your customers rather than a preference to fiddle with. The privacy policy page says
  24 hours in so many words, so edit that text too if you change the value.
- **Past that window nothing can open it** — not the customer who started it, not a
  representative, not the master admin. The application treats an expired conversation as
  already gone, so a cron job that failed to run cannot quietly keep a thread alive.
- **The messages are not copied anywhere else.** The alert to your operations address and the
  reply notification to the customer say that a message is waiting and link to it; neither
  repeats what was written. The audit log records that a message was sent, on which shipment,
  by whom — never its contents.
- **Deletion is done by** `php artisan portlane:purge-chat`, hourly from the scheduler
  (section 31). The chat also sweeps up while it is being used, at most once every fifteen
  minutes, so a host without cron still clears itself.

Anything that has to be kept — a delivery instruction, an address correction, a claim — is
recorded on the shipment as a tracking event or a document before the conversation clears.
Tell your representatives this during training: if it matters, it goes on the shipment.

**Who can see which conversation**

| | Master admin | Customer representative |
| --- | --- | --- |
| Conversations assigned to them | Yes | Yes |
| Conversations nobody has picked up | Yes | Yes, and can pick them up |
| Conversations assigned to somebody else | Yes | No — 403 |
| Reassign a conversation | Yes | No |
| Close or reopen | Any conversation | Their own |
| Start a conversation from a shipment | Yes | No |

A representative can handle as many customers as you like at once; each conversation carries
its own shipment, so a single person can be answering a customer about a container in
Rotterdam and another about an air consignment in Lagos in the same session.

The tracking page checks for new messages on a timer rather than holding a socket open, so
it runs on ordinary shared hosting with nothing extra installed. Two settings control it,
under **Site settings → Tracking and chat**:

- **Allow customers to message the team from the tracking page** — on by default.
- **Chat refresh interval** — 8000 milliseconds by default. `CHAT_POLL_INTERVAL` in `.env`
  sets the value used before anything is saved in the settings screen.

The retention window is not in the settings screen on purpose: it is `CHAT_RETENTION_HOURS`
in `.env`, 24 by default.

Abuse protection: 10 messages a minute and 60 an hour per address, and a hidden field that
automated form fillers trip over. Access to a conversation needs both the tracking number
and the session that created it.

Moving to websockets later means replacing the fetch in `resources/js/chat.js` and the
`messages` endpoint in `TrackingChatController` with a broadcast listener. The data model,
the authorisation rules and the admin side stay as they are.

## 45. Changing the company branding

Everything below is in **Site settings**, and takes effect immediately. Nothing needs a
rebuild.

| What | Where |
| --- | --- |
| Company name, registered name, registration number, tagline, description, footer note | Company |
| Logo | Company |
| Address, telephone, email, operations email, hours, timezone, additional contact details | Contact and hours |
| Homepage headings, hero copy, "why clients stay with us", lanes, reviews heading and intro, closing call to action | Homepage |
| Tracking prefix and length, tracking page copy, chat, upload limit | Tracking and chat |
| Quote page copy, confirmation message, frequently shipped countries, default quotation text | Quote requests |
| Customer update emails, internal notification address, email sign off | Notifications |
| Governing jurisdiction, trading conditions, retention period, legal contact | Legal |
| Primary and accent colour | Brand |
| Meta description, social card, indexing, social links | Search and social |

**Brand colours.** Set a primary and an accent colour under **Brand**. The layouts publish
them as CSS custom properties and the rest of the palette is derived from them, so the whole
site follows without a front end rebuild. The shipped defaults are navy `#0c1f2e` and rust
`#ab4c17`; they can also be set with `BRAND_PRIMARY_COLOUR` and `BRAND_ACCENT_COLOUR` in
`.env`. Keep the accent dark enough for white button text to stay readable.

**Legal placeholders.** Page copy can contain placeholders that are filled in from the
settings when the page is rendered: `[[company.legal_name]]`, `[[company.registration_number]]`,
`[[contact.email]]`, `[[contact.address]]`, `[[legal.jurisdiction]]`,
`[[legal.trading_conditions]]`, `[[legal.retention_period]]`, `[[legal.contact_email]]`.
A line whose placeholder has no value yet is left out of the page entirely, which is why the
terms of service says nothing about a registration number until you enter one.

## 46. Replacing the logo

The application ships with a real logo, not a placeholder. The source files are in
`public/assets/brand`:

| File | Use |
| --- | --- |
| `portlane-logo-primary.svg` / `.png` | Stacked lockup for documents and print |
| `portlane-logo-horizontal.svg` / `.png` | Website header, email header, light backgrounds |
| `portlane-logo-horizontal-on-dark.svg` / `.png` | Dark backgrounds |
| `portlane-mark.svg`, `portlane-mark-512.png` | Compact mark, app icons, avatars |
| `portlane-mark-on-dark.svg` | Compact mark on dark backgrounds |
| `portlane-og.svg` / `.png` | 1200 x 630 social sharing card |
| `public/favicon.svg`, `favicon-32.png`, `apple-touch-icon.png` | Browser and device icons |

To use your own logo: **Site settings → Company → Logo**, upload an SVG or PNG with a
transparent background. It replaces the mark and wordmark in the header, footer, admin panel
and email templates. The favicon and social card are static files — replace
`public/favicon.svg`, `public/favicon-32.png`, `public/apple-touch-icon.png` and upload a new
social image under **Search and social → Social sharing image**.

## 47. Replacing the images

The service pages and the about page use original artwork that ships with the application, in
`public/assets/illustrations`. It is there so the site never shows an empty grey box, and it
is meant to be replaced with photographs of your own operation.

- **A service:** Services → edit → Image. The uploaded photograph replaces the artwork on the
  services list and the service page. Landscape, at least 1200 pixels wide.
- **The homepage hero:** Site settings → Homepage → Hero photograph. Wide, at least 1800
  pixels; a photograph of a container terminal ships with the application and is used
  until you upload one. Keep the left third of any replacement free of detail — the
  headline sits over it — and remember the image renders darkened behind a gradient, so
  strong simple shapes survive and busy ones turn to mud.
- **The about page:** the illustration is referenced in `resources/views/public/about.blade.php`.

Use photographs you own or have licensed. Do not take images from a search engine.

## 48. Changing the tracking prefix

**Site settings → Tracking and chat → Tracking number prefix** and **Digits after the prefix**.
`PLS` and `8` produce `PLS-48291735`.

The change applies to numbers generated from that point on. Numbers already issued are never
rewritten, and the lookup accepts both old and new formats, so it is safe to change the prefix
on a running system. `TRACKING_PREFIX` and `TRACKING_DIGITS` in `.env` set the values used
before anything is saved in the settings screen.

## 49. Changing the shipment statuses

**Tracking statuses** in the admin panel. Two kinds:

- **Milestones** make up the timeline the customer sees, ordered by their timeline position.
  Fourteen ship with the application, from Booking Confirmed to Delivered.
- **Exceptions** cover situations such as Delayed, Port Congestion, Customs Hold or Damaged
  Cargo. Twelve ship with the application.

For each status you can set the name, a different customer-facing label, the timeline
position, the colour, the default wording, whether it is active, whether it completes the
shipment, whether it emails the customer, and whether it requires a written explanation.

A status that is in use cannot be deleted — switch it off instead, which keeps the history
intact while removing it from the dropdowns.

## 50. Adding staff accounts

**Admin users → New account**: name, email, job title, role and a password. Create as many
accounts as you need; there is no limit.

| Role | Access |
| --- | --- |
| Master Admin | Everything: shipments, tracking updates, statuses, documents, customers, quotes, contact messages, website content, settings, staff accounts, audit logs, and every conversation |
| Customer Representative | Customer messages only: the conversations assigned to them plus the unassigned queue, with read only tracking details for each. No shipment, customer, content, settings or audit access |

A normal setup is one master admin (you) and one account per person answering customers.

Rules the application enforces: nobody can change their own role or deactivate their own
account, the last active master admin cannot be removed, deactivating an account signs that
person out immediately, and a representative cannot open a conversation that belongs to
another representative. Passwords are hashed and are never written to the audit log.

To take somebody off the desk, either deactivate their account (they are signed out and
cannot sign back in) or reassign their open conversations first from **Messages**, so
nothing is left sitting with an account nobody is using.

Staff can change their own name, email and password under **Your profile**. Somebody who has
forgotten their password uses **Forgot password** on the login screen, which needs working
mail settings.

## 51. Configuring notification emails

Customer update emails are **off** until you switch them on, so a half-configured
installation cannot email your customers.

1. Put your SMTP details in `.env` (section 19) and confirm mail works.
2. **Site settings → Notifications**: set the internal notification address, the sign off,
   and turn on *Send shipment update emails to customers*.
3. **Tracking statuses**: tick *Email the customer* on the statuses that should notify. Cargo
   Received, Shipped, Arrived at Port, Clearance Completed, Out for Delivery and Delivered are
   ticked by default; Delayed, Customs Hold and Delivery Attempted are ticked among the
   exceptions.
4. Per shipment, the **Send status update emails for this shipment** box can be unticked for
   a customer who does not want them, and a customer record can opt out of all of them.
5. Per tracking update, **Email the customer** decides whether that particular update sends.

Internal alerts (quote requests, contact messages, new customer chats) go to the internal
notification address as soon as it is set, whether or not customer updates are on.

## 52. Removing the demo data

The demo seeder creates six sample shipments, three sample customers, sample reviews and one
sample enquiry, all flagged in the database and labelled on the website. Sample reviews are
created **unpublished**, so they never appear on the live site.

```sh
# Add it (development only; it also runs automatically when APP_ENV is not production)
php artisan db:seed --class=Database\\Seeders\\DemoDataSeeder

# Remove every sample record
php artisan portlane:clear-demo-data
```

The dashboard checklist reports sample data until it is gone. Records your own staff created
are never touched by the clear command.

The seeder also creates one sample conversation so the Messages screen is not empty. Like any
conversation it is deleted 24 hours after its last message, so on a development install that
has been sitting idle it will simply be gone; re-run the seeder if you want it back.

## 53. Database structure

| Table | Holds |
| --- | --- |
| `users` | Staff accounts, role, active flag, last sign in, and columns reserved for two factor authentication |
| `customers` | Customer records, contact details and notification preference |
| `shipments` | The shipment file: tracking number, customer, route, cargo, transport references, dates, current status, progress, notes |
| `shipment_statuses` | The configurable milestones and exceptions |
| `shipment_events` | The tracking history, public wording and internal notes |
| `shipment_documents` | Uploaded documents, their type, visibility and storage path |
| `chat_conversations` | One conversation per customer enquiry, tied to a shipment, deleted 24 hours after the last message |
| `chat_messages` | Customer and staff messages and read state, deleted with the conversation |
| `quote_requests` | Website quotation requests: contact, structured route, cargo details, status and handling |
| `quote_replies` | Quotations sent to the customer from the admin panel, with rate, transit time and validity |
| `contact_messages` | Website contact form messages |
| `reviews` | Client reviews, published state and sample flag |
| `services`, `pages`, `faqs` | Editable website content |
| `site_settings` | Every editable setting, as key, value and type |
| `audit_logs` | Who did what, when, from which address |
| `notifications`, `jobs`, `cache`, `sessions`, `password_reset_tokens` | Laravel's own tables |

Foreign keys link shipments to customers and statuses, events and documents to shipments, and
conversations to shipments; deleting a shipment removes its events, documents and
conversations with it. Indexes cover the tracking number (unique), status, customer, origin,
destination, and the created and updated timestamps.

## 54. Preparing the application for production

Work through this in order:

1. `.env`: `APP_ENV=production`, `APP_DEBUG=false`, the live `APP_URL`, database credentials,
   SMTP details, `APP_TIMEZONE`.
2. `php artisan key:generate` if the key is not set yet.
3. `php artisan migrate --force` and `php artisan db:seed --force` (settings, statuses,
   services, pages, FAQ — the demo data is skipped in production).
4. `php artisan portlane:create-admin` and sign in.
5. Work down the **Before you go live** checklist on the dashboard until it disappears:
   contact details, registered name, operating lanes, mail, legal details, legal review,
   sample data, debug mode.
6. Replace the logo and imagery if you have your own, and set the brand colours.
7. Send a test email, submit the quote and contact forms, and track a real shipment end to end.
8. `php artisan storage:link`, then `config:cache`, `route:cache` and `view:cache`.
9. Set up the cron entry (section 31) and backups (section 35).
10. Check `https://yourdomain/robots.txt` and `https://yourdomain/sitemap.xml` return what you
    expect, and that `/admin` is not indexable.


---


## 55. Quote requests: receiving and answering

**What the customer fills in.** The form at `/quote` is in three parts:

1. **Your details** — name, company, email and telephone.
2. **Route** — collection country and city, delivery country and city, preferred shipping
   method and incoterm. Countries come from a list of 190, with the ones you ship most often
   at the top, so enquiries are recorded consistently instead of as free text.
3. **Cargo** — type of goods, gross weight, number of packages, dimensions, commercial value,
   cargo ready date and any notes.

Only name, email, both countries and the type of goods are required; a customer who does not
yet know the weight can still send the enquiry.

**Where it goes.** Both at once:

- **The dashboard.** It appears under **Quote requests** with a badge on the sidebar, is
  counted on the dashboard, and gets a reference like `QR-260906-AQXD`.
- **Your mailbox.** The full enquiry is emailed to the internal notification address set in
  **Site settings → Notifications**.

**Answering from the dashboard.** Open the request and use **Send a quotation**:

- subject (pre-filled with the reference and the route);
- currency, rate, transit time and validity, which appear as labelled figures in the email;
- the message body, pre-filled with the standard wording from
  **Site settings → Quote requests → Default text for a quotation reply**;
- *Mark this request as quoted*, which is ticked by default.

The quotation is emailed to the customer, kept on the record under **Quotations sent**, and
written to the audit log. The customer's reply comes back to your operations address, because
that is set as the reply-to.

**Answering from webmail.** The alert email is addressed to reply to the customer, so pressing
Reply in webmail answers them directly — useful on a phone. Replies sent that way are not
recorded in the panel, so set the status to *Quoted* afterwards. The **Reply from webmail**
button at the top of the request opens your mail client with the address and subject filled in.

**Working through them.** The list filters by status, country, date and free text, and shows
which requests have had a quotation sent and which are still waiting.

## 56. Adding more contact details

Beyond the address, telephone and email, you can publish any number of extra lines —
a WhatsApp number, a branch office, an out of hours desk, a customs enquiries address.

**Site settings → Contact and hours → Additional contact details**, one per line:

```
WhatsApp | +234 000 000 0000
Lagos branch | 12 Wharf Road, Apapa
Out of hours | duty desk, +234 111 111 1111
```

They appear immediately in three places: the contact page, the site footer, and the help panel
on the shipment tracking page, so a customer chasing a delivery sees them where they already
are. Remove a line and it disappears everywhere.

## 57. Managing the client reviews section

**Reviews** in the admin panel: create, edit, publish, unpublish and delete. Each entry has a
client name, company, location, rating, the review itself, an optional photograph, a display
order and a date.

The heading and introduction shown above the reviews — on the homepage and at the top of the
client reviews page — are editable under
**Site settings → Homepage → Client reviews heading / intro**.

Rules the application keeps to: nothing appears on the website until you publish it, the
section is hidden entirely while no review is published, and anything flagged as sample
content is labelled as such wherever it appears. Only publish feedback a client has actually
given you.

## 58. The country list

Countries come from one list of 190 in `app/Support/Countries.php`, used by the quote form,
the shipment screens, the customer records and the contact settings. It covers every region,
including the Asian markets most freight moves through — China, Japan, South Korea, Taiwan,
Hong Kong, Singapore, Malaysia, Thailand, Vietnam, Indonesia, the Philippines, India,
Pakistan, Bangladesh, Sri Lanka, the Gulf states, Türkiye and Central Asia.

Countries entered anywhere in the admin panel are validated against the list, so a shipment
and a quote for the same lane always read the same way.

**Putting your own lanes first.** **Site settings → Quote requests → Frequently shipped
countries**, one per line, using the names exactly as they appear in the list:

```
China
United Arab Emirates
Netherlands
Nigeria
```

Those appear in a **Frequently shipped** group above the full list on every country selector.
Leave it empty and a sensible default set is used.

**Showing the countries you ship to, with flags.** The homepage has a *Where we ship* section
that is hidden until you fill it in. **Site settings → Home page → Destination lanes**, one
lane per line, written as `Region | Countries served, separated by commas`:

```
West Africa | Nigeria, Ghana, Benin, Togo
East Asia | China, South Korea, Japan, Vietnam
```

Each entry that matches the country list is printed with its flag next to the country name.
Anything that is not a country — a port, an airport, a note — is printed exactly as you typed
it, so existing copy is unaffected. The flag never replaces the name: Windows draws flag emoji
as the two letter country code rather than a picture, and the name has to read correctly there
too.

Common short forms are understood as well, so `UAE`, `UK`, `USA`, `Turkey`, `Hong Kong` and
`Ivory Coast` are matched to the right country.

## 59. Staff roles at a glance

| | Master Admin | Customer Representative |
| --- | --- | --- |
| Create and edit shipments | Yes | No |
| Add, edit or delete tracking updates | Yes | No |
| Change tracking statuses | Yes | No |
| Upload and delete documents | Yes | No |
| Customers, quotes, contact messages | Yes | No |
| Website content, settings, staff, audit log | Yes | No |
| Read the conversations assigned to them | Yes | Yes |
| Read unassigned conversations and pick them up | Yes | Yes |
| Read a conversation assigned to somebody else | Yes | No |
| Reply to a customer | Yes | Yes, on their own conversations |
| Reassign a conversation | Yes | No |
| See the shipment behind a conversation | Yes, and can edit it | Read only summary |

Both roles sign in at the same address, `/admin`. What they see afterwards is different: the
master admin lands on the operations dashboard, a representative lands on their own
conversation dashboard.

## 60. Manual: master admin

**Signing in.** `/admin/login` with your email and password. Five wrong attempts in a minute
locks the form briefly. Use **Forgot password** if you need a reset link (mail must be
configured).

**Setting the business up.** Work through the *Before you go live* checklist on the
dashboard: contact details, registered name, the routes you operate, mail, legal details,
legal review, sample data and debug mode. Each item links to the screen that fixes it.

**Creating a shipment.**

1. **Shipments → New shipment.**
2. Assign the customer: pick a saved customer, or type the contact name, email and phone.
3. Enter the origin and destination, and the current location if it is already known.
4. Enter the cargo: description, packages, weight, dimensions, and the container, vessel,
   voyage, air waybill or flight numbers that apply. Leave the rest blank.
5. Set the estimated departure, arrival and delivery.
6. Choose the opening status, normally *Booking Confirmed*.
7. Leave the tracking number empty so one is generated, then **Create shipment**.

The shipment page opens with **View tracking page** and **Add tracking update** at the top.

**Recording progress.** On the shipment, use **Add tracking update**: status, location, date
and time, the wording the customer reads, and an internal note if you need one. Tick or
untick:

- *Show on the tracking page* — untick to record something for staff only.
- *Move the shipment to this status* — updates the current status and location.
- *Email the customer* — sends an update if that status is set to notify.

An exception status (Delayed, Customs Hold, Damaged Cargo …) cannot be saved without a
written explanation, so a customer never sees a red status with no reason.

**Documents.** Upload on the shipment page, choosing *Visible to the customer* or *Internal
only*. Customer-visible files appear on the tracking page; internal files never leave the
admin panel.

**Answering a quote request.** **Quote requests** lists every enquiry, newest first, with the
new ones badged in the sidebar. Open one to see the route, cargo and contact details, then use
**Send a quotation** to email the customer a rate, transit time and validity — it is kept on
the record. If you would rather answer from your phone, press Reply on the alert email in
webmail; it is addressed to reply to the customer. Mark the request as quoted afterwards.
Section 55 has the detail.

**Customer messages.** Conversations are temporary: each is deleted 24 hours after its last
message, files cannot be sent through the chat, and nothing from a thread is copied into the
alert emails. Anything worth keeping goes on the shipment as a tracking event or a document
before the thread clears. **Messages** shows every conversation, with three views: all, assigned
to me, and waiting to be picked up. Open one to read the thread, reply, and see the tracking
details beside it. Use **Assign to** to hand it to a representative, or set it back to the
unassigned queue. Close a conversation when it is finished; it reopens by itself if the
customer writes again.

**Adding staff.** **Admin users → New account**, role *Customer Representative* for someone
who only answers customers, or *Master Admin* for another full administrator. Give them the
password directly; they can change it under **Your profile**.

**Publishing contact details and reviews.** Extra contact lines (WhatsApp, a branch, an out of
hours desk) go in **Site settings → Contact and hours → Additional contact details** and appear
on the contact page, the footer and the tracking page. Client feedback goes in **Reviews**;
nothing shows on the website until you publish it, and the heading above the section is
editable under **Site settings → Homepage**.

**Watching the desk.** The dashboard shows unread message counts, recent conversations,
recent tracking updates and recent quote requests. The sidebar badges count new quote
requests and new contact messages. **Audit logs** records every
administrative action with who did it, when and from which address.

## 61. Manual: customer representative

**What you can do.** Answer the customers assigned to you, and pick up customers who have
written in and have nobody handling them. You can see the tracking details for each
conversation so you can answer accurately. You cannot create or change shipments, tracking
updates or statuses — if something needs correcting on the file, tell the master admin.

**Signing in.** `/admin/login` with the email and password you were given. Change your
password under **Your profile** the first time you sign in.

**Your dashboard.** Signing in lands you on **My conversations**:

- **My open conversations** — how many customers you are currently handling.
- **Unread for me** — messages waiting for your reply.
- **Waiting to be picked up** — customers nobody has claimed yet.
- **Handled in total** — everything ever assigned to you.

Below that: your conversations on the left, the waiting queue on the right. Every row shows
the customer's name, their tracking number and the shipment's current status, so you know
what the conversation is about before you open it.

**Picking up a customer.** In the waiting list, press **Pick up**. The conversation becomes
yours and disappears from everyone else's queue. Replying to an unassigned conversation picks
it up automatically.

**Answering.** Open the conversation. You will see:

- the whole thread, oldest first, with your replies on the right, and the time it clears;
- the reply box. There is no attachment field: files cannot be sent through the chat in
  either direction;
- **Shipment** — tracking number, status, route, current location, shipping method,
  estimated delivery and the customer's name, read only;
- **Latest tracking updates** — the last few entries recorded by the operations desk;
- a link to the customer's own tracking page, so you can see exactly what they see.

Write the reply and press **Send reply**. The customer sees it on their tracking page within
a few seconds and gets an email telling them a reply is waiting.

**Handling several customers.** There is no limit. Each conversation carries its own
shipment, so you can be answering one customer about a container and another about an air
consignment at the same time. The **Messages** screen lists them all, with filters for
*assigned to me*, *waiting to be picked up*, open and closed.

**Conversations do not last.** A conversation is deleted 24 hours after its last message,
along with everything in it, so a customer's details do not sit on the server. The clearing
time is shown at the top of every thread. **If something in a conversation matters — a
corrected address, a delivery instruction, a complaint — tell the master admin so it is
recorded on the shipment before the thread clears.** Once it is gone, nobody can get it back.

If a customer wants to send a document, ask them to email it to the operations address with
their tracking number; the master admin uploads it against the shipment.

**Finishing.** Press **Close conversation** when the question is answered. If the customer
writes again, it reopens automatically and comes back to you — until the thread clears, after
which their next message starts a fresh conversation.

**What to escalate.** Anything that needs the shipment file changed — a wrong delivery date,
a missing document, a status that does not match reality — goes to the master admin. You can
tell the customer what the file currently says, but only the master admin can change it.

---

## 62. Running on SQLite in production

SQLite is a supported production database here, not only a development convenience. This
section is what you need to know if you choose it.

**When it is the right choice.** A freight desk booking up to a few hundred shipments a
month, with a handful of staff signed in and customers checking tracking pages, is nowhere
near the limits of one SQLite file. The site is overwhelmingly read heavy: a tracking page
is reads, and a shipment or a tracking event is one small write entered by one person.
There is no server to install, no credentials to leak, and the whole database is a file you
can copy.

**What is already configured.** `config/database.php` sets three pragmas on the SQLite
connection, and they are what make the above true rather than optimistic:

| Pragma | Value | Why |
| --- | --- | --- |
| `journal_mode` | `WAL` | Readers keep working while a write is in progress, instead of blocking each other. |
| `busy_timeout` | `5000` | A request waits up to five seconds for the write lock instead of failing with "database is locked". |
| `synchronous` | `NORMAL` | The usual pairing with WAL: durable across an application crash, at risk only in a sudden power loss. |

Each is overridable with `DB_JOURNAL_MODE`, `DB_BUSY_TIMEOUT` and `DB_SYNCHRONOUS`, but the
defaults are the ones you want. WAL creates two sidecar files next to the database
(`database.sqlite-wal` and `database.sqlite-shm`); they belong there, and they are in
`.gitignore`.

**Keep other writes off the file.** Set `SESSION_DRIVER=file` and `CACHE_STORE=file`. Left
on `database`, every page view — including every chat poll — writes a session row, and those
writes queue behind each other for no benefit. The application itself is careful about this
too: the chat marks messages as read only when there is actually something to mark, so an
open tracking page polling every eight seconds performs reads and nothing else.

**Protect the file.** The web server's document root must be `public/`, which puts
`database/` outside it. `database/.htaccess` denies web access as a second line of defence,
for hosts where the whole application ends up under `public_html`. Test it after deploying:

```
https://example.com/database/database.sqlite
```

must return 403 or 404. If that URL downloads a file, stop and fix the document root before
going any further — that file is your entire database, customer records included.

**Back it up.** Nobody else is looking after this file. Copying it with `cp` while a write is
in flight can produce a corrupt copy, so use the built in command, which uses SQLite's own
`VACUUM INTO` and is safe on a running site:

```sh
php artisan portlane:backup-database              # writes to database/backups
php artisan portlane:backup-database --path=~/backups --keep=30
```

It runs nightly at 02:30 from the scheduler (section 31) whenever the connection is SQLite,
keeping the last fourteen copies. Two things to do yourself: `storage/app` holds the uploaded
shipment documents and is not part of the database backup, and a backup that only exists on
the server is not a backup — pull a copy off the machine.

Restoring is a file copy, with the site in maintenance mode:

```sh
php artisan down
cp ~/backups/portlane-2026-09-07-023000.sqlite database/database.sqlite
php artisan up
```

**What would make you switch to MySQL.** None of these are hypothetical limits of SQLite as
such; they are the points where a database server starts paying for itself:

- Several people entering shipments and events at the same time, all day, rather than a few
  updates an hour. Writes are serialised, so sustained concurrent writing is where you feel it.
- Tens of thousands of shipments in the archive with heavy searching across them.
- Your host backs up MySQL for you but not your files, and you would rather rely on that.
- You want to move to more than one web server, which a single file cannot serve.

**Switching later is straightforward**, because nothing in the application is written against
one engine: create the MySQL database (section 9), point `.env` at it, run
`php artisan migrate --force`, and re-enter or import your data. Do it before the archive
gets large rather than after.

---

## 63. A free preview deployment

Before buying hosting and a domain, you can put the site on a free platform host
in about ten minutes and send people a link. Two files in the repository exist
only for this: `Dockerfile` and `render.yaml`. Shared hosting does not use them —
that is sections 28 to 31.

**Know what you are getting.** A free instance goes to sleep after fifteen
minutes with no traffic and takes about a minute to wake, and its disk is wiped
on every restart and redeploy. The database is therefore rebuilt from scratch
each time it wakes, with the sample shipments back in place: anything you type
into a preview is gone by the next morning. That is fine for showing the design
and the workflow, and it is why the real site goes somewhere else.

### Steps

1. **Make an application key.** On any machine with a terminal:

   ```sh
   echo "base64:$(openssl rand -base64 32)"
   ```

   Copy the whole line, `base64:` included. Keep it: it encrypts sessions.

2. **Sign up at [render.com](https://render.com)** with your GitHub account and
   give it access to the repository. No card is needed for the free plan.

3. **New → Web Service → pick this repository.** Render reads `render.yaml` and
   fills in the rest. If it asks, the settings are: language **Docker**, branch
   **main**, plan **Free**, health check path **/up**.

4. **Fill in the four values it asks for**, which are deliberately not in the
   repository:

   | Variable | What to put |
   | --- | --- |
   | `APP_KEY` | the line from step 1 |
   | `APP_URL` | `https://your-service-name.onrender.com` |
   | `ADMIN_EMAIL` | the address you will sign in with |
   | `ADMIN_PASSWORD` | at least 8 characters, and not one you use elsewhere |

   Everything else is already set in `render.yaml`.

5. **Deploy.** The first build takes a few minutes. The log ends with the
   migrations running, the sample data being seeded and your administrator being
   created.

6. **Check it.** Open the URL. The homepage, services, FAQ and reviews should
   render; the tracking page finds the sample tracking numbers, which are marked
   as demonstration shipments on screen. Sign in at `/admin/login` with the
   address and password from step 4 and change the password under **Your
   profile**.

7. **Before you send the link.** `APP_DEBUG` is already false, so a mistake shows
   the plain error page rather than a stack trace. Turn off **Allow search
   engines to index the site** under **Site settings → Search and social**, so a
   preview full of sample shipments does not end up in Google under your name.

### What is different from the real thing

| | Free preview | Real hosting |
| --- | --- | --- |
| Data | wiped on every restart | kept, and backed up |
| First request after idle | about a minute | immediate |
| Email | written to the log, nothing sent | real SMTP (section 19) |
| Documents uploaded | lost on restart | kept in `storage/app` |
| Demo data | on, so the site is never empty | removed (section 52) |

### Moving to the real site later

Section 64 is what that costs. Nothing about the preview locks you in: when the domain and
hosting are ready, follow sections 28 to 31, set up a real database (section 9), and enter
your own content, since the preview holds nothing worth migrating. Delete the Render service
when you are done with it, or leave it as a staging copy.

### Other free options

- **A free shared host with a control panel** (InfinityFree and similar) is the
  closest rehearsal for cPanel: PHP 8.3, MySQL, a free subdomain and free SSL,
  and the data persists. There is no SSH, so follow the no SSH procedure in
  section 30 — build on your own machine and upload.
- **Oracle Cloud Always Free** gives you a real VM that does not sleep or lose
  its disk, at the price of setting up nginx, PHP and certificates yourself.
  Closest to production, most work.

---

## 64. What it costs to run

This application is cheap to run on purpose. It needs no database server (SQLite, section
62), no queue worker, no websocket server and no Node process — the front end assets are
built and committed. What is left is PHP, about half a gigabyte of disk and one cron entry,
which is the smallest hosting anybody sells.

Prices below were checked in September 2026 and are in US dollars. Treat them as the shape
of the bill rather than a quote.

### The bill

| | Cheapest that is real | No server administration |
| --- | --- | --- |
| Hosting | Oracle Cloud Always Free VM — **$0** | Shared cPanel — **$24–58/yr** |
| Domain (.com) | **$10/yr** | **$10/yr** |
| Sending email (Brevo free) | **$0** | **$0** |
| Receiving email | Cloudflare Email Routing — **$0** | usually included |
| TLS certificate | Let's Encrypt — **$0** | included |
| Backups | `portlane:backup-database` — **$0** | included |
| **Total, first year** | **~$10** | **~$35** |
| **Total, later years** | **~$10** | **~$58–68** |
| What you give up | you install and patch the server yourself | about $4 a month |

The floor for a real, public site is therefore **the domain, around $10 a year**. Everything
else can genuinely be free at this volume.

The free preview in section 63 costs nothing at all, but it sleeps when idle and loses its
disk on every restart, so it is for showing people the site, not for running it.

### Where the money actually goes

**Domain, ~$10/yr, unavoidable.** Buy where registration and renewal are the same price:
Cloudflare Registrar sells at cost (about $10.44 for .com, no year-two markup), Porkbun and
Spaceship are close. Avoid the registrars advertising $2 for the first year and charging $18
after. Do not save $8 on a `.site` or `.online`: a freight company is judged on its address.

**Hosting, $0–5/mo.** Shared cPanel from about $2 a month for the first year and $4–5 after,
which is what sections 28 to 31 are written for. Oracle Cloud's Always Free tier gives a
permanent virtual machine — currently two ARM cores and 12 GB of memory, cut from four and
24 GB in June 2026 — which is far more than this site needs and costs nothing, in exchange
for installing nginx, PHP and certificates yourself and patching them forever.

**Email, $0.** Brevo's free plan sends 300 emails a day, which this site will not approach,
and gives you SPF and DKIM authentication and a delivery log. Section 19 has the setup.
Receiving is separate: use the mailboxes that come with your hosting, or point your MX
records at Cloudflare Email Routing and have `info@` forwarded to Gmail for nothing.

### Paying with cryptocurrency

The email provider is free, so the only two bills are the domain and the hosting, and both
can be paid in crypto:

| | Accepts | Notes |
| --- | --- | --- |
| **Namecheap** | BTC, via account credit | Domains and cPanel hosting from one account: fund the account balance with crypto, then pay for both from it, including auto renewal. The simplest single-vendor answer. |
| **Dynadot** | BTC, USDT, USDC, at checkout | Domains only, cheap renewals, crypto selectable during checkout rather than as a top up. |
| **Hostinger** | 50+ coins, via CoinGate | Hosting only, and it uses hPanel rather than cPanel, so the control panel screens in section 30 will look different. |
| **Vultr** | BTC, via BitPay | A VPS rather than shared hosting: you administer the server, as with Oracle. |
| **Njalla** | BTC, LTC, XMR, ETH | Privacy focused, around €15/yr, and it registers the domain on your behalf rather than in your name — read what that means for ownership before using it for a company domain. |

**A crypto-payable setup, end to end:** fund a Namecheap account balance with Bitcoin, buy
the domain and the Stellar shared hosting plan from that balance, and send mail through
Brevo's free plan. That is roughly $35 for the first year and $58 for the second, paid
entirely in crypto, with cPanel and the deployment steps in sections 28 to 31 matching what
you see on screen.

Two cautions. Exchange rates move while a payment confirms, so pay from a stablecoin such as
USDT where the provider accepts one. And a provider can stop accepting crypto at any time —
check at the checkout rather than trusting this table, and keep a card or PayPal available
for a renewal you cannot afford to miss.

### Costs that surprise people

- **Shared hosting renewals roughly double.** The advertised price is the first term only.
  Paying for three years up front at the promotional rate is the usual way around it.
- **Domain year two**, for the same reason. Buy where the two prices match.
- **Brevo's 300 a day is shared** across transactional and any marketing sending you add
  later.
- **Oracle reclaims idle Always Free instances** and has already cut the allowance once.
  Free, but not a promise.
- **Nothing here needs a paid SSL certificate, a CDN, or a "performance" add-on.** If a host
  tries to sell you one for a site this size, decline.

---

## Running the tests

```sh
php artisan test
```

The suite runs against an in-memory SQLite database and covers administrator authentication
and rate limiting, shipment creation and unique tracking numbers, tracking lookups, tracking
events and exception rules, customer chat and staff replies, authorisation between roles,
private document access, quote requests, the contact form, review publishing, notification
sending, the launch checklist, brand colour publishing, legal placeholder substitution, the
service artwork fallback, and the settings, tracking number and content formatting helpers.

Quote handling has its own coverage too: the structured form validates and stores the route,
countries must come from the list, the alert email is addressed to reply to the customer, a
quotation sent from the dashboard is emailed and recorded, a representative cannot see or
answer quote requests, extra contact details reach the contact page, footer and tracking page,
and the reviews heading follows the settings.

The role split has its own coverage: a representative cannot reach the shipment, status,
customer, content, settings or audit screens; cannot open or reply to a conversation
belonging to another representative; picks up an unassigned conversation by replying to it;
sees only their own conversations and the waiting queue on their dashboard; and only a master
admin can reassign.

## Licence

This application is released under the MIT licence, as is the Laravel framework it is
built on.
