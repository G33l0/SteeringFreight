<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\Auth\NewPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController;
use App\Http\Controllers\Admin\Auth\SessionController;
use App\Http\Controllers\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Admin\ConversationController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentLibraryController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\QuoteRequestController as AdminQuoteRequestController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShipmentController;
use App\Http\Controllers\Admin\ShipmentDocumentController;
use App\Http\Controllers\Admin\ShipmentEventController;
use App\Http\Controllers\Admin\ShipmentStatusController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\QuoteRequestController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Tracking\TrackingChatController;
use App\Http\Controllers\Tracking\TrackingController;
use App\Http\Controllers\Tracking\TrackingDocumentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');

Route::get('/faq', [FaqController::class, 'index'])->name('faq');
Route::get('/client-reviews', [ReviewController::class, 'index'])->name('reviews');

Route::get('/quote', [QuoteRequestController::class, 'create'])->name('quote.create');
Route::post('/quote', [QuoteRequestController::class, 'store'])
    ->middleware('throttle:forms')
    ->name('quote.store');

Route::get('/contact', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:forms')
    ->name('contact.store');

Route::get('/privacy-policy', [LegalPageController::class, 'privacy'])->name('privacy');
Route::get('/terms-of-service', [LegalPageController::class, 'terms'])->name('terms');
Route::get('/pages/{page}', [LegalPageController::class, 'show'])->name('pages.show');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

/*
|--------------------------------------------------------------------------
| Shipment tracking
|--------------------------------------------------------------------------
*/

Route::get('/track', [TrackingController::class, 'index'])->name('track.index');
Route::post('/track', [TrackingController::class, 'lookup'])
    ->middleware('throttle:tracking')
    ->name('track.lookup');
Route::get('/track/{tracking_number}', [TrackingController::class, 'show'])
    ->middleware('throttle:tracking')
    ->name('track.show');

Route::prefix('track/{tracking_number}')->name('track.')->group(function (): void {
    Route::get('documents/{document}', [TrackingDocumentController::class, 'download'])->name('documents.download');

    Route::post('conversations', [TrackingChatController::class, 'store'])
        ->middleware('throttle:chat')
        ->name('chat.store');
    Route::get('conversations/{conversation}', [TrackingChatController::class, 'messages'])
        ->middleware('throttle:chat-poll')
        ->name('chat.messages');
    Route::post('conversations/{conversation}/messages', [TrackingChatController::class, 'reply'])
        ->middleware('throttle:chat')
        ->name('chat.reply');
    Route::get('conversations/{conversation}/messages/{message}/attachment', [TrackingChatController::class, 'attachment'])
        ->name('chat.attachment');
});

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest.staff')->group(function (): void {
        Route::get('login', [SessionController::class, 'create'])->name('login');
        Route::post('login', [SessionController::class, 'store'])
            ->middleware('throttle:admin-login')
            ->name('login.store');

        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
            ->middleware('throttle:forms')
            ->name('password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
        Route::post('reset-password', [NewPasswordController::class, 'store'])
            ->middleware('throttle:forms')
            ->name('password.update');
    });

    Route::middleware(['auth', 'staff'])->group(function (): void {
        Route::post('logout', [SessionController::class, 'destroy'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Shipments.
        Route::get('shipments/archived', [ShipmentController::class, 'archived'])->name('shipments.archived');
        Route::resource('shipments', ShipmentController::class)->except(['destroy']);
        Route::post('shipments/{shipment}/archive', [ShipmentController::class, 'archive'])->name('shipments.archive');
        Route::post('shipments/{shipment}/restore', [ShipmentController::class, 'restore'])->name('shipments.restore');

        // Tracking events.
        Route::post('shipments/{shipment}/events', [ShipmentEventController::class, 'store'])->name('shipments.events.store');
        Route::get('shipments/{shipment}/events/{event}/edit', [ShipmentEventController::class, 'edit'])->name('shipments.events.edit');
        Route::put('shipments/{shipment}/events/{event}', [ShipmentEventController::class, 'update'])->name('shipments.events.update');
        Route::delete('shipments/{shipment}/events/{event}', [ShipmentEventController::class, 'destroy'])->name('shipments.events.destroy');

        // Shipment documents.
        Route::post('shipments/{shipment}/documents', [ShipmentDocumentController::class, 'store'])->name('shipments.documents.store');
        Route::get('shipments/{shipment}/documents/{document}', [ShipmentDocumentController::class, 'download'])->name('shipments.documents.download');
        Route::delete('shipments/{shipment}/documents/{document}', [ShipmentDocumentController::class, 'destroy'])->name('shipments.documents.destroy');

        // Shipment conversation started from the shipment screen.
        Route::post('shipments/{shipment}/conversations', [ConversationController::class, 'storeForShipment'])->name('shipments.conversations.store');

        Route::resource('customers', CustomerController::class);
        Route::resource('statuses', ShipmentStatusController::class)->parameters(['statuses' => 'status']);

        // Customer messages.
        Route::get('messages', [ConversationController::class, 'index'])->name('messages.index');
        Route::get('messages/{conversation}', [ConversationController::class, 'show'])->name('messages.show');
        Route::post('messages/{conversation}/reply', [ConversationController::class, 'reply'])->name('messages.reply');
        Route::post('messages/{conversation}/assign', [ConversationController::class, 'assign'])->name('messages.assign');
        Route::post('messages/{conversation}/claim', [ConversationController::class, 'claim'])->name('messages.claim');
        Route::post('messages/{conversation}/close', [ConversationController::class, 'close'])->name('messages.close');
        Route::post('messages/{conversation}/reopen', [ConversationController::class, 'reopen'])->name('messages.reopen');
        Route::get('messages/{conversation}/attachments/{message}', [ConversationController::class, 'attachment'])->name('messages.attachment');

        Route::get('documents', [DocumentLibraryController::class, 'index'])->name('documents.index');

        Route::resource('reviews', AdminReviewController::class);
        Route::post('reviews/{review}/publish', [AdminReviewController::class, 'togglePublish'])->name('reviews.publish');

        Route::get('quotes', [AdminQuoteRequestController::class, 'index'])->name('quotes.index');
        Route::get('quotes/{quote}', [AdminQuoteRequestController::class, 'show'])->name('quotes.show');
        Route::put('quotes/{quote}', [AdminQuoteRequestController::class, 'update'])->name('quotes.update');
        Route::post('quotes/{quote}/reply', [AdminQuoteRequestController::class, 'reply'])->name('quotes.reply');

        Route::get('contact-messages', [AdminContactMessageController::class, 'index'])->name('contact-messages.index');
        Route::get('contact-messages/{message}', [AdminContactMessageController::class, 'show'])->name('contact-messages.show');
        Route::put('contact-messages/{message}', [AdminContactMessageController::class, 'update'])->name('contact-messages.update');

        Route::resource('services', AdminServiceController::class);
        Route::resource('pages', AdminPageController::class);
        Route::resource('faqs', AdminFaqController::class);

        Route::get('settings/{group?}', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings/{group}', [SettingController::class, 'update'])->name('settings.update');

        Route::resource('users', UserController::class)->except(['show']);

        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    });
});
