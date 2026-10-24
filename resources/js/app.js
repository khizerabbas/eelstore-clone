import './bootstrap';
import noUiSlider from 'nouislider';
import 'nouislider/dist/nouislider.css';
import Alpine from 'alpinejs';

// ----- Error reporting → admin Settings → Error log -----
// Reports are kept in localStorage when the visitor is offline and sent when they're back online.
const ERROR_ENDPOINT = (document.querySelector('meta[name="error-report-url"]') || {}).content;
const PENDING_KEY = 'ue_pending_error_reports';
let reportsThisPage = 0;

const readPending = () => {
    try { return JSON.parse(localStorage.getItem(PENDING_KEY) || '[]'); } catch { return []; }
};
const writePending = (items) => {
    try { localStorage.setItem(PENDING_KEY, JSON.stringify(items.slice(-10))); } catch { /* storage full/blocked */ }
};

const sendReport = (report) => {
    if (!ERROR_ENDPOINT) return Promise.resolve();

    return fetch(ERROR_ENDPOINT, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(report),
        keepalive: true,
    }).then((response) => {
        if (!response.ok && response.status !== 429 && response.status !== 422) throw new Error('report failed');
    });
};

window.reportClientError = (report) => {
    if (reportsThisPage++ >= 5) return; // never flood

    const full = { page: window.location.href, level: 'error', ...report };

    if (!navigator.onLine) {
        writePending([...readPending(), full]);
        return;
    }

    sendReport(full).catch(() => writePending([...readPending(), full]));
};

const flushPending = () => {
    const pending = readPending();
    if (!pending.length || !navigator.onLine) return;
    writePending([]);
    pending.forEach((report) => sendReport(report).catch(() => {}));
};

window.addEventListener('online', flushPending);
flushPending();

window.addEventListener('error', (event) => {
    if (!(event.error || event.message) || event.target instanceof HTMLElement) return; // resource errors handled elsewhere
    window.reportClientError({
        type: event.error?.name || 'JavaScript error',
        message: String(event.message || event.error),
        source: event.filename,
        line: event.lineno,
        stack: event.error?.stack,
    });
});

// Readable text for whatever was thrown/rejected (objects become JSON instead of "[object Object]")
const describe = (reason) => {
    if (reason instanceof Error) return reason.message || reason.name;
    if (typeof reason === 'string') return reason;
    try { return JSON.stringify(reason).slice(0, 500); } catch { return String(reason); }
};

window.addEventListener('unhandledrejection', (event) => {
    window.reportClientError({
        type: 'Unhandled promise rejection',
        message: describe(event.reason) || 'Unknown',
        stack: event.reason?.stack,
    });
});

