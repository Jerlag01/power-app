<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\WelcomeUser;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Password;

/**
 * Opens the link of the welcome mail: the new user lands on the "choose a password" page of the admin panel.
 * Once a password is set the link no longer works ("Forgot password?" always remains available). Opening it
 * again (a second click, or a mail scanner that opens links first) gives the same token while it is valid.
 */
class WelcomeLinkController
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        $panel = Filament::getPanel('admin');

        if (! hash_equals(WelcomeUser::passwordFingerprint($user), (string) $request->query('h'))) {
            return redirect($panel->getLoginUrl())->with('status', __('mail_templates.welcome.link_used'));
        }

        return redirect($panel->getResetPasswordUrl($this->token($user), $user));
    }

    protected function token(User $user): string
    {
        /** @var PasswordBroker $broker */
        $broker = Password::broker(config('auth.defaults.passwords'));
        $key = 'welcome-token:'.$user->id.':'.WelcomeUser::passwordFingerprint($user);
        $cached = rescue(fn (): ?string => ($value = Cache::get($key)) ? Crypt::decryptString($value) : null, null, false);

        if ($cached !== null && $broker->tokenExists($user, $cached)) {
            return $cached;
        }

        $token = $broker->createToken($user);
        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
        Cache::put($key, Crypt::encryptString($token), now()->addMinutes(max(1, $minutes - 1)));

        return $token;
    }
}
