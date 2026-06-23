<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureVerificationUrl();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::authenticateUsing(function (Request $request) {
            $input = $request->input(Fortify::username());
            $normalized = User::normalizePhone($input);

            $user = $normalized
                ? User::where('phone', $normalized)->first()
                : User::where('email', strtolower($input))->first();

            if ($user && Hash::check($request->input('password'), $user->password)) {
                return $user;
            }
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));
        Fortify::verifyEmailView(fn () => view('pages::auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('pages::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('pages::auth.confirm-password'));
        Fortify::registerView(fn () => view('pages::auth.register'));
        // Set-password / reset link landing. We validate the single-use token up
        // front so older members who reuse an old link get sensible guidance
        // instead of a confusing form that only errors after submitting.
        Fortify::resetPasswordView(function (Request $request) {
            $token = (string) $request->route('token');
            $email = (string) $request->query('email', '');

            $broker = Password::broker();
            $user = $email !== '' ? $broker->getUser(['email' => $email]) : null;
            $tokenValid = $user !== null && $token !== '' && $broker->tokenExists($user, $token);

            if (! $tokenValid) {
                // Already set a password before? The link was single-use — send them to login.
                if ($user !== null && $user->hasSetPassword()) {
                    return redirect()->route('login')->with(
                        'status',
                        'You have already set your password. Please sign in below — you do not need the email link again.'
                    );
                }

                // Never set a password and the link is expired/used: tell them what to do.
                return response()->view('pages::auth.password-link-expired');
            }

            return view('pages::auth.reset-password');
        });
        Fortify::requestPasswordResetLinkView(fn () => view('pages::auth.forgot-password'));
    }

    /**
     * Customize the email verification URL to use a route that does NOT
     * require the user to be authenticated. This allows verification
     * links to work when clicked on a different device or browser.
     */
    private function configureVerificationUrl(): void
    {
        VerifyEmail::createUrlUsing(function (object $notifiable) {
            return URL::temporarySignedRoute(
                'verification.confirm',
                now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
