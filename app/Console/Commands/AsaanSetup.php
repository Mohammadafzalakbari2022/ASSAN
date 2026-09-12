<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Aimeos\MShop;

/**
 * ASAAN single-shop bootstrap.
 *
 * Idempotent: can be run again and again without creating duplicates.
 * - Enables the 3 languages: Dari (fa), Pashto (ps), English (en)
 * - Adds the AFN currency
 * - Creates the locale matrix: (fa, ps, en) x (AFN, USD), default fa + AFN
 * - Seeds an AFN price for every product from its USD price (rate: SHOP_AFN_RATE)
 * - Sets the site label to "ASAAN" and default customer date mode to Afghan
 * - Replaces the demo catalog with a kitchen-tools store and translates it
 * - Points all media at the ASAAN.af.png brand image, hero banners at kitchen photos
 */
class AsaanSetup extends Command
{
	protected $signature = 'asaan:setup {site=default : Site code to configure}';
	protected $description = 'ASAAN setup: languages, currency, locales, date mode, kitchen-tool demo catalog and images';

	/** @var array<string, array> Old product label => [new label, fa name, ps name, fa short, ps short, fa long, ps long] */
	protected $products = [
		'Dark grey dress' => [
			'Cookware set (12 pcs)', 'ست قابلمه ۱۲ تکه', 'د پخلي لوښو ۱۲ تکه سیټ',
			'ست کامل ظروف پخت‌وپز', 'بشپړ د پخلي لوښي',
			'ست کامل ظروف پخت‌وپز با کیفیت بالا که برای هر آشپزخانه‌ای مناسب است', 'بشپړ د پخلي لوښي سیټ چې لوړ کیفیت لري او د هر پخلنځي لپاره مناسب دی',
		],
		'Red T-Shirt' => [
			'Non-stick frying pan 26 cm', 'ماهی‌تابه نچسب ۲۶ سانتی', 'ناچسپک ماهی‌تابه ۲۶ سانتي',
			'ماهی‌تابه نچسب سایز متوسط', 'منځنۍ اندازه ناچسپک ماهی‌تابه',
			'ماهی‌تابه نچسب ۲۶ سانتی با روکش ضد چسب که آسان شسته می‌شود', '۲۶ سانتي ناچسپک ماهی‌تابه چې اسانه پاکېږي او روښانه پوښ لري',
		],
		'Black shirt' => [
			'Chef knife 20 cm', 'چاقوی سرآشپز ۲۰ سانتی', 'د شیف چاقو ۲۰ سانتي',
			'چاقوی حرفه‌ای سرآشپز', 'د شیف مسلکي چاقو',
			'چاقوی سرآشپز ۲۰ سانتی با تیغه فولادی و دسته ارگونومیک', 'د شیف ۲۰ سانتي چاقو چې فولادي تیغه او آرام لاستی لري',
		],
		'Black T-Shirt' => [
			'Stainless steel kettle', 'کتری استیل', 'د سټیل چایخور',
			'کتری استیل ۱۸۰۰ میلی‌لیتری', '۱۸۰۰ ملي لېټره سټیل چایخور',
			'کتری استیل با بدنه ضد زنگ و درب متحرک برای هر روز', 'د سټیل چایخور چې زنګ نه وهي او هره ورځ کارول کېږي',
		],
		'Short-sleeved shirt' => [
			'Mixing bowl set (5 pcs)', 'ست کاسه ۵ تکه', 'د کاسو ۵ تکه سیټ',
			'ست کاسه‌های مخلوط‌کن', 'د مخلوط کاسو سیټ',
			'ست کاسه‌های مخلوط‌کن ۵ تکه با درب پلاستیکی برای نگهداری غذا', 'د مخلوط کاسو ۵ تکه سیټ چې پوښ لري او د خوړو د ذخیرې لپاره دی',
		],
		'Sexy top' => [
			'Vegetable slicing set', 'ست رنده سبزیجات', 'د سبزیجاتو رنده سیټ',
			'ست رنده و خردکن سبزیجات', 'د سبزیجاتو رنده او کټوونکی سیټ',
			'ست رنده سبزیجات با چهار تیغه قابل تعویض برای خرد کردن ساده', 'د سبزیجاتو رنده سیټ چې څلور تیغې لري او کټول یې اسانه کوي',
		],
		'Tank-Top in black' => [
			'Cast iron pot 24 cm', 'دیگ چدنی ۲۴ سانتی', 'د اوسپنو دیگ ۲۴ سانتي',
			'دیگ چدنی سنگین', 'دروند د اوسپنو دیگ',
			'دیگ چدنی ۲۴ سانتی که دما را یکنواخت نگه می‌دارد و سال‌ها دوام دارد', 'د ۲۴ سانتي اوسپنیز دیگ چې تودوخه ساتي او کلونه کېږي',
		],
		'Gift voucher' => [
			'Gift voucher', 'کارت هدیه', 'د ډالۍ کارت',
			'کارت هدیه به هر مبلغ دلخواه', 'د ډالۍ کارت په هر مبلغ',
			'کارت هدیه ASAAN برای عزیزان خود؛ مبلغ را خودتان انتخاب کنید', 'د ASAAN د ډالۍ کارت؛ مبلغ یې خپله وټاکئ او ګرانو ته یې ورکړئ',
		],
		'Shirt & cap' => [
			'Knife and cutting board set', 'ست چاقو و تخته برش', 'د چاړې او کترې سیټ',
			'چاقو همراه با تخته برش', 'چاړه له کترې سره',
			'ست چاقو و تخته برش چوبی برای آماده‌سازی سریع غذا', 'د چاړې او لرګینې کترې سیټ چې خواړه ژر چمتو کوي',
		],
		'Shirts for women' => [
			'Kitchen utensils set', 'ست ادوات آشپزخانه', 'د پخلنځي وسایلو سیټ',
			'قاشق، کفگیر و ملاقه چوبی', 'چوپ، کفگیره او لرګین چمچه',
			'ست ادوات چوبی آشپزخانه شامل قاشق، کفگیر و ملاقه که به ظروف آسیب نمی‌زند', 'د پخلنځي لرګین وسایل سیټ چې لوښو ته تاوان نه رسوي',
		],
		'Fashion week' => [
			'New kitchenware event', 'رویداد لوازم آشپزخانه', 'د پخلنځي وسایلو پېښه',
			'رویداد معرفی لوازم جدید آشپزخانه', 'د نویو پخلنځي وسایلو د معرفي پېښه',
			'در رویداد لوازم آشپزخانه جدیدترین محصولات ASAAN را معرفی می‌کنیم', 'په دې پېښه کې موږ د ASAAN نوې وسایل معرفي کوو',
		],
	];

