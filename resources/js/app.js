import './bootstrap';
import noUiSlider from 'nouislider';
import 'nouislider/dist/nouislider.css';
import Alpine from 'alpinejs';

document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const menuPanel = document.getElementById('mobile-menu-panel');

    if (menuToggle && menuPanel) {
        menuToggle.addEventListener('click', () => {
            menuPanel.classList.toggle('hidden');
        });
    }

    const searchToggle = document.getElementById('mobile-search-toggle');
    const searchPanel = document.getElementById('mobile-search-panel');

    if (searchToggle && searchPanel) {
        searchToggle.addEventListener('click', () => {
            searchPanel.classList.toggle('hidden');
        });
    }

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
                throw new Error('Network error');
            }

            const data = await response.json();

            // Update counts in header if present
            if (type === 'cart' && typeof data.cart_count !== 'undefined') {
                const val = data.cart_count;

                ['cart-count', 'cart-count-mobile'].forEach((id) => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.textContent = val;
                    }
                });
            }


            if (type === 'wishlist' && typeof data.wishlist_count !== 'undefined') {
                const val = data.wishlist_count;

                ['wishlist-count', 'wishlist-count-mobile'].forEach((id) => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.textContent = val;
                    }
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
            console.error(err);
            showToast('Something went wrong. Please try again.');
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



});



window.Alpine = Alpine;

Alpine.start();
