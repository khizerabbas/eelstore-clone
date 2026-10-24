<?php

/*
| Default storefront content. Every value here can be changed in the admin panel (Website section);
| changed values are stored in the site_contents table and override these defaults.
| Placeholders in texts: {store} = store name, {year} = current year.
*/

return [

    'branding' => [
        'logo'             => null,   // uploaded image path (public disk); text logo is used when empty
        'logo_text'        => 'UE',   // letters in the round badge when there is no logo image
        'show_name'        => true,   // show the store name next to the logo
        'tagline'          => 'Solutions for Every Electrical Need',
        'favicon'          => null,
        'placeholder_image' => null, // shown when a product/slide has no image; empty = built-in "No preview available"
        'brand_color'      => '#0f172a', // header, footer, dark buttons (needs white text to be readable)
        'accent_color'     => '#fbbf24', // highlights, badges, main buttons
        'page_background'  => '#f3f4f6',
    ],

    'header' => [
        'topbar_enabled'     => true,
        'topbar_message'     => '',              // e.g. "Free delivery in Lahore on orders over Rs 10,000"
        'topbar_phone_label' => 'Contact Us:',
        'search_placeholder' => 'Search for products',
        'search_all_label'   => 'All categories',
        'nav_home'           => 'Home',
        'nav_categories'     => 'Categories',
        'nav_shop'           => 'Shop',
        'nav_contact'        => 'Contact Us',
        'nav_my_orders'      => 'My Orders',
        'nav_wishlist'       => 'Wishlist',
        'nav_cart'           => 'Cart',
        'show_my_orders'     => true,
        'show_wishlist'      => true,
        'extra_links'        => [],              // [['label' => 'Bulk orders', 'url' => '/pages/bulk-orders']]
    ],

    'home' => [
        'hero_title'         => 'Premium Electrical Supplies & Cables',
        'hero_subtitle'      => 'Genuine wires, cables, lighting, switches and accessories from trusted brands – order online and pay cash on delivery.',
        'hero_button_label'  => 'Shop Now',
        'hero_button_url'    => '/shop',
        'hero_button2_label' => '',
        'hero_button2_url'   => '',
        'hero_empty_text'    => '',              // shown in the slider box when there are no slides
        'benefits_enabled'   => true,
        'benefits'           => [
            ['icon' => 'shield', 'title' => 'Genuine Products', 'text' => '100% original branded items'],
            ['icon' => 'tag', 'title' => 'Best Prices', 'text' => 'Competitive pricing on all items'],
            ['icon' => 'cash', 'title' => 'Cash on Delivery', 'text' => 'Pay when your order arrives'],
            ['icon' => 'phone', 'title' => 'Expert Support', 'text' => 'Call or WhatsApp us anytime'],
        ],
        'section_view_all'   => 'View all →',
        'block_limit'        => 8,               // products per block
        'deals_enabled'      => true,
        'deals_title'        => "Today's Deals",
        'featured_enabled'   => true,
        'featured_title'     => 'Featured Products',
        'new_enabled'        => true,
        'new_title'          => 'New Arrivals',
        'brands_enabled'     => true,
        'brands_title'       => 'Shop by Brand',
    ],

    'footer' => [
        'about_text'        => 'Your trusted electrical store in Lahore for wires, cables, lighting, switches and accessories.',
        'menu_heading'      => 'Menu',
        'menu_links'        => [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Shop', 'url' => '/shop'],
            ['label' => 'My Orders', 'url' => '/my-orders'],
            ['label' => 'Contact Us', 'url' => '/contact-us'],
        ],
        'categories_heading' => 'Categories',
        'categories_limit'   => 6,
        'pages_heading'      => 'Information',
        'contact_heading'    => 'Contact Us',
        'copyright'          => '© {year} {store}. All rights reserved.',
        'bottom_text'        => 'Cash on delivery across Pakistan',
    ],

    'pages' => [
        // Shop / search / category
        'shop_title'          => 'Shop',
        'shop_intro'          => 'Browse our products and filter by category, brand, price and sale.',
        'shop_empty'          => 'No products found with current filters.',
        'search_empty'        => 'No products found matching your search.',
        'category_empty'      => 'No products found in this category yet.',
        'sale_badge'          => 'SALE',
        'add_to_cart'         => 'Add to Cart',
        'product_no_description' => 'High quality product from {store}.',

        // Cart
        'cart_title'          => 'Shopping Cart',
        'cart_empty'          => 'Your cart is currently empty.',
        'cart_checkout_button' => 'Proceed to Checkout',
        'cart_cod_note'       => 'No online payment – pay cash on delivery after we confirm your order.',

        // Wishlist
        'wishlist_title'      => 'Wishlist',
        'wishlist_empty'      => 'Your wishlist is currently empty.',

        // Checkout
        'checkout_title'      => 'Checkout',
        'checkout_intro'      => "Send us your order request – we'll call or WhatsApp you to confirm. Pay cash on delivery.",
        'checkout_submit'     => 'Submit Order Request',
        'checkout_consent'    => 'By submitting you agree that we may contact you on the number above about this order.',
        'checkout_steps_title' => 'How it works',
        'checkout_steps'      => [
            ['text' => 'Submit your order request – no payment needed now.'],
            ['text' => 'We call or WhatsApp you to confirm items and delivery.'],
            ['text' => 'We deliver to your door – you pay cash on delivery.'],
        ],
        'delivery_note'       => 'Delivery charges (if any) are confirmed on call.',

        // After submitting / order pages
        'thankyou_title'      => 'Thank you, {name}! 🎉',
        'thankyou_text'       => 'Your order request {reference} has been sent to our team. We will call or WhatsApp you shortly to confirm it.',
        'thankyou_whatsapp_button' => '💬 Message us on WhatsApp for a faster reply',
        'my_orders_title'     => 'My Orders',
        'my_orders_intro'     => 'Order requests you have placed from this browser.',
        'my_orders_empty'     => 'No order requests found in this browser.',
        'my_orders_note'      => 'This list is saved only in this browser. If you clear your cookies it will disappear – your orders are still safe with us, just contact us with your mobile number.',

        // Contact
        'contact_title'       => 'Contact Us',
        'contact_intro'       => 'Get in touch with {store} for any queries, quotes, or support.',
        'contact_phone_note'  => 'Available during business hours.',
        'contact_email_note'  => 'We usually respond within 1–2 business days.',
        'contact_form_title'  => 'Send us a message',
        'contact_button'      => 'Send Message',
    ],

    // What the customer sees on their order page for each status
    'order_status' => [
        'new'              => 'We have received your order request. Our team will call or WhatsApp you shortly to confirm it.',
        'contacted'        => 'We have reached out to you. Please check your WhatsApp, phone calls and email to confirm your order.',
        'confirmed'        => 'Your order is confirmed and is being prepared. You will pay cash on delivery.',
        'out_for_delivery' => 'Your order is on its way. Please keep cash ready for payment on delivery.',
        'delivered'        => 'Your order has been delivered. Thank you for shopping with us!',
        'cancelled'        => 'This order request was cancelled. Contact us if you think this is a mistake.',
    ],

    'seo' => [
        'home_title'        => '{store} – Electrical Supplies in Lahore',
        'title_suffix'      => ' – {store}',
        'meta_description'  => 'Buy genuine electrical supplies online – wires, cables, lighting, switches and accessories. Order online and pay cash on delivery.',
        'share_image'       => null,  // image used when links are shared on WhatsApp/Facebook
        'google_site_verification' => '',
    ],
];
