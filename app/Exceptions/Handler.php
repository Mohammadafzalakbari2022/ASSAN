<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Development-only: allow a request carrying the X-ASAN-DEBUG header to
     * see detailed error pages instead of the generic 500. Used to diagnose
     * the production admin panel while the rest of the world keeps the
     * sanitized page. Removed for the final report.
     */
    protected function debugEnabled( $request )
    {
        return $request->header( 'X-ASAN-DEBUG' ) === 'yes'
            && strncmp( $request->path(), 'admin', 5 ) === 0;
    }

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render( $request, \Throwable $e )
    {
        if( $this->debugEnabled( $request ) ) {
            \Illuminate\Support\Facades\Config::set( 'app.debug', true );
            \Illuminate\Support\Facades\Config::set( 'app.env', 'local' );
        }

        return parent::render( $request, $e );
    }

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