	/** @var array<string, string> Product label => image file served from public/aimeos/ */
protected $productImages = [
		'Cookware set (12 pcs)' => 'hero-2.jpg',
		'Non-stick frying pan 26 cm' => 'products/product-frying-pan.jpg',
		'Chef knife 20 cm' => 'products/product-chef-knife.jpg',
		'Stainless steel kettle' => 'products/product-kettle.jpg',
		'Mixing bowl set (5 pcs)' => 'products/product-mixing-bowls.jpg',
		'Vegetable slicing set' => 'hero-3.jpg',
		'Cast iron pot 24 cm' => 'products/product-cast-iron-pot.jpg',
		'Gift voucher' => 'products/product-gift-voucher.jpg',
		'Knife and cutting board set' => 'products/product-knife-board.jpg',
		'Kitchen utensils set' => 'products/product-utensils.jpg',
		'New kitchenware event' => 'products/product-event.jpg',
	];

	/** @var array<string, array> Old catalog label => [new label, fa label, ps label] */
	protected $categories = [
		'Home' => ['Home', 'خانه', 'کور'],
		'Best sellers' => ['Best sellers', 'پرفروش‌ها', 'ښه پلورونکي'],
		'Women' => ['Cookware', 'ظروف پخت‌وپز', 'د پخلي لوښي'],
		'Shirts' => ['Knives', 'چاقوها', 'چاړې'],
		'Dresses' => ['Bakeware', 'لوازم پخت', 'د پخلي لوښي'],
		'Tops' => ['Utensils', 'ادوات آشپزخانه', 'د پخلنځي وسایل'],
		'Men' => ['Cooking sets', 'ست پخلی', 'د پخلي سیټ'],
		'T-Shirts' => ['Storage', 'ظروف نگهداری', 'ذخیره'],
		'Muscle shirts' => ['Appliances', 'لوازم برقی', 'برقي وسایل'],
		'Misc' => ['Misc', 'متفرقه', 'متفرقه'],
		'Events' => ['Events', 'رویدادها', 'پېښې'],
		'Vouchers' => ['Vouchers', 'کارت‌های هدیه', 'د ډالۍ کارتونه'],
		'New arrivals' => ['New arrivals', 'جدیدترین‌ها', 'نوي راغلي'],
		'Hot deals' => ['Hot deals', 'پیشنهادهای ویژه', 'ځانګړي وړاندیزونه'],
	];

