# Usman Electronics – Project Status & Roadmap

_Last reviewed: 2026-10-24_

## 1. What this project is

**Usman Electronics** (repo: `eelstore-clone`) is an online storefront for an electrical-goods shop in
Madina Electric Market, Shah Alam Gate, Lahore. It sells wires & cables, lighting, switches/sockets and
accessories, with prices in **PKR (Rs)**. The look and flow follow Pakistani electrical e-stores
such as eelstore.pk, Powerhouse Express, and the Pakistan Cables e-store.

**Stack**

| Layer | Tech |
|---|---|
| Backend | Laravel 12, PHP 8.2 |
| Auth scaffolding | Laravel Breeze (Blade) |
| Frontend | Blade templates, Tailwind CSS, Alpine.js, Vite |
| Database | MySQL (`usman_electronics`) via XAMPP |
| Sessions / cache / queue | `database` driver |
| CI | GitHub Action that rebuilds `docs/chatgpt-context.txt` on every push to `main` |

## 2. What is done

### Storefront (public)
| Feature | Where | Notes |
|---|---|---|
| Home page | `HomeController@index`, `home.blade.php` | Hero slider (up to 3 slides from `homepage_sliders`), up to 3 category sections (8 latest products each) from `homepage_category_sections` |
| Shop / listing page | `ProductController@index`, `shop/index.blade.php` | Filters for category, company (brand nested under category), on-sale, and price range. Sort by name, latest, or price. Choice of 12/20/40/60 per page. Paginated |
| Category URLs | `CategoryController@show` | `/category/{slug}` redirects to `/shop?categories[]=id` |
| Product detail | `ProductController@show`, `product/show.blade.php` | Product info plus 4 related products from the same category |
| Search | `ProductController@search` | Searches name and short/long description, with an optional category filter from the header dropdown |
| Cart | `CartController` | Session-based. Add (AJAX + form), update, remove, clear, and an order summary |
| Wishlist | `WishlistController` | Session-based toggle (AJAX), remove, clear |
| Contact page | `ContactController` | Saves to `contact_messages` and emails the store. Honeypot field, rate limit (5 per 10 min per IP), `strip_tags` on input |
| Global layout | `layouts/frontend.blade.php` | Top bar, header search with category dropdown, cart/wishlist counts, categories menu, footer, mobile menu |
| Site settings | `SiteSetting::current()` | Store name, phone, email, address, social links, map, contact recipient. Shared with all views by a view composer in `AppServiceProvider` |

### Accounts
- Breeze login, register, forgot/reset password, email verification and profile pages all exist.
- The storefront header has **no login/account links**, so customers can't reach these pages from the shop.

### Data model
`categories` (has `parent_id` for nesting), `companies` (brands), `products` (price, sale_price,
is_on_sale, stock, sku, is_featured, single `image`), `site_settings`, `contact_messages`,
`homepage_sliders`, `homepage_category_sections`, plus the Laravel defaults (`users`, `sessions`, `jobs`, `cache`).

Seeders fill in the categories (wires, lighting, …), two demo companies and demo products.

## 3. Known bugs & technical debt (fix these first)

> ✅ Items 1–4 were fixed on 2026-10-24 and are covered by `tests/Feature/CartTest.php`.

1. **Updating a cart quantity breaks the cart.** `CartController@update` stores a plain integer
   (`$cart[$id] = $qty`), but `index()` and `add()` expect an array
   (`['quantity' => .., 'price' => ..]`). After any update, the line shows quantity 0 and subtotal 0.
2. **Two price rules disagree.** The cart stores `sale_price ?? price`, so it ignores `is_on_sale`. The cart view
   shows `getDisplayPrice()`, which checks `is_on_sale`. Shop filters and sorting use
   `COALESCE(sale_price, price)`. Pick one rule, such as an `effective_price` accessor, and use it everywhere.
3. **Homepage sections are empty on a fresh install.** The `homepage_category_sections` migration fills in
   rows from `categories`, but migrations run before the seeders, so it finds no categories. Move that
   step into a `HomepageSeeder`.
4. **Duplicate routes** in `routes/web.php`: `/`, `/category/{slug}` and `/product/{slug}` are each declared twice.
5. `home_hero_title` / `home_hero_subtitle` exist in the DB but aren't in `SiteSetting::$fillable`.
6. The **footer hardcodes** the phone, email, category links (`#` for Switch & Socket and Accessories) and social links
   (`#`). It should read them from `$settings` and the categories.
7. **There are no product images.** Cards, the product page and the cart show placeholders, even though `products.image` exists.
8. The **checkout button is a placeholder.** The cart has no next step.
9. The contact form falls back to `test@test.com` and silently swallows mail errors. At minimum, log them.
10. **Stock isn't checked.** You can add more items than `stock` allows, or add out-of-stock products.
11. The only tests are Breeze's defaults. Nothing covers the shop, cart or wishlist.
12. `welcome.blade.php` and `category/show.blade.php` are unused.
13. Search uses `LIKE %q%` on three columns. That's fine for now, but it will slow down as the catalog grows. A MySQL FULLTEXT index or Laravel Scout would scale better.

