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

## 4. What's missing for a working end-to-end store

A complete purchase flow for a Pakistani electrical store looks like this:

```
Browse / Search → Product → Add to cart → Checkout (guest or login)
 → Address + phone → Shipping fee → Payment (COD / JazzCash / Easypaisa / card)
 → Order placed → Confirmation email/SMS/WhatsApp → Admin processes → Shipped (tracking) → Delivered
```

Right now the project covers only the **first three steps**. Everything below is still needed.

### Phase 1 – Make the core purchase work (MVP) 🔴
1. **Fix the bugs in section 3** (cart update, pricing rule, duplicate routes, homepage seeding).
2. **Orders schema**
   - `orders`: order number, user_id (nullable for guests), customer name/phone/email, shipping address, city,
     subtotal, shipping_fee, discount, total, payment_method, payment_status, order status, notes, timestamps.
   - `order_items`: order_id, product_id, then a snapshot of name, SKU, unit price and qty, plus the line total.
   - Status flow: `pending → confirmed → processing → shipped → delivered`, with `cancelled` and `returned` as side branches.
3. **Checkout page** (`/checkout`)
   - Guest checkout: name, phone (with Pakistani format validation, e.g. `03XXXXXXXXX`), email, address, city, notes.
   - Re-check prices and stock on the server. Never trust the price stored in the session.
   - Shipping fee from a simple rule (flat rate per city, or free above a threshold), stored in settings.
   - **Cash on Delivery** as the first payment method. It's the most common way to pay online in Pakistan.
   - Create the order and items in one DB transaction, decrement stock, then clear the cart.
4. **Order confirmation page + email** (a mailable to the customer, plus a copy to the store).
5. **Order tracking** at `/track-order`, looked up by order number + phone.
6. **Add login/register/My Account links** to the storefront header. Add a **My Orders** page for logged-in users.
7. **Merge the cart on login.** Optionally, persist the cart and wishlist to the DB for logged-in users.

### Phase 2 – Admin panel 🔴 (the store can't run without one)
Recommended: **Filament** (`filament/filament`). It works well with Laravel 12 and is fast to build with.
- Products CRUD with **image upload** (`storage:link`, with multiple images in a `product_images` table), SKU, stock, sale price, active/featured flags.
- Categories (including nesting) and companies/brands CRUD.
- Orders: list, filter by status, view, change status, print an invoice or packing slip.
- Homepage sliders and homepage category sections (replaces the "add in admin panel later" placeholder).
- Site settings form: contact info, social links, shipping rules, hero text.
- Contact messages inbox.
- An `is_admin` flag or roles (e.g. `spatie/laravel-permission`) with a protected `/admin` route.
- A dashboard with orders today, revenue, low-stock alerts and pending orders.

### Phase 3 – Online payments & communication 🟠
- Payment gateways common in Pakistan: **JazzCash**, **Easypaisa**, and a card aggregator (**Safepay**, **PayFast** or **XPay**).
  Build them behind a `PaymentGateway` interface, with callback/webhook routes, signature verification, and idempotent updates to `payment_status`.
- Bank transfer option: show the account details and let the customer upload a payment screenshot.
- **WhatsApp**: a floating "Order on WhatsApp" button and a "Ask about this product" link on the product page (`wa.me` with a pre-filled product name).
  Optionally, order notifications through the WhatsApp Business API.
- SMS order confirmation (local SMS gateway).
- Queue all emails (`QUEUE_CONNECTION=database` is already set, so run `queue:work` in production).

### Phase 4 – Catalog & UX features (matching sites like eelstore / Powerhouse Express) 🟡
- **Product variants**: color (white/black), wattage, colour temperature (3000K/4000K/6000K), wire size (1.5mm/2.5mm/4mm) and roll length.
  Use a `product_variants` table with its own price, stock and SKU.
- A product **specs table** (key/value attributes), datasheet/PDF downloads, and a warranty note.
- **Multiple images** with a gallery/zoom on the product page.
- **Brands page** and a "Shop by brand" logo strip on the home page.
- Homepage blocks: "Today's best deals", "New arrivals", "Featured" (the `is_featured` flag is already there), and a top promo bar.
- A **mega menu** for nested categories (`parent_id` already exists).
- **Bulk / trade orders**: a quote request form for electricians and contractors. This is common in this market.
- Coupons / discount codes.
- Reviews & ratings (only from verified buyers).
- Recently viewed products, plus "Customers also bought".
- Search autocomplete.
- Stock badges (In stock / Low stock / Out of stock) and a "Notify me when back in stock" option.
- Unit pricing where it applies (per metre or per roll for cables).

### Phase 5 – Content, SEO & trust 🟡
- Static pages: About Us, Shipping Policy, Return & Refund Policy, Warranty Policy, Privacy Policy, Terms, FAQ.
  Managed through a simple `pages` table in the admin panel.
- SEO: per-page `<title>`/meta description, Open Graph tags, canonical URLs, `sitemap.xml` (`spatie/laravel-sitemap`), Product JSON-LD schema, and clean slugs (already done).
- Google Analytics 4 / Meta Pixel with ecommerce events (view_item, add_to_cart, begin_checkout, purchase).
- Trust elements: COD available, delivery across Pakistan, genuine products, easy returns, plus the store's physical address and map (already in settings).
- Newsletter signup.

### Phase 6 – Quality, security & deployment 🟢
- **Tests**: feature tests for the shop filters, cart (add/update/remove), checkout (stock and price checks), and order creation, plus payment callback tests.
- Rate-limit the cart/wishlist/checkout POST routes and add server-side authorization to admin routes.
- Image optimization (WebP, responsive sizes) and lazy-loading.
- Caching: cache category/menu queries and site settings, with eager loading to avoid N+1 queries (the homepage runs one query per section).
- Production `.env`: `APP_ENV=production`, `APP_DEBUG=false`, real SMTP, HTTPS, `php artisan optimize`, scheduler and queue worker running under Supervisor/cron.
- Hosting: a VPS or managed Laravel host (Forge / Ploi / cPanel with SSH), MySQL backups (`spatie/laravel-backup`), and error monitoring (Sentry / Flare).
- Replace the README with real setup instructions (see below).

## 5. Suggested order of work

| # | Task | Size |
|---|---|---|
| 1 | Fix the cart update bug, the single price rule, duplicate routes and homepage seeding | S |
| 2 | Account links in the header, footer driven by settings | S |
| 3 | Orders + order_items migrations/models | S |
| 4 | Checkout page with COD, stock check, confirmation page + email | M |
| 5 | Filament admin: products (with images), categories, brands, orders, settings, sliders | M–L |
| 6 | Order tracking and My Orders | S |
| 7 | Policy pages, SEO basics, WhatsApp button | S |
| 8 | Tests for cart/checkout/orders | M |
| 9 | Deploy to staging, then production | M |
| 10 | JazzCash / Easypaisa / card gateway | M |
| 11 | Variants, specs, reviews, coupons, bulk quote | L |

When items 1–9 are done, a customer can find a product, order it with Cash on Delivery and get a confirmation,
and the owner can manage products and process the order from the admin panel. That's a working store end to end.

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
