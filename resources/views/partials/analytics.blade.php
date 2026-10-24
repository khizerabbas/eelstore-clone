{{-- Analytics & ads tracking (Website → SEO). Nothing loads unless an ID is set.
     window.ueTrack(event, data) is always defined, so pages can call it safely. --}}
@php
    $ga4   = content('seo.ga4_id');
    $pixel = content('seo.meta_pixel_id');
    $ga4   = is_string($ga4) && preg_match('/^G-[A-Z0-9]{4,20}$/', $ga4) ? $ga4 : null;
    $pixel = is_string($pixel) && preg_match('/^\d{6,20}$/', $pixel) ? $pixel : null;
@endphp
@if($ga4)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4 }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){ dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', @js($ga4));
    </script>
@endif
@if($pixel)
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;
        s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', @js($pixel));
        fbq('track', 'PageView');
    </script>
@endif
<script>
    // event: 'add_to_cart' | 'begin_checkout' | 'lead'   data: { value, items: [{ id, name, price, quantity }], kind }
    window.ueTrack = function (event, data) {
        data = data || {};
        var items = data.items || [];
        var value = Number(data.value || 0);
        try {
            if (typeof window.gtag === 'function') {
                var gaName = { add_to_cart: 'add_to_cart', begin_checkout: 'begin_checkout', lead: 'generate_lead' }[event];
                if (gaName) {
                    window.gtag('event', gaName, {
                        currency: 'PKR', value: value, lead_type: data.kind,
                        items: items.map(function (i) { return { item_id: String(i.id), item_name: i.name, price: Number(i.price), quantity: Number(i.quantity || 1) }; })
                    });
                }
            }
            if (typeof window.fbq === 'function') {
                var fbName = { add_to_cart: 'AddToCart', begin_checkout: 'InitiateCheckout', lead: 'Lead' }[event];
                if (fbName) {
                    window.fbq('track', fbName, {
                        currency: 'PKR', value: value, content_type: 'product',
                        content_ids: items.map(function (i) { return String(i.id); }),
                        content_name: data.kind || (items[0] && items[0].name) || undefined
                    });
                }
            }
        } catch (e) { /* tracking must never break the page */ }
    };
</script>