	/** @var array<string, array> Old attribute label => [new label, fa label, ps label] */
protected $attributes = [
		'Demo: Light' => ['Beige', 'بژ', 'بیژ'],
		'Demo: Dark' => ['Black', 'مشکی', 'تور'],
		'Demo: Blue' => ['Blue', 'آبی', 'شين'],
		'Demo: Small print' => ['Small size', 'سایز کوچک', 'کوچنی اندازه'],
		'Demo: Large print' => ['Large size', 'سایز بزرگ', 'لویه اندازه'],
		'Demo: Small sticker' => ['Small set', 'مجموعه کوچک', 'کوچنی سیټ'],
		'Demo: Large sticker' => ['Large set', 'مجموعه بزرگ', 'لوی سیټ'],
		'Demo: Text for print' => ['Free engraving', 'حکاکی رایگان', 'وړیا نقاشي'],
		'Demo: Custom date' => ['Gift wrapping', 'بسته‌بندی هدیه', 'د ډالۍ بسته‌بندي'],
		'Demo: One month' => ['1 month warranty', 'گارانتی یک ماه', 'یوه میاشت تضمین'],
		'Demo: One year' => ['1 year warranty', 'گارانتی یک سال', 'یو کال تضمین'],
		'Demo: Length 34' => ['Capacity 3 l', 'ظرفیت ۳ لیتر', '۳ لیتره ظرفیت'],
		'Demo: Length 36' => ['Capacity 5 l', 'ظرفیت ۵ لیتر', '۵ لیتره ظرفیت'],
		'Demo: Width 32' => ['Incl. lid', 'با درپوش', 'له سرپوښ سره'],
		'Demo: Width 33' => ['Wooden handle', 'دسته چوبی', 'لرګين لاستی'],
	];

	public function handle() : int
	{
		$site = $this->argument( 'site' );

		\Aimeos\MShop::cache( false );
		\Aimeos\MAdmin::cache( false );

		$context = $this->getLaravel()->make( 'aimeos.context' )->get( false, 'command' );
		$context->setEditor( 'asaan:setup' );

		$localeManager = MShop::create( $context, 'locale' );
		$localeItem = $localeManager->bootstrap( $site, '', '', false );
		$localeItem->setLanguageId( null );
		$localeItem->setCurrencyId( null );

		$scontext = clone $context;
		$scontext->setLocale( $localeItem );

		$this->enableLanguages( $scontext );
		$this->addCurrencies( $scontext );
		$this->updateSite( $scontext, $site );
		$this->createLocales( $scontext, $site );
		$this->removeExtras( $scontext );
		$this->seedPrices( $scontext );
		$this->translateCatalog( $scontext );
		$this->seedBrandMedia( $scontext );
		$this->seedProductImages( $scontext );
		$this->seedBrandAssets();

		\Aimeos\MShop::cache( true );
		\Aimeos\MAdmin::cache( true );

		$this->info( 'ASAAN setup finished. Storefront now offers Dari (default), Pashto and English, currencies AFN (default) and USD. Catalog is a kitchen-tools store with Afghanistan as the sole shipping country.' );

		return self::SUCCESS;
	}


	/**
	 * Enables the Dari (fa) and Pashto (ps) languages.
	 */
	protected function enableLanguages( $context ) : void
	{
		$manager = MShop::create( $context, 'locale/language' );

		foreach( ['fa', 'ps'] as $code )
		{
			try {
				$item = $manager->find( $code );
			} catch( \Aimeos\MShop\Exception $e ) {
				$item = $manager->create()->setCode( $code );
			}

			if( $item->getStatus() < 1 )
			{
				$item->setStatus( 1 );
				$manager->save( $item );
				$this->info( sprintf( 'Language "%1$s" enabled', $code ) );
			}
		}
	}


	/**
	 * Adds the AFN currency.
	 */
	protected function addCurrencies( $context ) : void
	{
		$manager = MShop::create( $context, 'locale/currency' );

		try {
			$item = $manager->find( 'AFN' );
		} catch( \Aimeos\MShop\Exception $e ) {
			$item = $manager->create()->setCode( 'AFN' )->setLabel( 'Afghan afghani' )->setStatus( 1 );
			$manager->save( $item );
			$this->info( 'Currency "AFN" added' );
			return;
		}

		if( $item->getStatus() < 1 )
		{
			$item->setStatus( 1 );
			$manager->save( $item );
			$this->info( 'Currency "AFN" enabled' );
		}
	}