document.addEventListener('DOMContentLoaded', () => {
    // Mobile header: menu and search panels (opening one closes the other)
    const panels = [
        [document.getElementById('mobile-menu-toggle'), document.getElementById('mobile-menu-panel')],
        [document.getElementById('mobile-search-toggle'), document.getElementById('mobile-search-panel')],
    ].filter(([toggle, panel]) => toggle && panel);

    panels.forEach(([toggle, panel]) => {
        toggle.addEventListener('click', () => {
            const opening = panel.classList.contains('hidden');

            panels.forEach(([otherToggle, otherPanel]) => {
                otherPanel.classList.add('hidden');
                otherToggle.setAttribute('aria-expanded', 'false');
            });

            if (opening) {
                panel.classList.remove('hidden');
                toggle.setAttribute('aria-expanded', 'true');
                panel.querySelector('input[type="search"]')?.focus();
            }
        });
    });

    // Auto-check parent category when a company is checked
    const companyCheckboxes = document.querySelectorAll('.company-checkbox');

    companyCheckboxes.forEach((cb) => {
        cb.addEventListener('change', () => {
            if (cb.checked) {
                const catId = cb.getAttribute('data-category-id');
                if (!catId) return;

                const parentCategory = document.querySelector(
                    '.category-checkbox[data-category-id="' + catId + '"]'
                );

                if (parentCategory) {
                    parentCategory.checked = true;
                }
            }
        });
    });

    // ----- Price slider using noUiSlider -----
    const sliderEl = document.getElementById('price-slider');

    if (sliderEl && typeof noUiSlider !== 'undefined') {
        const min = parseFloat(sliderEl.dataset.min || '0');
        const max = parseFloat(sliderEl.dataset.max || '0');
        const currentMin = parseFloat(sliderEl.dataset.currentMin || String(min));
        const currentMax = parseFloat(sliderEl.dataset.currentMax || String(max));

        const minHidden = document.getElementById('min_price_input');
        const maxHidden = document.getElementById('max_price_input');
        const minLabel = document.getElementById('price-min-label');
        const maxLabel = document.getElementById('price-max-label');

        noUiSlider.create(sliderEl, {
            start: [currentMin, currentMax],
            connect: true,
            range: {
                min: min,
                max: max,
            },
            step: 1,
        });

        sliderEl.noUiSlider.on('update', (values) => {
            const vMin = Math.round(parseFloat(values[0]));
            const vMax = Math.round(parseFloat(values[1]));

            if (minHidden) minHidden.value = String(vMin);
            if (maxHidden) maxHidden.value = String(vMax);
            if (minLabel) minLabel.textContent = vMin.toLocaleString();
            if (maxLabel) maxLabel.textContent = vMax.toLocaleString();
        });
    }

    // ----- Helper: Toast -----
    function showToast(message) {
        const toast = document.getElementById('toast');
        if (!toast) return;

        toast.textContent = message;
        toast.classList.remove('hidden');
        // trigger reflow for CSS transition
        void toast.offsetWidth;
        toast.classList.remove('opacity-0');

        clearTimeout(window.__toastTimeout);
        window.__toastTimeout = setTimeout(() => {
            toast.classList.add('opacity-0');
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 300);
        }, 2000);
    }

    // ----- AJAX helpers -----
    const csrfTokenEl = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenEl ? csrfTokenEl.getAttribute('content') : '';

    async function ajaxSubmitForm(form, type) {
        const action = form.getAttribute('action');
        const method = (form.getAttribute('method') || 'POST').toUpperCase();
        const formData = new FormData(form);

        try {
            const response = await fetch(action, {
                method,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            if (!response.ok) {
                window.reportClientError({
                    type: 'Request failed',
                    level: response.status === 419 || response.status === 429 ? 'warning' : 'error',
                    message: `${type} request returned HTTP ${response.status}`,
                    details: { action, status: response.status, product_id: form.getAttribute('data-product-id') },
                });
                showToast(response.status === 419
                    ? 'Your session expired. Please refresh the page and try again.'
                    : 'Something went wrong. Please try again.');
                return;
            }

            const data = await response.json();

            if (type === 'cart' && data.product && typeof window.ueTrack === 'function') {
                window.ueTrack('add_to_cart', {
                    value: data.product.price * data.product.quantity,
                    items: [data.product],
                });
            }

            // Update counts in header if present
            if (type === 'cart' && typeof data.cart_count !== 'undefined') {
                document.querySelectorAll('[data-cart-count]').forEach((el) => {
                    el.textContent = data.cart_count;
                    el.classList.toggle('hidden', el.hasAttribute('data-hide-when-empty') && Number(data.cart_count) === 0);
                });
            }


            if (type === 'wishlist' && typeof data.wishlist_count !== 'undefined') {
                document.querySelectorAll('[data-wishlist-count]').forEach((el) => {
                    el.textContent = data.wishlist_count;
                });
            }


            // Toggle heart icon for wishlist
            if (type === 'wishlist' && typeof data.in_wishlist !== 'undefined') {
                const productId = form.getAttribute('data-product-id');

                // Update all hearts for this product (cards + detail page)
                const heartSpans = document.querySelectorAll(
                    'form[data-ajax="wishlist"][data-product-id="' + productId + '"] .wishlist-heart'
                );

                heartSpans.forEach((heartSpan) => {
                    if (data.in_wishlist) {
                        heartSpan.textContent = '❤';
                        heartSpan.classList.remove('text-gray-400');
                        heartSpan.classList.add('text-red-500');
                    } else {
                        heartSpan.textContent = '♡';
                        heartSpan.classList.remove('text-red-500');
                        heartSpan.classList.add('text-gray-400');
                    }
                });

                // Update any wishlist labels (only exists on detail page)
                const labelSpans = document.querySelectorAll(
                    'form[data-ajax="wishlist"][data-product-id="' + productId + '"] .wishlist-label'
                );

                labelSpans.forEach((labelSpan) => {
                    labelSpan.textContent = data.in_wishlist
                        ? 'Added to Wishlist'
                        : 'Add to Wishlist';
                });
            }




            if (data.message) {
                showToast(data.message);
            } else if (type === 'cart') {
                showToast('Added to cart');
            } else if (type === 'wishlist') {
                showToast('Wishlist updated');
            }
        } catch (err) {
            // Network down / blocked request
            window.reportClientError({
                type: navigator.onLine ? 'Network error' : 'Offline',
                level: navigator.onLine ? 'error' : 'warning',
                message: `${type} request could not reach the server: ${err.message}`,
                details: { action, product_id: form.getAttribute('data-product-id') },
            });
            showToast(navigator.onLine
                ? 'Something went wrong. Please try again.'
                : 'You are offline. Please check your internet connection.');
        }
    }

    // ----- Attach AJAX listeners to cart & wishlist forms -----
    document.querySelectorAll('form[data-ajax="cart"]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            ajaxSubmitForm(form, 'cart');
        });
    });

    document.querySelectorAll('form[data-ajax="wishlist"]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            ajaxSubmitForm(form, 'wishlist');
        });
    });

    const slider = document.querySelector('[data-hero-slider]');
    if (!slider) return;

    const slides = Array.from(slider.querySelectorAll('[data-hero-slide]'));
    const dots = Array.from(document.querySelectorAll('[data-hero-dot]'));

    if (slides.length <= 1) return;

    let current = 0;

    const setActive = (index) => {
        slides.forEach((slide, i) => {
            if (i === index) {
                slide.classList.remove('opacity-0', 'pointer-events-none');
                slide.classList.add('opacity-100');
            } else {
                slide.classList.remove('opacity-100');
                slide.classList.add('opacity-0', 'pointer-events-none');
            }
        });

        dots.forEach((dot, i) => {
            if (i === index) {
                dot.classList.add('bg-accent');
                dot.classList.remove('bg-slate-600');
            } else {
                dot.classList.remove('bg-accent');
                dot.classList.add('bg-slate-600');
            }
        });
    };

    setActive(current);

    setInterval(() => {
        current = (current + 1) % slides.length;
        setActive(current);
    }, 5000);


});



