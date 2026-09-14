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

Route::group(['prefix' => 'admin/default/jqadm', 'middleware' => ['web']], function () {
    Route::get('/debug/jqadm-info', function () {
        if (request('k') !== 'debug-js8qwb') abort(404);

        $aimeos = app('aimeos')->get();
        $site = 'default';
        $lang = config('app.locale', 'en');

        // backend context (no locale apply, mirrors JqadmController::createAdmin)
        $context = app('aimeos.context')->get(false, 'backend');
        $context->setI18n(app('aimeos.i18n')->get([$lang, 'en']));
        $context->setLocale(app('aimeos.locale')->getBackend($context, $site)->setLanguageId($lang));

        $config = $context->config();

        $siteManager = \Aimeos\MShop::create($context, 'locale/site');
        $siteItem = $siteManager->find($site);
        $config->apply($siteItem->getConfig());

        $paths = $aimeos->getTemplatePaths('admin/jqadm/templates', $context->locale()->getSiteItem()->getTheme());
        $view = app('aimeos.view')->create($context, $paths, $lang);
        $context->setView($view);

        $results = [];
        foreach (['dashboard', 'settings', 'locale', 'locale/language', 'locale/currency', 'locale/site', 'site', 'log', 'group'] as $res) {
            $results[$res] = $view->access($config->get('admin/jqadm/resource/' . $res . '/groups', []));
        }

        $creates = [];
        foreach (['dashboard', 'settings', 'locale/language', 'locale/currency', 'locale/site', 'site', 'log'] as $res) {
            try {
                $class = '\\Aimeos\\Admin\\JQAdm\\' . str_replace('/', '\\', ucwords($res, '/')) . '\\Standard';
                $ok = class_exists($class);
                $client = \Aimeos\Admin\JQAdm::create($context, $aimeos, $res);
                $searchInfo = '';
                $searchHtml = '';
                try {
                    $searchHtml = (string) $client->search();
                    $searchInfo = 'search_ok len=' . strlen($searchHtml);
                } catch (\Throwable $e2) {
                    $searchInfo = 'search_exception: ' . get_class($e2) . ' code=' . $e2->getCode() . ' msg=' . substr($e2->getMessage(), 0, 160);
                }
                $creates[$res] = ['class_exists' => $ok, 'created' => true, 'class' => get_class($client), 'search' => $searchInfo];
            } catch (\Throwable $e) {
                $creates[$res] = ['class_exists' => isset($ok) ? $ok : false, 'created' => false, 'exception' => get_class($e), 'code' => $e->getCode(), 'message' => $e->getMessage()];
            }
        }

        $routePrefix = optional(Route::getCurrentRoute())->getPrefix();
        $routeKey = collect(config('shop.routes'))->where('prefix', $routePrefix)->keys()->first();
        $guardName = data_get(config('shop.guards'), $routeKey, Auth::getDefaultDriver());

        return response()->json([
            'route_prefix' => $routePrefix,
            'route_key' => $routeKey,
            'guard_name' => $guardName,
            'auth_default' => Auth::getDefaultDriver(),
            'user_code' => $context->user() ? $context->user()->getCode() : null,
            'user_id' => $context->user() ? $context->user()->getId() : null,
            'context_groups' => $context->groups(),
            'access_results' => $results,
            'create_results' => $creates,
        ], 200, ['Content-Type' => 'application/json']);
    });
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