	/**
	 * Sets the site label to ASAAN and the default customer date mode.
	 */
	protected function updateSite( $context, string $site ) : void
	{
		$manager = MShop::create( $context, 'locale/site' );
		$filter = $manager->filter()->add( 'locale.site.code', '==', $site );
		$item = $manager->search( $filter )->first();

		if( $item === null )
		{
			$this->error( sprintf( 'Site "%1$s" not found, skipping site configuration', $site ) );
			return;
		}

		$changed = false;

		if( in_array( $item->getLabel(), ['', 'Default'], true ) )
		{
			$item->setLabel( 'ASAAN' );
			$changed = true;
		}

		$config = $item->getConfig();

		if( !isset( $config['date']['customer-mode'] ) )
		{
			$config['date']['customer-mode'] = 'afghan';
			$changed = true;
		}

		if( $changed )
		{
			$item->setConfig( $config );
			$manager->save( $item );
			$this->info( sprintf( 'Site "%1$s" label set to "ASAAN", date mode default set to Afghan', $site ) );
		}
		else
		{
			$this->info( sprintf( 'Site "%1$s" already configured (label "%2$s")', $site, $item->getLabel() ) );
		}
	}


	/**
	 * Creates the locale rows for (fa, ps, en) x (AFN, USD) and removes the rest.
	 */
	protected function createLocales( $context, string $site ) : void
	{
		$manager = MShop::create( $context, 'locale' );
		$filter = $manager->filter()->add( 'locale.site.code', '==', $site );
		$existing = $manager->search( $filter );

		$rows = [];
		foreach( $existing as $item )
		{
			$rows[$item->getLanguageId() . '/' . $item->getCurrencyId()] = $item;
		}

// position 0 = default locale (fa/AFN), everything else takes the next free position
		$position = 0;
		$targets = [['fa', 'AFN'], ['fa', 'USD'], ['ps', 'AFN'], ['ps', 'USD'], ['en', 'AFN'], ['en', 'USD']];

		foreach( $existing as $row )
		{
			if( !in_array( [$row->getLanguageId(), $row->getCurrencyId()], $targets ) )
			{
				$key = $row->getLanguageId() . '/' . $row->getCurrencyId();
				$manager->delete( $row->getId() );
				$this->info( sprintf( 'Locale row deleted: %1$s', $key ) );
				unset( $rows[$key] );
			}
		}

		foreach( $targets as $pair )
		{
			$key = $pair[0] . '/' . $pair[1];

			if( isset( $rows[$key] ) )
			{
				if( $key === 'fa/AFN' ) {
					$rows[$key]->setPosition( 0 )->setStatus( 1 );
				} elseif( $rows[$key]->getPosition() == 0 ) {
					$rows[$key]->setPosition( ++$position );
				}
				$manager->save( $rows[$key] );
				$this->info( sprintf( 'Locale row ok: %1$s', $key ) );
			}
			else
			{
				$item = $manager->create();
				$item->setLanguageId( $pair[0] )->setCurrencyId( $pair[1] )->setStatus( 1 )
					->setPosition( $key === 'fa/AFN' ? 0 : ++$position );
				$manager->save( $item );
				$this->info( sprintf( 'Locale row created: %1$s (%2$s)', $key, $key === 'fa/AFN' ? 'default' : $position ) );
			}
		}
	}


	/**
	 * Removes all languages except English, Dari and Pashto and all
	 * currencies except AFN and USD from the shop.
	 *
	 * The locale rows for the removed languages/currencies are deleted by
	 * createLocales() first, so the language/currency catalog entries can be
	 * removed without breaking the default (fa/AFN) or any other locale row.
	 */
	protected function removeExtras( $context ) : void
	{
		$removed = ['language' => [], 'currency' => []];
		$trim = function( string $managerName, array $keep, string $key ) use ( $context, &$removed ) {

			$manager = MShop::create( $context, $managerName );

			// Aimeos filters default to a 100-item slice, so page through the
			// catalog in batches until nothing non-target is left, otherwise the
			// languages/currencies after the first page stay behind forever.
			do
			{
				$filter = $manager->filter()->slice( 0, 500 );
				$found = false;

				foreach( $manager->search( $filter ) as $item )
				{
					if( !in_array( $item->getCode(), $keep ) )
					{
						$manager->delete( $item->getId() );
						$removed[$key][] = $item->getCode();
						$found = true;
					}
				}
			} while( $found );
		};

		$trim( 'locale/language', ['en', 'fa', 'ps'], 'locale/language' );
		$trim( 'locale/currency', ['AFN', 'USD'], 'locale/currency' );

		if( !empty( $removed['language'] ) ) {
			$this->info( sprintf( 'Languages removed (kept en/fa/ps): %1$s', implode( ', ', $removed['language'] ) ) );
		}
		if( !empty( $removed['currency'] ) ) {
			$this->info( sprintf( 'Currencies removed (kept AFN/USD): %1$s', implode( ', ', $removed['currency'] ) ) );
		}
		if( empty( $removed['language'] ) && empty( $removed['currency'] ) ) {
			$this->info( 'Languages/currencies already restricted to en/fa/ps and AFN/USD, nothing to do' );
		}
	}


