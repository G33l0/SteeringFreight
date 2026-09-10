<?php

namespace App\Providers;

use App\Enums\ContactStatus;
use App\Enums\QuoteStatus;
use App\Models\ChatConversation;
use App\Models\ContactMessage;
use App\Models\QuoteRequest;
use App\Models\Shipment;
use App\Models\ShipmentDocument;
use App\Models\User;
use App\Policies\ChatConversationPolicy;
use App\Policies\ShipmentDocumentPolicy;
use App\Policies\ShipmentPolicy;
use App\Policies\UserPolicy;
use App\Support\Settings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);
    }

    public function boot(): void
    {
        Model::preventLazyLoading($this->app->environment('local'));

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->registerPasswordPolicy();
        $this->registerGates();
        $this->registerRateLimiters();

        // Unread badge in the admin sidebar.
        View::composer('components.layouts.admin', function ($view): void {
            $user = auth()->user();

            $unread = match (true) {
                $user === null => 0,
                $user->hasPermission('chat.manage') => ChatConversation::sum('unread_for_staff'),
                $user->hasPermission('chat.view') => ChatConversation::forRepresentative($user)->sum('unread_for_staff'),
                default => 0,
            };

            $view->with('unreadMessages', $unread ?: null);

            $view->with('newQuotes', $user?->hasPermission('quotes.view')
                ? (QuoteRequest::where('status', QuoteStatus::New->value)->count() ?: null)
                : null);

            $view->with('newContactMessages', $user?->hasPermission('contact.view')
                ? (ContactMessage::where('status', ContactStatus::New->value)->count() ?: null)
                : null);
        });

        ResetPassword::createUrlUsing(fn (object $notifiable, string $token) => route('admin.password.reset', [
            'token' => $token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]));
    }

    /**
     * Every ability used in the admin panel resolves through the role
     * permission map on the user, so adding a permissions table later only
     * means changing User::hasPermission().
     */
    /**
     * How strong a staff password has to be.
     *
     * Laravel's own default is eight characters and nothing else, which accepts
     * "password" for an account that can read every customer record. Length is
     * what actually resists guessing, so this asks for twelve rather than
     * demanding a symbol nobody remembers, and in production also refuses
     * passwords known to have been breached. That check fails open if the
     * service cannot be reached, so a host without outbound access is not
     * locked out of setting a password.
     *
     * The cap is bcrypt's: it ignores anything past 72 bytes, and silently
     * accepting a longer password would be a promise the hash does not keep.
     */
    private function registerPasswordPolicy(): void
    {
        Password::defaults(function (): Password {
            $rule = Password::min(12)->max(72);

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });
    }

    private function registerGates(): void
    {
        Gate::policy(ChatConversation::class, ChatConversationPolicy::class);
        Gate::policy(Shipment::class, ShipmentPolicy::class);
        Gate::policy(ShipmentDocument::class, ShipmentDocumentPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        $abilities = [
            'shipments.view', 'shipments.manage', 'shipments.archive',
            'customers.view', 'customers.manage',
            'statuses.view', 'statuses.manage',
            'documents.view', 'documents.manage',
            'chat.view', 'chat.reply', 'chat.manage', 'chat.assign',
            'quotes.view', 'quotes.manage',
            'contact.view', 'contact.manage',
            'reviews.view', 'reviews.manage',
            'services.view', 'services.manage',
            'pages.view', 'pages.manage',
            'faqs.view', 'faqs.manage',
            'settings.manage', 'users.manage', 'audit.view',
        ];

        foreach ($abilities as $ability) {
            Gate::define($ability, fn (User $user) => $user->hasPermission($ability));
        }
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('tracking', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for('forms', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perHour(12)->by($request->ip()),
        ]);

        RateLimiter::for('chat', fn (Request $request) => [
            Limit::perMinute(10)->by($request->ip()),
            Limit::perHour(60)->by($request->ip()),
        ]);

        RateLimiter::for('chat-poll', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        RateLimiter::for('admin-login', fn (Request $request) => [
            Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);
    }
}
