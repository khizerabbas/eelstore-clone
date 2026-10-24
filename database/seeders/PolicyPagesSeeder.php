<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * First drafts of the policy pages (Website → Content pages), shown in the footer.
 * Only creates pages that don't exist yet, so running it again never overwrites the owner's edits:
 *   php artisan db:seed --class=PolicyPagesSeeder --force
 * Text in [To confirm: …] is for the owner to fill in or delete.
 */
class PolicyPagesSeeder extends Seeder
{
    public function run(): void
    {
        $settings = SiteSetting::current();

        $store   = e($settings->store_name ?: config('app.name'));
        $phone   = e($settings->contact_phone ?: '[To confirm: phone number]');
        $address = e(str_replace("\n", ', ', (string) $settings->store_address) ?: '[To confirm: shop address]');
        $email   = filled($settings->contact_email) && ! str_ends_with($settings->contact_email, '@example.com')
            ? e($settings->contact_email)
            : '[To confirm: email address]';

        $contact = "call or WhatsApp us on <strong>{$phone}</strong>, email <strong>{$email}</strong>, or visit our shop at {$address}";

        $pages = [
            [
                'slug'             => 'delivery-and-returns',
                'title'            => 'Delivery & Returns',
                'meta_description' => "How {$store} delivers orders with cash on delivery, delivery charges and times, and how returns and exchanges work.",
                'sort_order'       => 1,
                'body'             => <<<HTML
<p>At {$store} every order is <strong>cash on delivery</strong>. You send us an order request on the website, we call or WhatsApp you to confirm the items, price and delivery, and you pay when the parcel reaches you.</p>

<h2>How ordering works</h2>
<ol>
<li>Add products to your cart and send the order request. No online payment is needed.</li>
<li>We contact you on the mobile number you gave us, usually within [To confirm: e.g. 1–2 working hours during shop hours], to confirm availability, the final price and the delivery charge.</li>
<li>After you confirm, we pack and dispatch your order.</li>
<li>You pay the courier or our rider in cash when you receive it.</li>
</ol>
<p>You can follow the status of your request on the <strong>My Orders</strong> page of this website (on the same phone or computer you ordered from).</p>

<h2>Where we deliver</h2>
<ul>
<li><strong>Lahore:</strong> [To confirm: our own rider / courier, which areas].</li>
<li><strong>Other cities in Pakistan:</strong> [To confirm: by courier, e.g. TCS / Leopards / M&amp;P].</li>
<li><strong>Shop pickup:</strong> you are welcome to collect your order from our shop at {$address}.</li>
</ul>

<h2>Delivery time</h2>
<ul>
<li>Lahore: [To confirm: e.g. same day or next working day].</li>
<li>Other cities: [To confirm: e.g. 2–5 working days].</li>
</ul>
<p>Large or bulk orders may take longer; we will tell you the expected date when we confirm your order.</p>

<h2>Delivery charges</h2>
<p>[To confirm: e.g. Rs 200 within Lahore, courier charges for other cities, free delivery above Rs X]. The exact charge is always confirmed with you on the phone <strong>before</strong> we dispatch, so there are no surprises.</p>

<h2>When your parcel arrives</h2>
<p>Please check the parcel in front of the rider before paying. If anything is missing, damaged or not what you ordered, do not accept it (or note the problem with the rider) and contact us straight away.</p>

<h2>Returns and exchanges</h2>
<p>You can return or exchange a product within <strong>[To confirm: 7] days</strong> of delivery if:</p>
<ul>
<li>it is unused and not installed,</li>
<li>it is in its original packaging with all parts, and</li>
<li>you have the order reference (for example UE-100123).</li>
</ul>

<h3>Items we cannot take back</h3>
<ul>
<li>Wire and cable cut to a custom length for you.</li>
<li>Products that have been installed, connected or used.</li>
<li>Products damaged by wrong installation, voltage problems or misuse.</li>
<li>Items specially ordered for you [To confirm].</li>
</ul>

<h3>Faulty or wrong items</h3>
<p>If a product is faulty, damaged in delivery or not what you ordered, tell us within <strong>[To confirm: 48 hours]</strong> of delivery with photos. We will replace it or refund you, and we cover the return delivery cost. Products with a manufacturer's warranty are repaired or replaced according to that brand's warranty terms; we will help you with the claim.</p>

<h3>Refunds</h3>
<p>Once we receive and check the returned item, we refund you [To confirm: in cash at the shop, or by bank transfer / JazzCash / Easypaisa within X working days]. Delivery charges are refunded only if the item was faulty or wrong.</p>

<h2>Cancelling an order</h2>
<p>You can cancel free of charge any time before your order is dispatched. Just {$contact}.</p>

<h2>Questions</h2>
<p>For anything about delivery or returns, {$contact}.</p>
HTML,
            ],
            [
                'slug'             => 'privacy-policy',
                'title'            => 'Privacy Policy',
                'meta_description' => "What personal information {$store} collects when you order or contact us, why we need it and how we keep it safe.",
                'sort_order'       => 2,
                'body'             => <<<HTML
<p>This policy explains what information {$store} ("we", "us") collects when you use this website, why we need it and how we look after it. We only ask for what we need to handle your order or question, and we never sell your information.</p>

<h2>Information we collect</h2>
<h3>When you send an order request</h3>
<ul>
<li>Your name and mobile / WhatsApp number (required).</li>
<li>Your email address, delivery address and any comment you add (optional).</li>
<li>The products, quantities and prices in your order.</li>
</ul>

<h3>When you send a bulk quote request or a contact message</h3>
<ul>
<li>Your name, phone number and/or email, and business name and city if you give them.</li>
<li>Your message, item list and any file you attach (for example a photo or PDF of your list).</li>
</ul>

<h3>Technical information</h3>
<ul>
<li>Your IP address and browser type, saved with orders and messages to protect the shop against spam and fake orders.</li>
<li>Error reports from your browser if a page fails to work, so we can fix it. These do not include passwords or payment details.</li>
</ul>
<p>We do not take online payments, so we never ask for or store card or bank details on this website.</p>

<h2>How we use your information</h2>
<ul>
<li>To contact you about your order or request, confirm the details and deliver it.</li>
<li>To answer your questions and send you a quotation.</li>
<li>To keep records of orders for accounts, returns and warranty claims.</li>
<li>To protect the website from spam and abuse.</li>
</ul>
<p>We will not send you marketing messages unless you ask us to.</p>

<h2>Who we share it with</h2>
<p>Only with the people and services we need to run the shop:</p>
<ul>
<li><strong>Delivery:</strong> our rider or courier company receives your name, phone number and address to deliver your order.</li>
<li><strong>Order alerts:</strong> new orders are sent to the shop owner by email and WhatsApp (through a WhatsApp alert service) so we can respond quickly.</li>
<li><strong>Service providers:</strong> our web hosting and email providers store and send data on our behalf.</li>
<li><strong>Analytics:</strong> if enabled, Google Analytics and/or Meta (Facebook) Pixel collect anonymous information about how visitors use the site. [To confirm: delete this point if you don't use them.]</li>
<li><strong>The law:</strong> when we are required to by Pakistani law or a lawful request from the authorities.</li>
</ul>

<h2>Cookies</h2>
<p>We use a small number of cookies:</p>
<ul>
<li><strong>Essential cookies</strong> that keep your cart and wishlist working and protect forms against misuse.</li>
<li><strong>"My Orders" cookie</strong> that remembers the order requests you sent from this browser, so you can check their status without an account. Clearing your browser cookies removes this list (your orders are not cancelled).</li>
<li><strong>Analytics cookies</strong>, only if analytics are enabled (see above).</li>
</ul>

<h2>How long we keep it</h2>
<p>We keep order records for as long as needed for accounts, returns and warranty, normally [To confirm: e.g. 3 years]. Contact messages and quote requests are deleted when no longer needed. Website backups are kept for a limited time and then deleted automatically.</p>

<h2>How we protect it</h2>
<p>The website uses a secure (https) connection. Only the shop owner and authorised staff can open the admin area, protected by passwords and two-step login. Uploaded files are stored privately and are not publicly visible.</p>

<h2>Your choices</h2>
<p>You can ask us to show you the information we hold about you, correct it, or delete it (unless we must keep it, for example for an open order or our accounts). To do this, {$contact}.</p>

<h2>Changes to this policy</h2>
<p>We may update this policy from time to time. The latest version is always on this page. Last updated: [To confirm: date].</p>
HTML,
            ],
            [
                'slug'             => 'terms-and-conditions',
                'title'            => 'Terms & Conditions',
                'meta_description' => "The terms for ordering from {$store}: order requests, prices, cash on delivery, product information and warranty.",
                'sort_order'       => 3,
                'body'             => <<<HTML
<p>These terms apply when you use this website or order from {$store}, {$address}. By sending an order request you agree to them.</p>

<h2>1. Order requests</h2>
<ul>
<li>An order sent through the website is a <strong>request</strong>. It becomes a confirmed order only after we contact you and you agree the items, price and delivery charge.</li>
<li>We may decline or cancel a request, for example if a product is out of stock, a price on the website is clearly wrong, or we cannot reach you on the number given. If this happens we will tell you, and you pay nothing.</li>
<li>Please give a correct mobile number and address. We cannot be responsible for delays caused by wrong details.</li>
</ul>

<h2>2. Prices</h2>
<ul>
<li>All prices are in Pakistani Rupees (Rs). [To confirm: prices include / do not include GST.]</li>
<li>Prices of wire, cable and other electrical goods change often with copper and market rates. The price confirmed on the phone is the price you pay.</li>
<li>Delivery charges are extra unless we tell you otherwise, and are confirmed before dispatch.</li>
</ul>

<h2>3. Payment</h2>
<p>We accept <strong>cash on delivery</strong>. For large or bulk orders we may ask for an advance payment [To confirm: e.g. bank transfer, JazzCash or Easypaisa], which we will agree with you before dispatch.</p>

<h2>4. Delivery, returns and cancellations</h2>
<p>Delivery, returns, exchanges and cancellations are explained on our <a href="delivery-and-returns">Delivery &amp; Returns</a> page, which is part of these terms.</p>

<h2>5. Product information</h2>
<ul>
<li>We try to show every product accurately, but photos are for illustration. Colour, packaging and design can differ slightly, and manufacturers may change products without notice.</li>
<li>Stock shown on the website is a guide only. We confirm availability when we call you.</li>
<li>If you are unsure whether a product suits your job, please ask us before ordering.</li>
</ul>

<h2>6. Safety and installation</h2>
<p>Electrical products must be installed by a qualified electrician and used according to the manufacturer's instructions and the correct voltage and load. We are not responsible for damage, injury or loss caused by wrong installation, overloading, unstable supply or misuse.</p>

<h2>7. Warranty</h2>
<p>Products that come with a manufacturer's warranty are covered by that brand's own warranty terms. We will help you with warranty claims for products bought from us. The warranty does not cover damage from wrong installation, misuse or voltage problems.</p>

<h2>8. Our responsibility</h2>
<p>If something goes wrong with your order, our responsibility is limited to repairing, replacing or refunding the product concerned, up to the price you paid for it. This does not affect any rights you have under Pakistani law.</p>

<h2>9. Website use</h2>
<p>Please do not misuse this website, for example by sending fake orders or spam, or trying to break its security. The content, logo and photos on this site belong to {$store} or the brands concerned and may not be copied without permission.</p>

<h2>10. Law</h2>
<p>These terms are governed by the laws of Pakistan. Any dispute will be handled by the courts of Lahore.</p>

<h2>11. Changes and contact</h2>
<p>We may update these terms from time to time; the version on this page applies to new orders. Questions? {$contact}.</p>
<p>Last updated: [To confirm: date].</p>
HTML,
            ],
        ];

        foreach ($pages as $page) {
            Page::firstOrCreate(
                ['slug' => $page['slug']],
                $page + ['is_published' => true, 'show_in_footer' => true],
            );
        }
    }
}