	/**
	 * Seeds an AFN price row for every product from its USD price.
	 *
	 * The storefront has no currency converter, so each currency needs its own
	 * price. Products only have USD prices, which breaks prices and add-to-basket
	 * in the default AFN locale. This creates whole-afghani prices at the
	 * configured rate (SHOP_AFN_RATE), idempotent: products that already have an
	 * AFN price are left untouched so admin edits survive re-runs.
	 */
	protected function seedPrices( $context ) : void
	{
		$rate = (float) ( env( 'SHOP_AFN_RATE', 63 ) ?: 63 );
		$manager = MShop::create( $context, 'product' );
		// New price items use this precision; keeping AFN prices as whole
		// afghanis while the existing USD prices keep their two decimals
		$context->config()->set( 'mshop/price/precision', 0 );
		$priceManager = MShop::create( $context, 'price' );

		$filter = $manager->filter()->add( 'product.status', '>=', 0 );
		$seeded = 0;

		foreach( $manager->search( $filter, ['price'] ) as $item )
		{
			$usdPrices = [];
			$hasAfn = false;

			foreach( $item->getListItems( 'price', null, null, false ) as $listItem )
			{
				$ref = $listItem->getRefItem();

				if( $ref === null ) {
					continue;
				}

				if( $ref->getCurrencyId() === 'AFN' ) {
					$hasAfn = true;
				} elseif( $ref->getCurrencyId() === 'USD' && $ref->getValue() > 0 ) {
					$usdPrices[] = $listItem;
				}
			}

			if( $hasAfn || empty( $usdPrices ) ) {
				continue;
			}

			$added = false;

			foreach( $usdPrices as $listItem )
			{
				$usd = $listItem->getRefItem();

				$afn = $priceManager->create()
					->setCurrencyId( 'AFN' )
					->setStatus( $usd->getStatus() )
					->setValue( round( $usd->getValue() * $rate ) )
					->setRebate( round( $usd->getRebate() * $rate ) )
					->setCosts( round( $usd->getCosts() * $rate ) )
					->setTaxRate( $usd->getTaxRate() )
					->setTaxFlag( $usd->getTaxFlag() )
					->setQuantity( $usd->getQuantity() );

				$newList = $manager->createListItem()
					->setType( $listItem->getType() )
					->setPosition( $listItem->getPosition() );

				$item->addListItem( 'price', $newList, $afn );
				$added = true;
				$seeded++;
			}

			if( $added ) {
				$manager->save( $item );
			}
		}

		if( $seeded > 0 ) {
			$this->info( sprintf( 'AFN prices seeded: %1$d price items at %2$s AFN per USD', $seeded, rtrim( $rate, '.0' ) ) );
		} else {
			$this->info( 'AFN prices already seeded, nothing to do' );
		}
	}


	/**
	 * Translates the demo catalog products, categories and attributes.
	 */
	protected function translateCatalog( $context ) : void
	{
		$this->translateProducts( $context );
		$this->translateCategories( $context );
		$this->assignCategoryMembers( $context );
		$this->translateAttributes( $context );
	}


