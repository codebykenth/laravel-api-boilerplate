<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Repositories\JobApplicationRepository;
use App\Repositories\JobApplicationRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\UserRepositoryInterface;
use App\Services\AuthServiceInterface;
use App\Services\SessionAuthService;
use App\Services\TokenAuthService;
use Carbon\CarbonImmutable;
use Date;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Passkeys;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Fortify::ignoreRoutes();
        Passkeys::ignoreRoutes();

        $this->app->bind(AuthServiceInterface::class, function ($app) {
            $request = $app->make('request');
            if (EnsureFrontendRequestsAreStateful::fromFrontend($request)) {
                return $app->make(SessionAuthService::class);
            }

            return $app->make(TokenAuthService::class);
        });
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(JobApplicationRepositoryInterface::class, JobApplicationRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! app()->isProduction());
        Schema::defaultStringLength(191);
        if (app()->isProduction()) {
            DB::prohibitDestructiveCommands();
            URL::forceHttps();
        }
        Date::use(CarbonImmutable::class);
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            return (new MailMessage)
                ->subject('Reset Your Password - '.config('app.name', 'API Boilerplate'))
                ->view('emails.reset-password', [
                    'url' => $url,
                    'user' => $notifiable,
                ]);
        });

        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Verify Email Address - '.config('app.name', 'API Boilerplate'))
                ->view('emails.verify-email', [
                    'url' => $url,
                    'user' => $notifiable,
                ]);
        });
    }
}
