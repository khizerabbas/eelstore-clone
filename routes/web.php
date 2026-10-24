<?php

use App\Http\Controllers\Admin\OrderPrintController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;


Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('category.show');

Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/search', [ProductController::class, 'search'])->name('search');
Route::get('/shop', [ProductController::class, 'index'])->name('shop.index');


// Cart routes (we’ll fill CartController below)
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update/{product}', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove/{product}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

// Order requests (Cash on Delivery, no customer accounts)
Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:20,1')->name('checkout.store');

Route::get('/my-orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('/orders/{order:public_token}', [OrderController::class, 'show'])->where('order', '[A-Za-z0-9]{48}')->name('orders.show');
Route::post('/orders/{order:public_token}/reorder', [OrderController::class, 'reorder'])->where('order', '[A-Za-z0-9]{48}')->name('orders.reorder');

// Admin-only extras outside the Filament panel
Route::get('/admin/orders/{order:id}/print', OrderPrintController::class)
    ->middleware('auth')
    ->whereNumber('order')
    ->name('admin.orders.print');

// CMS pages managed in the admin (Website → Content pages)
Route::get('/pages/{slug}', [PageController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('pages.show');

Route::get('/contact-us',[ContactController::class, 'index'])->name('contact.show');
Route::post('/contact-us', [ContactController::class, 'submit'])->name('contact.submit');

Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
Route::post('/wishlist/add/{product}', [WishlistController::class, 'add'])->name('wishlist.add');
Route::post('/wishlist/remove/{product}', [WishlistController::class, 'remove'])->name('wishlist.remove');
Route::post('/wishlist/clear', [WishlistController::class, 'clear'])->name('wishlist.clear');
