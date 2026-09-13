<?php

namespace App\Providers;

use Illuminate\Validation\Rules\Password;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Password::defaults(function () {
            $rule = Password::min( 8 );
            return $this->app->isProduction() ? $rule->mixedCase()->uncompromised() : $rule;
        });


        // for multi-locale/site setups
        \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(function($notifiable, $token) {
            return url(airoute('password.reset', [
                'email' => $notifiable->getEmailForPasswordReset(),
                'token' => $token,
            ], false));
        });


        // for multi-locale/site setups
        \Illuminate\Auth\Notifications\VerifyEmail::$createUrlCallback = function($notifiable) {
            $time = Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60));
            $params = [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ];

            if( config( 'app.shop_multilocale' ) ) {
                $params['locale'] = Request::route( 'locale', Request::input( 'locale', app()->getLocale() ) );
            }

            if( config( 'app.shop_multishop' ) || config( 'app.shop_registration' ) ) {
                $params['site'] = Request::route( 'site', Request::input( 'site', config( 'shop.mshop.locale.site', 'default' ) ) );
            }

            return URL::temporarySignedRoute('verification.verify', $time, $params);
        };


        // Aimeos admin check for backend
        \Illuminate\Support\Facades\Gate::define('admin', function($user, $class, $roles) {
            if( isset( $user->superuser ) && $user->superuser ) {
                return true;
            }
            return app( '\Aimeos\Shop\Base\Support' )->checkUserGroup( $user, $roles );
        });


        // Aimeos context for icon and logo in all Blade templates
        View::composer('*', function ( $view ) {
            try {
                $view->with( 'aimeossite', app( 'aimeos.context' )->get()->locale()->getSiteItem() );
            } catch( \Exception $e ) {
                $view->with( 'aimeossite', \Aimeos\MShop::create( app( 'aimeos.context' )->get( false ), 'locale/site' )->create() );
            }
        });


        // Send shop e-mails with the SMTP account saved in the admin Settings panel
        // (Settings > Basic > E-mail). Falls back to the env-configured mailer if none
        // is saved in the shop administration.
        Mail::extend( 'asaan', function ( array $config ) {
            return $this->smtpTransport();
        } );

        $siteMail = $this->shopSettings();
        if( !empty( $siteMail['host'] ) ) {
            config( ['mail.default' => 'asaan'] );

            if( !empty( $siteMail['from-email'] ) ) {
                config( ['mail.from' => [
                    'address' => $siteMail['from-email'],
                    'name' => $siteMail['from-name'] ?? config( 'mail.from.name' ),
                ]] );
            }
        }


        // resolve CMS pages sharing same route as categories and products
        \Aimeos\Shop\Controller\ResolveController::register( 'cms', function( $context, $path ) {
            return $this->cms( $context, $path );
        });
    }


    /**
     * Returns the mail settings saved in the admin Settings panel.
     *
     * Looks up the shop's saved configuration. An empty result means no SMTP
     * account was entered in the admin panel yet, so the env-based mailer is used.
     *
     * @return array Mail settings using "admin/email/*" and "client/html/email/*" keys
     */
    protected function shopSettings() : array
    {
        $empty = [
            'host' => '',
            'port' => 0,
            'username' => '',
            'password' => '',
            'encryption' => '',
            'from-email' => '',
            'from-name' => '',
        ];

        try {
            $site = app( 'aimeos.context' )->get()->locale()->getSiteItem();
        } catch( \Throwable $e ) {
            return $empty;
        }

        if( !$site ) {
            return $empty;
        }

        $cfg = (array) $site->getConfig();
        $mail = (array) ( $cfg['admin/email'] ?? [] );
        $from = (array) ( $cfg['client/html/email'] ?? [] );

        return [
            'host' => isset( $mail['host'] ) ? trim( (string) $mail['host'] ) : '',
            'port' => isset( $mail['port'] ) ? (int) $mail['port'] : 0,
            'username' => isset( $mail['username'] ) ? (string) $mail['username'] : '',
            'password' => isset( $mail['password'] ) ? (string) $mail['password'] : '',
            'encryption' => isset( $mail['encryption'] ) ? strtolower( (string) $mail['encryption'] ) : '',
            'from-email' => isset( $from['from-email'] ) ? trim( (string) $from['from-email'] ) : '',
            'from-name' => isset( $from['from-name'] ) ? (string) $from['from-name'] : '',
        ];
    }


    /**
     * Builds the SMTP transport from the admin-saved mail settings.
     *
     * @return \Symfony\Component\Mailer\Transport\TransportInterface SMTP transport
     */
    protected function smtpTransport() : \Symfony\Component\Mailer\Transport\TransportInterface
    {
        $settings = $this->shopSettings();
        $fallback = (array) config( 'mail.mailers.smtp' );
        $encryption = strtolower( (string) ( $settings['encryption'] ?: $fallback['encryption'] ?? '' ) );
        $port = $settings['port'] ?: (int) ( $fallback['port'] ?? 587 );

        if( $encryption === '' && $port === 465 ) {
            $encryption = 'ssl';
        }
        if( !in_array( $encryption, ['ssl', 'tls', ''] ) ) {
            $encryption = '';
        }

        $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
            $settings['host'] ?: (string) ( $fallback['host'] ?? 'localhost' ),
            $port,
            $encryption
        );

        if( $username = $settings['username'] ?: ( $fallback['username'] ?? '' ) ) {
            $transport->setUsername( $username );
        }
        if( $password = $settings['password'] ?: ( $fallback['password'] ?? '' ) ) {
            $transport->setPassword( $password );
        }

        return $transport;
    }
}
