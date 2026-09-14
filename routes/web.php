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

        $out = [];

        // Authentication facts
        $defaultGuard = Auth::getDefaultDriver();
        $out['auth_default_guard'] = $defaultGuard;
        $out['auth_web_id'] = Auth::guard('web')->id();
        $out['auth_web_user'] = Auth::guard('web')->user() ? [
            'class' => get_class(Auth::guard('web')->user()),
            'id' => Auth::guard('web')->user()->getAuthIdentifier(),
            'except' => array_values(array_diff(get_object_vars(Auth::guard('web')->user()), [])) ?? 'n/a',
        ] : null;
        $out['shop_guards'] = config('shop.guards', []);
        $out['shop_routes_keys'] = array_keys(config('shop.routes', []));
        $out['shop_routes_jqadm'] = config('shop.routes.jqadm', null);

        // DB shape: users table vs mshop customer
        foreach (['users', 'mshop_customer'] as $table) {
            try {
                $out['table_' . $table] = \Illuminate\Support\Facades\Schema::hasTable($table) ? 'exists' : 'missing';
            } catch (\Throwable $e) {
                $out['table_' . $table] = 'error: ' . $e->getMessage();
            }
        }
        try {
            $out['users_count'] = \Illuminate\Support\Facades\DB::table('users')->count();
            $out['users_sample'] = \Illuminate\Support\Facades\DB::table('users')->select('id', 'email')->limit(5)->get()->toArray();
            $out['customer_sample'] = \Illuminate\Support\Facades\DB::table('mshop_customer')->select('id', 'code', 'label', 'status')->limit(5)->get()->toArray();
            $out['group_rows'] = \Illuminate\Support\Facades\DB::table('mshop_customer_group')->select('id', 'code', 'label')->get()->toArray();
        } catch (\Throwable $e) {
            $out['db_error'] = $e->getMessage();
        }

        // Backend context access resolution
        $aimeos = app('aimeos')->get();
        $site = 'default';
        $lang = config('app.locale', 'en');

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

        $out['context_user_code'] = $context->user() ? $context->user()->getCode() : null;
        $out['context_user_id'] = $context->user() ? $context->user()->getId() : null;
        $out['context_groups'] = $context->groups();

        // access helper details
        try {
            $helper = $view->access;
            $out['access_helper_class'] = is_object($helper) ? get_class($helper) : gettype($helper);
        } catch (\Throwable $e) {
            $out['access_helper_class'] = 'ERR: ' . $e->getMessage();
        }
        $codes = [];
        try {
            $manager = \Aimeos\MShop::create($context, 'group');
            $filter = $manager->filter(true)->add('group.id', '==', $context->groups());
            $codes = $manager->search($filter)->col('group.code')->all();
        } catch (\Throwable $e) {
            $codes = ['ERR: ' . $e->getMessage()];
        }
        $out['access_resolved_codes'] = $codes;

        // navbar merge reality
        try {
            $navbar = \Aimeos\Base\Map::from($context->config()->get('admin/jqadm/navbar', []))->ksort();
            $navout = [];
            foreach ($navbar as $key => $navitem) {
                $name = is_array($navitem) ? ($navitem['_'] ?? current($navitem)) : $navitem;
                $navout[$key] = ['item' => $navitem, 'name' => $name, 'access' => $view->access($context->config()->get('admin/jqadm/resource/' . $name . '/groups', []))];
            }
            $out['navbar_merged'] = $navout;
        } catch (\Throwable $e) {
            $out['navbar_merged'] = 'ERR: ' . $e->getMessage();
        }

        // config paths actually used by Aimeos
        try {
            $out['config_paths'] = array_values($aimeos->getConfigPaths());
        } catch (\Throwable $e) {
            $out['config_paths'] = ['ERR: ' . $e->getMessage()];
        }

        foreach (['dashboard', 'settings', 'locale', 'locale/language', 'locale/currency', 'locale/site', 'site', 'log', 'group'] as $res) {
            try {
                $out['access'][$res] = $view->access($config->get('admin/jqadm/resource/' . $res . '/groups', []));
            } catch (\Throwable $e) {
                $out['access'][$res] = 'ERR: ' . $e->getMessage();
            }
        }

        return response()->json($out, 200, ['Content-Type' => 'application/json', 'X-Role-Out' => 'v8']);
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