# Deploying Dani Group (PHP) to Elitehost / cPanel

This is a plain-PHP + MySQL rewrite of the original ASP.NET Core site, built
specifically so it runs on ordinary shared cPanel hosting with no special
setup - just PHP and MySQL, both of which are standard on any cPanel plan.

## What you need

- A cPanel hosting account with PHP 8.0+ and MySQL (any standard Elitehost
  shared plan works - this no longer needs the unmanaged VPS / ASP.NET
  situation we discussed earlier).
- Your domain already pointed at that hosting account (you said this is
  ready).

## 1. Upload the files

Upload the **entire contents** of this folder (not the folder itself) into
`public_html` in cPanel's File Manager, or via FTP/SFTP. When you're done,
`public_html/index.php` should exist directly (not
`public_html/danigroup-php/index.php`).

> The site uses root-absolute links everywhere (`/products.php`,
> `/assets/css/site.css`, etc.), so it must live at the domain root. If you
> need it in a sub-folder instead, every `href="/..."` and `src="/..."` in
> `includes/header.php`, `includes/footer.php`, and each page would need the
> sub-folder prefixed on - ask me and I'll adjust it for you.

## 2. Create the MySQL database

In cPanel:

1. **MySQL Databases** -> create a new database (e.g. `danigroup`). cPanel
   will prefix it automatically, e.g. `cpaneluser_danigroup`.
2. Create a database user with a strong password, and add it to that
   database with **All Privileges**.
3. Note the full database name, username, and password - cPanel always
   prefixes both the database and the username with your cPanel account
   name.

## 3. Configure the app

Edit `includes/config.php` (via File Manager's code editor, or edit locally
and re-upload) and fill in:

- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` - from step 2.
- `SITE_BASE_URL` - your real domain, e.g. `https://www.danigroup.co.za`.
- `YOCO_SECRET_KEY`, `YOCO_WEBHOOK_SECRET` - from your Yoco merchant portal
  (see the payments note below).
- `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` - the address contact-form
  confirmations etc. are sent from.
- Once everything works, set `APP_DEBUG` to `false` so PHP errors aren't
  shown to visitors.

## 4. Import the database schema

In cPanel, open **phpMyAdmin**, select your new database, go to the
**Import** tab, and upload `schema.sql` from this project. This creates all
the tables (empty - no data yet).

## 5. Run the one-time installer

Visit `https://danigroup.co.za/install.php` in your browser once. It will:

- Create the admin login: **admin@danigroup.com / Admin@12345**
  (log in and change this password immediately via My Account -> Manage
  Profile).
- Seed the starter categories (Car Parts, Accessories, Bike Parts) and 3
  sample products, exactly like the original app did on first run.

**Then delete `install.php` from the server.** Leaving it online would let
anyone re-run it.

## 6. File upload folders

`assets/uploads/products` and `assets/uploads/returns` need to be writable
by PHP so product images and return-request photos can be saved. cPanel's
default folder permissions (755, owned by your cPanel user, which is also
who PHP runs as under cPanel's normal setup) are normally already fine -
no action needed unless you see an upload error, in which case set those
two folders to 755 (or 775) via File Manager's permissions dialog.

Product images accept any common picture format (JPG, PNG, WEBP, GIF, BMP,
TIFF, HEIC, HEIF, AVIF, ICO) up to 8 MB - SVG is intentionally excluded for
security reasons (see the comment in `includes/config.php`). If an upload
over a couple of MB fails silently, check cPanel's **MultiPHP INI Editor**
for `upload_max_filesize` and `post_max_size` - some shared plans cap these
below 8 MB by default, and PHP will reject the upload before our own 8 MB
check even runs. Raise both there if needed.

## 7. Yoco payments

This mirrors the original app's Yoco integration, including its caveat:
**double-check the checkout payload/response field names and the webhook
signature verification against your current Yoco developer docs/portal**
before accepting real payments - Yoco's API details can change, and this
was written from documentation rather than a live-tested account.

In your Yoco merchant portal, set the webhook URL to:

```
https://danigroup.co.za/payment/yoco-webhook.php
```

## 8. Email sending

Contact-form and other emails use PHP's built-in `mail()` function, which
on cPanel sends through the server's local Exim/sendmail and normally works
immediately for a domain hosted on that same cPanel account - no SMTP
setup needed. If messages aren't arriving, check cPanel's **Email Deliverability**
tool for your domain (SPF/DKIM records) and your **Track Delivery** log.

## What's included

- Plain PHP, no framework, no Composer dependencies - just upload and run.
- MySQL/MariaDB via PDO.
- Session-based login (replaces ASP.NET Identity) - `users` table with
  `Customer` / `Admin` roles.
- The full storefront: home, product catalog with filters, product details
  with reviews, cart, checkout with Yoco, order history/invoices.
- Dropper services: parcel delivery, furniture moving, and towing request
  forms with live price quotes, matching the original pricing logic.
- Contact form and returns/damage-report form (with photo upload).
- Full admin panel: products (add/edit/delete, with image upload and the
  ability to create a new category on the fly), orders (view, update
  status, **cancel**, and **permanently remove**), returns, and contact
  messages.

## What's different from the ASP.NET version (by necessity)

- No server-side model-binding/validation framework - validation is done
  by hand in each page, matching the same rules as the original.
- No Entity Framework - plain SQL via PDO prepared statements.
- No Razor views/partials - plain PHP templates (`includes/header.php` /
  `includes/footer.php` wrap every page).
- Bootstrap and its JS bundle are loaded from a CDN (cdnjs) rather than
  bundled locally, to keep the upload small and avoid a build step.