## 4. Product direction (decided 2026-10-24)

- **No online payment and no customer accounts.** Every order is **Cash on Delivery**, confirmed by phone or WhatsApp.
- Checkout is an **order request**. The customer submits their name and phone (both required), plus email, address and a comment (all optional).
  The owner gets the request by **email + WhatsApp**, contacts the customer, confirms the order and delivers it.
- Customers see their past requests and status on a **browser-only "My Orders" page**, using a long-lived cookie with no login.
  If they clear their cookies the list is gone. That's acceptable, because the owner keeps everything in the database.
- An **admin panel** manages everything: orders, catalog, homepage, settings and content.
- Every phase has to keep the site **SEO-friendly and secure**.

The customer journey:

```
Cart → /checkout (name*, phone*, email, address, comment)
  → Order saved (reference UE-XXXXXX + secret tracking token, items and prices snapshotted)
  → Queued notifications: admin email + WhatsApp to owner (+ confirmation email to the customer if they gave one)
  → Thank-you page + "Message us on WhatsApp" button (pre-filled with the order reference)
  → /my-orders (cookie) and /orders/{token} (bookmarkable) show status:
      New → Contacted ("check your WhatsApp/email") → Confirmed → Out for delivery → Delivered  (or Cancelled)
  → The owner moves the order through these statuses in the admin panel
```

## 5. Roadmap

### Step 1 – Order request flow ✅ (done 2026-10-24)
> Built: `CheckoutController`, `OrderController`, `App\Actions\PlaceOrderRequest`, `App\Support\{Cart, OrderHistory, PakistaniPhone}`,
> `App\Services\WhatsApp\*` (log / callmebot / cloud_api), `config/orders.php`. Tested in `tests/Feature/CheckoutTest.php`.
> **To go live:** set `ORDER_ADMIN_EMAIL`, real SMTP `MAIL_*`, `WHATSAPP_DRIVER=callmebot` + `CALLMEBOT_PHONE`/`CALLMEBOT_API_KEY`,
> and keep a queue worker running (`php artisan queue:work`). Without a worker, emails and WhatsApp alerts wait in the `jobs` table.

- `orders` table: reference, public token, name, phone (stored normalised as `+92…`), email, address, comment, subtotal, item count,
  status, status timestamps, internal admin note, IP and user agent. An `order_items` table holds a snapshot of product name, SKU, unit price, qty and line total.
- `/checkout` page with a cart summary and the form. The server re-prices every item from the DB and rejects an empty cart, inactive products and silly quantities.
- Pakistani phone validation and normalisation (`03XXXXXXXXX`, `3XXXXXXXXX`, `+92…`, `0092…`).
- Anti-spam: honeypot, rate limit per IP and per phone, and Cloudflare Turnstile switched on through config.
- Notifications are **queued** so checkout stays fast:
  - Admin email (Markdown mailable): customer details, an items table, the total, and one-tap **Call** / **WhatsApp** links to the customer.
  - **WhatsApp to the owner** through a swappable `WhatsAppNotifier` with `log`, `callmebot` and `cloud_api` drivers, chosen in `.env`.
  - A confirmation email to the customer with their tracking link, sent only if they gave an email.
- Thank-you page, `/orders/{token}` status page with a timeline, and `/my-orders` (an encrypted cookie holding up to 20 tokens, kept for 1 year).
  Both pages are `noindex`. A **Re-order** button puts the same items back in the cart.
- Feature tests covering validation, pricing, the notifications sent, the cookie, and the status page.

