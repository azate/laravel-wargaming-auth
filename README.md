# Wargaming authentication for Laravel 5

This package is a Laravel 5 service provider which provides support for Wargaming OpenID and is very easy to integrate with any project that requires Wargaming authentication.

## Usage example
In `routes/web.php`:
```php
Route::get('auth/wargaming', 'AuthController@redirectToWargaming')->name('auth.wargaming');
Route::get('auth/wargaming/callback', 'AuthController@handleWargamingCallback')->name('auth.wargaming.handle');
```

In `AuthController`:
```php
namespace App\Http\Controllers;

use Azate\LaravelWargamingAuth\WargamingAuth;

class AuthController extends Controller
{
    /**
     * @var WargamingAuth
     */
    protected $wargaming;

    /**
     * AuthController constructor.
     *
     * @param WargamingAuth $wargaming
     */
    public function __construct(WargamingAuth $wargaming)
    {
        $this->wargaming = $wargaming;
    }

    /**
     * Redirect the user to the authentication page
     *
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function redirectToWargaming()
    {
        return $this->wargaming->redirect();
    }

    /**
     * Get user info and log in (hypothetically)
     *
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function handleWargamingCallback()
    {
        if ($this->wargaming->validate()) {
            $user = $this->wargaming->user();

            //

            return redirect('/');
        }

        return $this->redirectToWargaming();
    }
}
```
