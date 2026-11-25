<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CategoryDashboardController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ListingInteractionController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchSuggestController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

// ------------------------------------------------------------------ public
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/ads', [ListingController::class, 'index'])->name('listings.index');
Route::get('/search/suggest', SearchSuggestController::class)->middleware('throttle:60,1')->name('search.suggest');
// The ad page's slug must not swallow /ad/{id}/edit, /whatsapp, /promote, /pay-fee
Route::get('/ad/{listing}/{slug?}', [ListingController::class, 'show'])->whereNumber('listing')
    ->where('slug', '(?!(edit|whatsapp|promote|pay-fee)$)[A-Za-z0-9\-]+')->name('listings.show');

Route::post('/ad/{listing}/phone', [ListingInteractionController::class, 'revealPhone'])->middleware('throttle:30,1')->name('listings.phone');
Route::get('/ad/{listing}/whatsapp', [ListingInteractionController::class, 'whatsapp'])->middleware('throttle:30,1')->name('listings.whatsapp');
Route::post('/ad/{listing}/report', [ListingInteractionController::class, 'report'])->middleware('throttle:5,10')->name('listings.report');

Route::get('/store/{store}', [StoreController::class, 'show'])->name('stores.show');
Route::get('/seller/{user}', [SellerController::class, 'show'])->name('sellers.show');

Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');
Route::get('/advertise', [BannerController::class, 'create'])->name('advertise');
Route::post('/advertise', [BannerController::class, 'store'])->middleware('throttle:5,10')->name('advertise.store');
Route::get('/b/{banner}', [BannerController::class, 'click'])->name('banners.click');

Route::get('/safety', [PageController::class, 'safety'])->name('pages.safety');
Route::get('/about', [PageController::class, 'about'])->name('pages.about');
Route::get('/terms', [PageController::class, 'terms'])->name('pages.terms');
Route::get('/privacy', [PageController::class, 'privacy'])->name('pages.privacy');

// One-time installer for hosts without SSH (only works while INSTALL_KEY is set)
Route::get('/install/{key}', InstallController::class)->middleware('throttle:5,1')->name('install');

// PayHere server-to-server callback (CSRF exempt, see bootstrap/app.php)
Route::post('/payhere/notify', [CheckoutController::class, 'notify'])->name('payhere.notify');

// ------------------------------------------------------------------ guests
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendReset'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');
});

// ------------------------------------------------------------------ members
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/post', [ListingController::class, 'create'])->name('listings.create');
    Route::post('/post', [ListingController::class, 'store'])->middleware('throttle:10,1')->name('listings.store');
    Route::get('/ad/{listing}/edit', [ListingController::class, 'edit'])->name('listings.edit');
    Route::put('/ad/{listing}', [ListingController::class, 'update'])->name('listings.update');
    Route::delete('/ad/{listing}', [ListingController::class, 'destroy'])->name('listings.destroy');
    Route::post('/ad/{listing}/sold', [ListingController::class, 'markSold'])->name('listings.sold');
    Route::post('/ad/{listing}/renew', [ListingController::class, 'renew'])->name('listings.renew');
    Route::post('/ad/{listing}/favorite', [ListingInteractionController::class, 'favorite'])->name('listings.favorite');

    Route::get('/ad/{listing}/promote', [PromotionController::class, 'show'])->name('promote.show');
    Route::post('/ad/{listing}/promote', [PromotionController::class, 'store'])->name('promote.store');
    Route::get('/ad/{listing}/pay-fee', [PromotionController::class, 'payFee'])->name('listings.payFee');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/ads', [DashboardController::class, 'ads'])->name('dashboard.ads');
    Route::get('/dashboard/saved', [DashboardController::class, 'saved'])->name('dashboard.saved');
    Route::get('/dashboard/leads', [DashboardController::class, 'leads'])->name('dashboard.leads');
    Route::get('/dashboard/billing', [DashboardController::class, 'billing'])->name('dashboard.billing');
    Route::get('/dashboard/profile', [DashboardController::class, 'profile'])->name('dashboard.profile');
    Route::put('/dashboard/profile', [DashboardController::class, 'updateProfile'])->name('dashboard.profile.update');
    Route::get('/dashboard/categories', [CategoryDashboardController::class, 'index'])->name('dashboard.categories');
    Route::get('/dashboard/categories/{category}', [CategoryDashboardController::class, 'show'])->name('dashboard.categories.show');
    Route::get('/dashboard/store', [StoreController::class, 'edit'])->name('dashboard.store');
    Route::post('/dashboard/store', [StoreController::class, 'save'])->name('dashboard.store.save');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/ad/{listing}/chat', [MessageController::class, 'start'])->middleware('throttle:10,1')->name('messages.start');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}', [MessageController::class, 'reply'])->name('messages.reply');
    Route::patch('/messages/{conversation}', [MessageController::class, 'update'])->name('messages.update');

    Route::post('/seller/{user}/review', [ReviewController::class, 'store'])->middleware('throttle:5,10')->name('reviews.store');

    Route::post('/subscribe/{package}', [PricingController::class, 'subscribe'])->name('pricing.subscribe');
    Route::get('/checkout/{order}', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/{order}/bank', [CheckoutController::class, 'bank'])->name('checkout.bank');
    Route::get('/checkout/{order}/return', [CheckoutController::class, 'return'])->name('checkout.return');
});

// ------------------------------------------------------------------ admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/listings', [Admin\ListingController::class, 'index'])->name('listings');
    Route::post('/listings/{listing}/approve', [Admin\ListingController::class, 'approve'])->name('listings.approve');
    Route::post('/listings/{listing}/reject', [Admin\ListingController::class, 'reject'])->name('listings.reject');
    Route::post('/listings/{listing}/promote', [Admin\ListingController::class, 'promote'])->name('listings.promote');
    Route::delete('/listings/{listing}', [Admin\ListingController::class, 'destroy'])->name('listings.destroy');

    Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports');
    Route::post('/reports/{report}', [Admin\ReportController::class, 'update'])->name('reports.update');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('users');
    Route::post('/users/{user}/verify', [Admin\UserController::class, 'verify'])->name('users.verify');
    Route::post('/users/{user}/ban', [Admin\UserController::class, 'ban'])->name('users.ban');
    Route::post('/users/{user}/role', [Admin\UserController::class, 'role'])->name('users.role');

    Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders');
    Route::post('/orders/{order}/approve', [Admin\OrderController::class, 'approve'])->name('orders.approve');
    Route::post('/orders/{order}/reject', [Admin\OrderController::class, 'reject'])->name('orders.reject');

    Route::get('/banners', [Admin\BannerController::class, 'index'])->name('banners');
    Route::post('/banners', [Admin\BannerController::class, 'store'])->name('banners.store');
    Route::post('/banners/{banner}', [Admin\BannerController::class, 'update'])->name('banners.update');
    Route::delete('/banners/{banner}', [Admin\BannerController::class, 'destroy'])->name('banners.destroy');

    Route::get('/packages', [Admin\SettingsController::class, 'packages'])->name('packages');
    Route::put('/packages/{package}', [Admin\SettingsController::class, 'updatePackage'])->name('packages.update');
    // One back office per ad type (Vehicles, Property, Jobs ...)
    Route::get('/categories', [Admin\CategoryController::class, 'index'])->name('categories');
    Route::get('/categories/{category}', [Admin\CategoryController::class, 'show'])->name('categories.show');
    Route::get('/categories/{category}/ads', [Admin\CategoryController::class, 'ads'])->name('categories.ads');
    Route::get('/categories/{category}/settings', [Admin\CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [Admin\CategoryController::class, 'update'])->name('categories.update');
});