// Broken/missing images → show the "No preview available" placeholder (see components/store-image.blade.php)
const useImageFallback = (img) => {
    const fallback = img.dataset.fallback;

    if (fallback && img.getAttribute('src') !== fallback) {
        img.src = fallback;
    }
};

document.addEventListener('error', (event) => {
    if (event.target instanceof HTMLImageElement) {
        useImageFallback(event.target);
    }
}, true);

// Images that already failed before this script loaded
document.querySelectorAll('img[data-fallback]').forEach((img) => {
    if (img.complete && img.naturalWidth === 0) {
        useImageFallback(img);
    }
});

// Shop: the phone and desktop sort/size dropdowns mirror each other, then apply right away
document.addEventListener('change', (event) => {
    const select = event.target.closest?.('select[data-sync-submit]');
    if (!select || !select.form) return;

    document.querySelectorAll(`select[data-sync-submit][name="${select.name}"]`).forEach((other) => {
        other.value = select.value;
    });

    select.form.requestSubmit();
});

// Forms sent back with errors: bring the first wrong field into view (important on phones, where it may be
// far below the top), and drop the red state as soon as the visitor edits that field
const firstInvalid = document.querySelector('[aria-invalid="true"]');
if (firstInvalid) {
    firstInvalid.scrollIntoView({ block: 'center' });
    firstInvalid.focus({ preventScroll: true });
}

document.addEventListener('input', (event) => {
    const field = event.target;
    if (field.getAttribute?.('aria-invalid') !== 'true') return;
    field.removeAttribute('aria-invalid');
    field.classList.remove('!border-red-500', 'bg-red-50/40');
});

// Cart quantity box: apply a typed number without needing a separate "Update" button
document.addEventListener('change', (event) => {
    const input = event.target.closest?.('input[data-submit-on-change]');
    if (input?.form && input.value !== '') input.form.requestSubmit();
});

// Ask before destructive form actions (e.g. "Clear cart")
document.addEventListener('submit', (event) => {
    const message = event.target.getAttribute?.('data-confirm');
    if (message && !window.confirm(message)) event.preventDefault();
}, true);

// Shop filters: leave the price out of the URL when the slider covers the full range
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-shop-filters')) return;

    const slider = document.getElementById('price-slider');
    const min = form.querySelector('#min_price_input');
    const max = form.querySelector('#max_price_input');

    if (slider && min && max
        && Number(min.value) <= Number(slider.dataset.min)
        && Number(max.value) >= Number(slider.dataset.max)) {
        min.disabled = true;
        max.disabled = true;
    }
});

// Prevent double submission (e.g. checkout): disable the submit button once the form is sent
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-disable-on-submit')) {
        return;
    }

    // Offline: don't send the browser to an error page – tell the visitor and keep the form filled in
    if (!navigator.onLine) {
        event.preventDefault();
        window.reportClientError({
            type: 'Offline',
            level: 'warning',
            message: 'Form submitted while offline: ' + (form.getAttribute('action') || ''),
        });
        alert('You seem to be offline. Please check your internet connection and press the button again – your details are still filled in.');
        return;
    }

    form.querySelectorAll('button[type="submit"]').forEach((button) => {
        button.disabled = true;
        button.textContent = 'Sending…';
    });
});

// Product page gallery + full-screen viewer (swipe, arrows, Esc)
Alpine.data('productGallery', (images) => ({
    images,
    index: 0,
    open: false,
    startX: null,
    next() { this.index = (this.index + 1) % this.images.length; },
    prev() { this.index = (this.index - 1 + this.images.length) % this.images.length; },
    openViewer() { this.open = true; document.body.classList.add('overflow-hidden'); },
    close() { this.open = false; document.body.classList.remove('overflow-hidden'); },
    onKey(event) {
        if (!this.open) return;
        if (event.key === 'Escape') this.close();
        if (event.key === 'ArrowRight') this.next();
        if (event.key === 'ArrowLeft') this.prev();
    },
    touchStart(event) { this.startX = event.changedTouches[0].clientX; },
    touchEnd(event) {
        if (this.startX === null || this.images.length < 2) return;
        const dx = event.changedTouches[0].clientX - this.startX;
        this.startX = null;
        if (Math.abs(dx) > 40) {
            event.preventDefault(); // a swipe is not a tap (don't open/close the viewer)
            dx < 0 ? this.next() : this.prev();
        }
    },
}));

window.Alpine = Alpine;

Alpine.start();
