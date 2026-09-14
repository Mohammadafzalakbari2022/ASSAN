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

Route::get('/debug/jqadm-info', function() {
    if (request('k') !== 'debug-js8qwb') abort(404);

    $aimeos = app('aimeos')->get();
    $site = 'default';

    // backend context (no locale apply, mirrors JqadmController::createAdmin)
    $context = app('aimeos.context')->get(false, 'backend');
    $lang = config('app.locale', 'en');
    $context->setI18n(app('aimeos.i18n')->get([$lang, 'en']));
    $context->setLocale(app('aimeos.locale')->getBackend($context, $site)->setLanguageId($lang));

    $config = $context->config();

    $beforeGroups = $config->get('admin/jqadm/resource/locale/language/groups', 'NOT SET');
    $beforeCurGroups = $config->get('admin/jqadm/resource/locale/currency/groups', 'NOT SET');

    $siteManager = \Aimeos\MShop::create($context, 'locale/site');
    $siteItem = $siteManager->find($site);
    $siteConfig = $siteItem->getConfig();

    $config->apply($siteConfig);

    $afterLang = $config->get('admin/jqadm/resource/locale/language/groups', 'NOT SET');
    $afterCur = $config->get('admin/jqadm/resource/locale/currency/groups', 'NOT SET');
    $afterLocale = $config->get('admin/jqadm/resource/locale/groups', 'NOT SET');
    $afterSiteLock = $config->get('admin/jqadm/resource/site/groups', 'NOT SET');
    $afterLocSite = $config->get('admin/jqadm/resource/locale/site/groups', 'NOT SET');

    $siteKeys = array_filter(array_keys($siteConfig), function($k) { return stripos($k, 'jqadm') !== false || stripos($k, 'resource') !== false; });

    return response()->json([
        'context_groups' => $context->groups(),
        'before_site_apply' => [
            'locale/language/groups' => $beforeGroups,
            'locale/currency/groups' => $beforeCurGroups,
        ],
        'after_site_apply' => [
            'locale/groups' => $afterLocale,
            'locale/language/groups' => $afterLang,
            'locale/currency/groups' => $afterCur,
            'locale/site/groups' => $afterLocSite,
            'site/groups' => $afterSiteLock,
        ],
        'site_config_jqadm_keys' => $siteKeys,
        'site_config_jqadm_values' => array_intersect_key($siteConfig, array_flip($siteKeys)),
    ], 200, ['Content-Type' => 'application/json']);
});

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