### Step 2 – Admin panel (Filament 5) ✅ (done 2026-10-24)
> Built at `/admin`: dashboard (stats + latest requests); order requests (status tabs, WhatsApp/Call, Mark contacted,
> Change status with notes, edit, packing slip); products (main image + gallery, sale price, stock, SEO); categories
> (nesting, image, SEO; non-empty categories can't be deleted); brands; home slider; home sections; content pages;
> contact inbox; admin users (Owner only); store settings (Owner only). Breeze customer auth removed – only admins log in.
> Roles: Owner / Staff (`App\Policies\*`). 2FA via authenticator app (required in production, `ADMIN_REQUIRE_2FA`).
> Create admins with `php artisan app:create-admin`. Rich text is rendered through `App\Support\RichText` (sanitised).
> Not done yet (later): activity log of who changed what; storefront pages/footer that show the CMS pages (Step 3).
>
> **Server requirements found during this step:** PHP extensions `intl` and `gd`; after deploy run
> `php artisan storage:link` and `php artisan filament:assets` (published assets are gitignored).

- **Lock down authentication first.** Remove Breeze's public register, profile and customer login. Only admins log in, at `/admin`,
  with login throttling and Filament's built-in **2FA**. Roles: Owner and Staff.
- **Dashboard**: new requests, requests waiting for contact, today/this week, top products, low stock.
- **Orders**: list and filter by status, view items, Call/WhatsApp buttons, change status (with a timestamp), internal notes, print a packing slip.
- **Catalog**: products (several images, stock, sale, featured, SEO title and description), categories (nested, image, SEO), brands (logo).
- **Homepage**: sliders, category sections, featured/deals blocks.
- **Settings**: store contact details, socials, WhatsApp number, order-notification recipients, footer, default SEO and analytics IDs.
- **Content**: CMS pages (About, Delivery & COD, Returns, Warranty, Privacy, Terms, FAQ) and the contact messages inbox.
- Activity log of who changed what.

### Step 3 – Fully dynamic storefront 🟡 (mostly done 2026-10-24)
> **Done:** admin "Website" section – Store & contact, Branding & theme (logo, favicon, tagline, brand/accent/background
> colours with readability checks), Header & menu (top bar, labels, extra links), Home page (hero, buttons, benefits strip),
> Home slider, Home sections, Footer (about, links, headings, copyright), Page texts (every storefront message, checkout
> steps, thank-you text, order-status messages), Content pages (public at `/pages/{slug}`), SEO (titles, description,
> share image, Google verification). Content lives in `site_contents` with defaults in `config/site_content.php`
> (`content('key')` helper, cached). Theme colours are CSS variables used by Tailwind (`brand`, `accent`, `on-accent`,
> `accent-ink`, `page`). Product images + gallery now show on cards, product page and cart.
> **Also done:** WebP copies of every upload (thumb 480 px / large 1280 px, `app:optimize-images` to backfill; replaced
> or deleted images are cleaned up), "No preview available" placeholder for missing/broken images (custom one uploadable),
> real indexable category pages (`/category/{slug}`, includes sub-categories).
> **Also done:** brand pages (`/brand/{slug}`), home blocks (Today's deals, Featured, Shop by brand, New arrivals – each switchable),
> admin activity log (Settings → Activity log), app timezone Asia/Karachi.
> **Still to do:** wire length estimator, bulk quote form.

- Real product images (WebP, responsive sizes, lazy loading, alt text) and a product gallery.
- Footer, menus, contact details and socials all read from settings and categories. Nothing is hardcoded.
- **Proper category pages.** `/category/{slug}` should render its own indexable page with filters, not redirect to `/shop?categories[]=…`.
- Brand pages, plus "Today's deals", "New arrivals" and "Featured" blocks on the home page.
- Wire/cable length estimator and a "Bulk quote" form for electricians (both lead into the same order-request flow).

### Step 4 – SEO 🟡 (mostly done 2026-10-24)
> **Done:** titles/descriptions/canonical/Open Graph on every page, editable SEO settings, JSON-LD (Store, Product + PKR
> Offer, BreadcrumbList), dynamic `sitemap.xml` + `robots.txt` (blocks everything outside production), `noindex` on cart,
> checkout, orders, wishlist, search and filter/sort variations. **Left:** connect Google Search Console + Business Profile
> after launch, measure Core Web Vitals on the live server.
- Unique `<title>` and meta description per page, canonical URLs, Open Graph and Twitter cards.
- JSON-LD: `LocalBusiness`/`ElectronicsStore` (Lahore address, hours, phone), `Product` + `Offer` (PKR, availability), `BreadcrumbList`.
- An automatically built `sitemap.xml` and a correct `robots.txt`. `noindex` on cart, checkout, orders, search and filter combinations.
- Performance and Core Web Vitals: cached menus and settings, eager loading, optimised images. Link the Google Business Profile.

### Step 5 – Security hardening & launch 🟡 (hardening done 2026-10-24, launch pending)
> **Done:** security headers on storefront + admin (nosniff, SAMEORIGIN, Referrer-Policy, Permissions-Policy, COOP, HSTS on
> HTTPS in production, no X-Powered-By), rate limits on every POST route, uploads folder can't run scripts
> (`storage/app/public/.htaccess`), JPG/PNG/WebP-only uploads, policies on every admin resource, 2FA, clean composer/npm audits,
> Breeze leftovers removed. **Left for launch:** a Content-Security-Policy (needs testing against Alpine/Livewire),
> backups, error monitoring, HTTPS + `.env` production values, queue worker/cron.
- `APP_DEBUG=false`, HTTPS with HSTS, secure session cookies, and security headers (CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy).
- Rate limits on every POST route. Strict upload validation (re-encode images, block SVG/PHP). Policies on every admin resource.
- `composer audit` / `npm audit`, DB backups (`spatie/laravel-backup`), error monitoring, and a queue worker plus scheduler (cron on shared hosting).
- Cleanup: remove `welcome.blade.php` and the leftover Breeze customer views.

> **Deployment:** the complete first-launch checklist (server, php.ini, `.env`, cron, commands, clean-up) is in [docs/GO_LIVE.md](GO_LIVE.md).

## 6. Local setup (current)

```bash
composer install
npm install
cp .env.example .env        # set DB_* for XAMPP MySQL (database: usman_electronics)
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run dev                 # or npm run build
php artisan serve           # or browse via http://localhost/eelstore-clone/public
```
