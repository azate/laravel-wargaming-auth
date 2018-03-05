# Wargaming authentication for Laravel 5
[![License](https://poser.pugx.org/azate/laravel-wargaming-auth/license)](https://packagist.org/packages/azate/laravel-wargaming-auth)
[![Latest Stable Version](https://poser.pugx.org/azate/laravel-wargaming-auth/v/stable)](https://packagist.org/packages/azate/laravel-wargaming-auth)
[![Total Downloads](https://poser.pugx.org/azate/laravel-wargaming-auth/downloads)](https://packagist.org/packages/azate/laravel-wargaming-auth)

This package is a Laravel 5 service provider which provides support for Wargaming OpenID and is very easy to integrate with any project that requires Wargaming authentication.

## Installation
Require this package with composer.
```shell
composer require azate/laravel-wargaming-auth
```
Laravel >=5.5 uses Package Auto-Discovery, so doesn't require you to manually add the ServiceProvider.

Copy the package config to your local config with the publish command:

```shell
php artisan vendor:publish --provider="Azate\LaravelWargamingAuth\WargamingServiceProvider"
```
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
