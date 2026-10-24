<?php

use App\Http\Controllers\Admin\OrderPrintController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;


Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('category.show');
Route::get('/brand/{slug}', [BrandController::class, 'show'])->name('brand.show');

Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/search', [ProductController::class, 'search'])->name('search');
Route::get('/shop', [ProductController::class, 'index'])->name('shop.index');


// Cart routes (we’ll fill CartController below)
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{product}', [CartController::class, 'add'])->middleware('throttle:60,1')->name('cart.add');
Route::post('/cart/update/{product}', [CartController::class, 'update'])->middleware('throttle:60,1')->name('cart.update');
Route::post('/cart/remove/{product}', [CartController::class, 'remove'])->middleware('throttle:60,1')->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->middleware('throttle:60,1')->name('cart.clear');

// Order requests (Cash on Delivery, no customer accounts)
Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:20,1')->name('checkout.store');

Route::get('/my-orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('/orders/{order:public_token}', [OrderController::class, 'show'])->where('order', '[A-Za-z0-9]{48}')->name('orders.show');
Route::post('/orders/{order:public_token}/reorder', [OrderController::class, 'reorder'])->middleware('throttle:20,1')->where('order', '[A-Za-z0-9]{48}')->name('orders.reorder');

// Admin-only extras outside the Filament panel
Route::get('/admin/orders/{order:id}/print', OrderPrintController::class)
    ->middleware('auth')
    ->whereNumber('order')
    ->name('admin.orders.print');

// CMS pages managed in the admin (Website → Content pages)
Route::get('/pages/{slug}', [PageController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('pages.show');

Route::get('/contact-us',[ContactController::class, 'index'])->name('contact.show');
Route::post('/contact-us', [ContactController::class, 'submit'])->middleware('throttle:10,1')->name('contact.submit');

Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
Route::post('/wishlist/add/{product}', [WishlistController::class, 'add'])->middleware('throttle:60,1')->name('wishlist.add');
Route::post('/wishlist/remove/{product}', [WishlistController::class, 'remove'])->middleware('throttle:60,1')->name('wishlist.remove');
Route::post('/wishlist/clear', [WishlistController::class, 'clear'])->middleware('throttle:60,1')->name('wishlist.clear');