	/**
	 * Renames and translates the demo products into kitchen tools.
	 */
protected function translateProducts( $context ) : void
	{
		$manager = MShop::create( $context, 'product' );

		foreach( $this->products as $oldLabel => $data )
		{
			$filter = $manager->filter()->add( 'product.label', '==', [$oldLabel, $data[0]] )->add( 'product.status', '>=', 0 );
			$item = $manager->search( $filter, ['text'] )->first();

			if( $item === null )
			{
				$this->warn( sprintf( 'Product "%1$s" not found, skipping', $oldLabel ) );
				continue;
			}

			if( $item->getLabel() !== $data[0] )
			{
				$item->setLabel( $data[0] );
			}

			$this->translateItemTexts( $context, $manager, $item, $data[0], array_slice( $data, 1 ), false );
		}

		// leftovers: duplicate demo rows that shared a clothing label (e.g. the selection article)
		$leftovers = [
			'Black shirt' => [
				'Kitchen bundle', 'ست آشپزخانه', 'د پخلنځي سیټ',
				'ست کامل لوازم آشپزخانه', 'بشپړ د پخلنځي وسایل',
				'ست کامل لوازم آشپزخانه با گزینه‌های قابل انتخاب', 'بشپړ د پخلنځي وسایل سیټ چې انتخابي گزینه لري',
			],
		];

		foreach( array_keys( $leftovers ) as $oldLabel )
		{
			$filter = $manager->filter()->add( 'product.label', '==', $oldLabel )->add( 'product.status', '>=', 0 );
			$items = $manager->search( $filter, ['text'] );

			foreach( $items as $item )
			{
				$data = $leftovers[$oldLabel];
				$item->setLabel( $data[0] );
				$this->translateItemTexts( $context, $manager, $item, $data[0], array_slice( $data, 1 ), false );
			}
		}
	}


	/**
	 * Renames and translates the demo catalog nodes into a kitchen-tools tree.
	 */
	protected function translateCategories( $context ) : void
	{
		$manager = MShop::create( $context, 'catalog' );

		$rows = DB::table( 'mshop_catalog' )->where( 'status', '>=', 0 )->get( ['id', 'label'] );

		foreach( $rows as $row )
		{
			$oldLabel = $row->label;

			if( !isset( $this->categories[$oldLabel] ) ) {
				continue;
			}

			[ $newLabel, $faLabel, $psLabel ] = $this->categories[$oldLabel];
			$item = $manager->get( $row->id, ['text'] );

			if( $item->getLabel() !== $newLabel )
			{
				$item->setLabel( $newLabel );
			}

			$this->translateItemTexts( $context, $manager, $item, $newLabel, [$faLabel, $psLabel, $faLabel, $psLabel, $faLabel, $psLabel], true );
		}
	}


	/**
	 * Links the kitchen products into their renamed subcategories so every
	 * storefront category page shows matching items instead of an empty list.
	 */
	protected function assignCategoryMembers( $context ) : void
	{
		$map = [
			'Cookware set (12 pcs)' => ['Cookware'],
			'Non-stick frying pan 26 cm' => ['Cookware'],
			'Chef knife 20 cm' => ['Knives'],
			'Stainless steel kettle' => ['Appliances'],
			'Mixing bowl set (5 pcs)' => ['Utensils'],
			'Vegetable slicing set' => ['Utensils'],
			'Cast iron pot 24 cm' => ['Bakeware', 'Cookware'],
			'Gift voucher' => ['Vouchers'],
			'Knife and cutting board set' => ['Knives'],
			'Kitchen utensils set' => ['Utensils', 'Storage'],
			'Kitchen bundle' => ['Cooking sets'],
			'New kitchenware event' => ['Events'],
			'Discount' => ['Misc'],
		];

		$cmanager = MShop::create( $context, 'catalog' );
		$catIds = [];

		foreach( $cmanager->search( $cmanager->filter()->add( 'catalog.status', '>=', 0 ) ) as $item )
		{
			$catIds[$item->getLabel()] = $item->getId();
		}

		$manager = MShop::create( $context, 'product' );
		$added = 0;
		$touched = [];

		foreach( $map as $label => $cats )
		{
			$filter = $manager->filter()->add( 'product.label', '==', $label )->add( 'product.status', '>=', 0 );
			$items = $manager->search( $filter, ['catalog'] );

			foreach( $items as $item )
			{
				$pos = 0;
				$existing = [];

				foreach( $item->getListItems( 'catalog', 'default', null, false ) as $listItem )
				{
					$existing[] = $listItem->getRefId();
					$pos = max( $pos, $listItem->getPosition() );
				}

				$itemAdded = false;

				foreach( $cats as $cat )
				{
					$catId = $catIds[$cat] ?? null;

					if( $catId === null || in_array( $catId, $existing ) ) {
						continue;
					}

					$listItem = $manager->createListItem()->setType( 'default' )->setRefId( $catId )->setPosition( ++$pos );
					$item->addListItem( 'catalog', $listItem );
					$existing[] = $catId;
					$added++;
					$itemAdded = true;
				}

				if( $itemAdded )
				{
					$manager->save( $item );
					$touched[$item->getId()] = $item;
				}
			}
		}

		if( $added > 0 ) {
			$this->info( sprintf( 'Products linked to kitchen categories: %1$d new product-category links', $added ) );
		}

		// The storefront lists products through the product index, so the
		// category memberships must be reindexed to become visible. Cheap and
		// idempotent, therefore run on every pass to heal stale indexes too.
		$context->config()->set( 'mshop/index/manager/domains', ['text', 'price', 'media', 'attribute', 'supplier', 'catalog'] );
		MShop::create( $context, 'index' )->rebuild();
		$this->info( sprintf( 'Product index rebuilt (%1$d products in pass)', $added ? count( $touched ) : 0 ) );
	}


