<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/ready', function() {
    return 'OK';
});

Route::get('/manifest.json', function() {
	$config = app( 'config' );
	$context = app( 'aimeos.context' )->get( true );

	try {
		$icon = $context->locale()->getSiteItem()->getIcon() ?: 'asaan.png';
		$baseurl = $context->config()->get( 'resource/fs-media/baseurl', '/aimeos' );
		$iconUrl = $baseurl . '/' . $icon;
	} catch( \Throwable $e ) {
		$iconUrl = 'asaan.png';
	}

	$name = $config->get( 'app.name', 'ASAAN' );
	$lang = $context->locale()->getLanguageId() ?: app()->getLocale();

	return response()->json( [
		'id' => url( '/' ),
		'name' => $name . ' — Afghan Online Shop',
		'short_name' => $name,
		'description' => $name . ' web shop for kitchenware and more',
		'lang' => str_replace( '_', '-', $lang ),
		'dir' => in_array( $lang, ['ar', 'az', 'dv', 'fa', 'he', 'ku', 'ps', 'ur'] ) ? 'rtl' : 'ltr',
		'start_url' => '/',
		'scope' => '/',
		'display' => 'standalone',
		'background_color' => '#ffffff',
		'theme_color' => '#1c5b3a',
		'icons' => [
			['src' => url( $iconUrl ), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
			['src' => url( $iconUrl ), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
			['src' => url( $iconUrl ), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
		],
	] );
})->middleware( 'web' );

$params = [];
$conf = ['prefix' => '', 'where' => []];

if( env( 'SHOP_MULTILOCALE' ) )
{
    $conf['prefix'] .= '{locale}';
    $conf['where']['locale'] = '[a-z]{2}(\_[A-Z]{2})?';
    $params = ['locale' => app()->getLocale()];
}

if( env( 'SHOP_MULTISHOP' ) )
{
    $conf['prefix'] .= '/{site}';
    $conf['where']['site'] = '[A-Za-z0-9\.\-]+';
}

if( $conf['prefix'] )
{
    Route::get('/', function() use ($params) {
        return redirect(airoute('aimeos_home', $params));
    });
}

Route::group($conf ?? [], function() {
    require __DIR__.'/auth.php';
});

if( env( 'SHOP_MULTIROUTE' ) )
{
    Route::group( $conf + ['middleware' => ['web']], function() {
        Route::match( ['GET', 'POST'], '/{path?}', array(
            'as' => 'aimeos_resolve',
            'uses' => 'Aimeos\Shop\Controller\ResolveController@indexAction'
        ) )->where( ['locale' => '[a-z]{2}(\_[A-Z]{2})?', 'site' => '[A-Za-z0-9\.\-]+'], 'path', '.*' );
    });
}