	/**
	 * Renames and translates the demo attributes into kitchen-related options.
	 */
	protected function translateAttributes( $context ) : void
	{
		$manager = MShop::create( $context, 'attribute' );

		foreach( $this->attributes as $oldLabel => $translations )
		{
$filter = $manager->filter()->add( 'attribute.label', '==', [$oldLabel, $translations[0]] );
			$item = $manager->search( $filter, ['text'] )->first();

			if( $item === null )
			{
				$this->warn( sprintf( 'Attribute "%1$s" not found, skipping', $oldLabel ) );
				continue;
			}

if( $item->getLabel() !== $translations[0] )
			{
				$item->setLabel( $translations[0] );
			}

			$this->translateItemTexts( $context, $manager, $item, $translations[0],
				[$translations[1], $translations[2], $translations[1], $translations[2], $translations[1], $translations[2]], false );

			$manager->save( $item );
			$this->info( sprintf( 'Attribute "%1$s" renamed and translated', $translations[0] ) );
		}
	}


	/**
	 * Replaces all media with the ASAAN brand image; stage banners get the kitchen photos.
	 */
	protected function seedBrandMedia( $context ) : void
	{
		$imgDir = public_path( 'aimeos' );
		if( !is_dir( $imgDir ) ) {
			@mkdir( $imgDir, 0777, true );
		}

		$src = base_path( 'ASAAN.af.png' );

		if( !is_file( $src ) || !@copy( $src, $imgDir . '/asaan.png' ) )
		{
			$this->warn( 'ASAAN.af.png not found, media items keep their current images' );
			return;
		}

		$heroes = [];

		foreach( ['hero-1.jpg', 'hero-2.jpg', 'hero-3.jpg'] as $file )
		{
			if( is_file( base_path( 'ext/asaan/media/' . $file ) ) )
			{
				@copy( base_path( 'ext/asaan/media/' . $file ), $imgDir . '/' . $file );
				$heroes[] = '/aimeos/' . $file;
			}
		}

		$brand = '/aimeos/asaan.png';
		$manager = MShop::create( $context, 'media' );
		$items = $manager->search( $manager->filter()->slice( 0, 1000 ) );
		$heroIdx = 0;

		foreach( $items as $item )
		{
			if( $item->getType() === 'stage' && !empty( $heroes ) )
			{
				$url = $heroes[$heroIdx++ % count( $heroes )];
				$item->setMimeType( 'image/jpeg' )
					->setUrl( $url )
					->setPreviews( [480 => $url, 960 => $url, 1920 => $url] );
			}
			else
			{
				$item->setMimeType( 'image/png' )
					->setUrl( $brand )
					->setPreviews( array_fill_keys( [240, 480, 720, 960, 1350, 1920], $brand ) );
			}

			$manager->save( $item );
		}

		$this->info( sprintf( 'Media seeded: %1$d items (stage = kitchen hero photos, rest = %2$s)', count( $items ), basename( $brand ) ) );
	}


	/**
	 * Points each demo product's main image to its own local photo.
	 *
	 * The photos live in ext/asaan/media/products (attribution in
	 * ATTRIBUTION.txt) and are copied to public/aimeos/products so the admin
	 * image previews work under the restrictive img-src content policy. Two
	 * products reuse the in-repo kitchen heroes. Idempotent: it only rewrites
	 * the URL of each product's existing first media item.
	 */
	protected function seedProductImages( $context ) : void
	{
		$srcDir = base_path( 'ext/asaan/media/products' );
		$imgDir = public_path( 'aimeos/products' );

		if( !is_dir( $imgDir ) ) {
			@mkdir( $imgDir, 0777, true );
		}

		$copied = 0;

		if( is_dir( $srcDir ) )
		{
			foreach( glob( $srcDir . '/*.jpg' ) as $src )
			{
				if( @copy( $src, $imgDir . '/' . basename( $src ) ) ) {
					$copied++;
				}
			}
		}

		$manager = MShop::create( $context, 'product' );
		$mediaManager = MShop::create( $context, 'media' );
		$set = 0;

		foreach( $manager->search( $manager->filter()->add( 'product.status', '>=', 0 ), ['media'] ) as $item )
		{
			$target = $this->productImages[$item->getLabel()] ?? null;
			$listItem = $target ? $item->getListItems( 'media', null, null, false )->first() : null;
			$media = $listItem ? $listItem->getRefItem() : null;

			if( $media === null ) {
				continue;
			}

			$url = '/aimeos/' . $target;
			$media->setMimeType( 'image/jpeg' )
				->setUrl( $url )
				->setPreviews( array_fill_keys( [240, 480, 720, 960, 1350, 1920], $url ) );
			$mediaManager->save( $media );
			$set++;
		}

		$this->info( sprintf( 'Product images assigned: %1$d products, %2$d photos copied', $set, $copied ) );
	}


	/**
	 * Overwrites the stock theme logo/favicon with the ASAAN brand assets.
	 *
	 * The Docker build publishes the original Aimeos theme into
	 * public/vendor/shop/themes/default/ on every deploy, so the brand files
	 * are copied back here (idempotent, runs on deploy and locally).
	 */
	protected function seedBrandAssets() : void
	{
		$srcDir = base_path( 'ext/asaan/media/brand' );
		$themeDir = public_path( 'vendor/shop/themes/default/assets' );

		if( !is_dir( $srcDir ) )
		{
			$this->warn( 'ext/asaan/media/brand not found, keeping stock theme logo' );
			return;
		}

		foreach( ['logo.png', 'icon.png', 'icon-192.png', 'icon-512.png', 'apple-touch-icon.png'] as $file )
		{
			if( is_file( $srcDir . '/' . $file ) && is_dir( $themeDir ) ) {
				@copy( $srcDir . '/' . $file, $themeDir . '/' . $file );
			}
		}

		if( is_file( $srcDir . '/favicon.ico' ) ) {
			@copy( $srcDir . '/favicon.ico', public_path( 'favicon.ico' ) );
		}

		$this->info( 'Brand assets seeded: theme logo, favicon, apple-touch-icon and PWA icons replaced with ASAAN.af.png' );
	}


	/**
	 * Adds or updates Dari/Pashto texts of the given item type.
	 *
	 * @param mixed $context Aimeos context
	 * @param mixed $manager Domain manager
	 * @param mixed $item Domain item
	 * @param string $label English label used for console output and the name text
	 * @param array $translations [fa name, ps name, fa short, ps short, fa long, ps long]
	 * @param bool $catalog True if the item is a catalog node
	 */
protected function translateItemTexts( $context, $manager, $item, string $label, array $translations, bool $catalog = false ) : void
	{
		$textManager = MShop::create( $context, 'text' );
		$this->removeDuplicateTexts( $item );

		foreach( ['fa', 'ps'] as $lang )
		{
			foreach( ['name', 'short', 'long'] as $idx => $type )
			{
				$content = $translations[$idx * 2 + ( $lang === 'fa' ? 0 : 1 )] ?? null;

				if( $content === null || $content === '' ) {
					continue;
				}

				$found = false;

				foreach( $item->getListItems( 'text' ) as $listItem )
				{
					if( ( $refItem = $listItem->getRefItem() ) !== null
						&& $refItem->getLanguageId() === $lang && $refItem->getType() === $type )
					{
						$refItem->setContent( $content )->setLabel( 'ASAAN ' . $type . '/' . $lang );
						$found = true;
						break;
					}
				}

				if( !$found )
				{
					$listItem = $manager->createListItem();
					$refItem = $textManager->create()
						->setLanguageId( $lang )->setType( $type )->setStatus( 1 )
						->setContent( $content )->setLabel( 'ASAAN ' . $type . '/' . $lang );
					$item->addListItem( 'text', $listItem, $refItem );
				}
			}
		}

		$manager->save( $item );
		$count = $catalog ? 'Category' : 'Product';
		$this->info( sprintf( '%1$s "%2$s" renamed/translated to Dari and Pashto', $count, $label ) );
	}


	/**
	 * Removes duplicate text list items pointing at the same language and type.
	 */
	protected function removeDuplicateTexts( $item ) : void
	{
		$seen = [];

		foreach( $item->getListItems( 'text' ) as $listItem )
		{
			$refItem = $listItem->getRefItem();

			if( $refItem === null || !in_array( $refItem->getType(), ['name', 'short', 'long'] ) ) {
				continue;
			}

			$key = $refItem->getLanguageId() . '/' . $refItem->getType();

			if( isset( $seen[$key] ) )
			{
				$item->deleteListItem( 'text', $listItem );
			}
			else
			{
				$seen[$key] = true;
			}
		}
	}
}
