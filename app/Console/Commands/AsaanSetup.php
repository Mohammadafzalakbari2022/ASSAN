<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Aimeos\MShop;

/**
 * ASAAN single-shop bootstrap.
 *
 * Idempotent: can be run again and again without creating duplicates.
 * - Enables the 3 languages: Dari (fa), Pashto (ps), English (en)
 * - Adds the AFN currency and the Afghanistan country entries
 * - Creates the locale matrix: (fa, ps, en) x (AFN, USD), default fa + AFN
 * - Sets the site label to "ASAAN" and default customer date mode to Afghan
 * - Translates the demo catalog into Dari and Pashto
 */
class AsaanSetup extends Command
{
	protected $signature = 'asaan:setup {site=default : Site code to configure}';
	protected $description = 'ASAAN single-shop setup: languages, currencies, country, date mode and demo catalog translations';

	/** @var array<string, array> English label => [fa name, ps name, fa short, ps short, fa long, ps long] */
	protected $products = [
		'Dark grey dress' => [
			'لباس خاکستری تیره', 'توره خړ گاون',
			'لباس کشیده در رنگ خاکستری تیره', 'په توره خړ رنګ کې کشدار گاون',
			'لباس کشیده در رنگ خاکستری تیره که اندام شما را زیباتر نشان می‌دهد', 'توره خړ کشدار گاون چې ستاسو بڼه ښکلې ښیي',
		],
		'Red T-Shirt' => [
			'تی‌شرت سرخ', 'سور ټي-شرټ',
			'تی‌شرت نخی در رنگ سرخ', 'په سور رنګ کې نری ټي-شرټ',
			'تی‌شرت نخی با رنگ سرخ که با هر نوع لباس هماهنگ است', 'سور نری ټي-شرټ چې له هر ډول جامو سره ښه ځلیږي',
		],
		'Black shirt' => [
			'پیراهن مشکی', 'توره کمیس',
			'پیراهن نخی در رنگ مشکی', 'په تور رنګ کې نری کمیس',
			'پیراهن نخی مشکی با دوام و با کیفیت بالا', 'دوامداره توره نری کمیس چې ډېر ښه کیفیت لري',
		],
		'Black T-Shirt' => [
			'تی‌شرت مشکی', 'تور ټي-شرټ',
			'تی‌شرت نخی در رنگ مشکی', 'په تور رنګ کې نری ټي-شرټ',
			'تی‌شرت نخی مشکی مناسب برای استفاده روزانه', 'تور نری ټي-شرټ چې د ورځني استعمال لپاره مناسب دی',
		],
		'Short-sleeved shirt' => [
			'پیراهن آستین کوتاه', 'لنډ لستوڼی کمیس',
			'پیراهن با آستین کوتاه و سبک', 'سپک کمیس چې لنډ لستوڼي لري',
			'پیراهن سبک با آستین کوتاه که برای فصل گرم مناسب است', 'سپک لنډ لستوڼی کمیس چې د ګرمې موسم لپاره مناسب دی',
		],
		'Sexy top' => [
			'تاپ جذاب', 'ښکلی تذرو',
			'تاپ جذاب با طراحی مدرن', 'په عصري ډیزاین سره ښکلی تذرو',
				'تاپ جذاب با طراحی مدرن که ظاهر شما را برجسته می‌کند', 'عصري ښکلی تذرو چې ستاسو ظاهر ډېر ښکلی ښیي',
		],
		'Tank-Top in black' => [
			'تاپ بدون آستین مشکی', 'تور بې لستوڼی تذرو',
			'تاپ بدون آستین در رنگ مشکی', 'په تور رنګ کې بې لستوڼي تذرو',
			'تاپ بدون آستین مشکی با جنس نرم و راحت', 'نرم او آرام تور تذرو چې لستوڼي نلري',
		],
		'Gift voucher' => [
			'کارت هدیه', 'د ډالۍ کارت',
			'کارت هدیه به هر مبلغ دلخواه', 'د ډالۍ کارت چې په هر مبلغ ترلاسه کولی شئ',
			'کارت هدیه ASAAN برای عزیزان خود؛ مبلغ را خودتان انتخاب کنید', 'د ASAAN د ډالۍ کارت؛ مبلغ یې خپله وټاکئ او ګران کسانو ته یې ورکړئ',
		],
		'Shirt & cap' => [
			'پیراهن و کلاه', 'کمیس او کپ',
			'مجموعه پیراهن و کلاه', 'کمیس او کپ مجموعې',
			'مجموعه کامل پیراهن با کلاه هم‌رنگ', 'یوه بشپړه مجموعه، کمیس د هم‌رنګه کپ سره',
		],
		'Shirts for women' => [
			'پیراهن‌های زنانه', 'د ښځو کمیسونه',
			'مجموعه پیراهن‌های زنانه', 'د ښځو د کمیسونو مجموعه',
			'مجموعه پیراهن‌های زیبای زنانه با مدل‌های متنوع', 'د ښځو ښکلي کمیسونه په بېلابېلو سټایلونو کې',
		],
		'Fashion week' => [
			'هفته مد', 'د فیشن اونۍ',
			'رویداد هفته مد', 'د فیشن اونۍ پېښه',
			'در رویداد هفته مد جدیدترین لباس‌ها را معرفی می‌کنیم', 'په د فیشن اونۍ کې موږ نوې جامې معرفي کوو',
		],
	];

	/** @var array<string, array> English category label => [fa label, ps label] */
	protected $categories = [
		'Home' => ['خانه', 'کور'],
		'best-sellers' => ['پرفروش‌ها', 'ښه پلورونکي'],
		'women' => ['زنانه', 'ښځینه'],
		'men' => ['مردانه', 'نارینه'],
		'shirts' => ['پیراهن‌ها', 'کمیسونه'],
		'dresses' => ['لباس‌ها', 'گاونونه'],
		'tops' => ['تاپ‌ها', 'تذروونه'],
		't-shirts' => ['تی‌شرت‌ها', 'ټي-شرټونه'],
		'muscle-shirts' => ['پیراهن‌های عضله‌نما', 'د عضلې کمیسونه'],
		'misc' => ['متفرقه', 'متفرقه'],
		'events' => ['رویدادها', 'پېښې'],
		'vouchers' => ['کارت‌های هدیه', 'د ډالۍ کارتونه'],
		'new-arrivals' => ['جدیدترین‌ها', 'نوي راغلي'],
		'hot-deals' => ['پیشنهادهای ویژه', 'ځانګړي وړاندیزونه'],
	];

	/** @var array<string, array> English attribute label => [fa label, ps label] */
	protected $attributes = [
		'Demo: Light' => ['روشن', 'روڼ'],
		'Demo: Dark' => ['تیره', 'توره'],
		'Demo: Blue' => ['آبی', 'شين'],
		'Demo: Small print' => ['چاپ کوچک', 'کوچنی چاپ'],
		'Demo: Large print' => ['چاپ بزرگ', 'لوی چاپ'],
		'Demo: Small sticker' => ['استیکر کوچک', 'کوچنی سټیکر'],
		'Demo: Large sticker' => ['استیکر بزرگ', 'لوی سټیکر'],
		'Demo: Text for print' => ['متن برای چاپ', 'د چاپ لپاره متن'],
		'Demo: Custom date' => ['تاریخ سفارشی', 'ځانګړې نېټه'],
		'Demo: One month' => ['یک ماه', 'یوه میاشت'],
		'Demo: One year' => ['یک سال', 'یو کال'],
		'Demo: Length 34' => ['طول ۳۴', 'اوږدوالی ۳۴'],
		'Demo: Length 36' => ['طول ۳۶', 'اوږدوالی ۳۶'],
		'Demo: Width 32' => ['عرض ۳۲', 'پراخوالی ۳۲'],
		'Demo: Width 33' => ['عرض ۳۳', 'پراخوالی ۳۳'],
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
		$this->addCountries( $scontext );
		$this->updateSite( $scontext, $site );
		$this->createLocales( $scontext, $site );
		$this->translateCatalog( $scontext );

		\Aimeos\MShop::cache( true );
		\Aimeos\MAdmin::cache( true );

		$this->info( 'ASAAN setup finished. Storefront locale selector now offers Dari (default), Pashto and English; currencies AFN (default) and USD. Site label set to "ASAAN".' );

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
				$item = $manager->getItem( $code, false );
			} catch( \Aimeos\MShop\Exception $e ) {
				$item = $manager->create()->setCode( $code );
			}

			if( $item->getStatus() < 1 )
			{
				$item->setStatus( 1 );
				$manager->saveItem( $item );
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
			$item = $manager->getItem( 'AFN', false );
		} catch( \Aimeos\MShop\Exception $e ) {
			$item = $manager->create()->setCode( 'AFN' )->setLabel( 'Afghan afghani' )->setStatus( 1 );
			$manager->saveItem( $item );
			$this->info( 'Currency "AFN" added' );
			return;
		}

		if( $item->getStatus() < 1 )
		{
			$item->setStatus( 1 );
			$manager->saveItem( $item );
			$this->info( 'Currency "AFN" enabled' );
		}
	}


	/**
	 * Adds the Afghanistan country entry.
	 */
	protected function addCountries( $context ) : void
	{
		$manager = MShop::create( $context, 'locale/country' );

		try {
			$item = $manager->getItem( 'AF', false );
		} catch( \Aimeos\MShop\Exception $e ) {
			$item = $manager->create()->setCode( 'AF' )->setLabel( 'Afghanistan' )->setStatus( 1 );
			$manager->saveItem( $item );
			$this->info( 'Country "AF" (Afghanistan) added' );
			return;
		}

		if( $item->getStatus() < 1 )
		{
			$item->setStatus( 1 );
			$manager->saveItem( $item );
			$this->info( 'Country "AF" (Afghanistan) enabled' );
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
			$manager->saveItem( $item );
			$this->info( sprintf( 'Site "%1$s" label set to "ASAAN", date mode default set to Afghan', $site ) );
		}
		else
		{
			$this->info( sprintf( 'Site "%1$s" already configured (label "%2$s")', $site, $item->getLabel() ) );
		}
	}


	/**
	 * Creates the locale rows for (fa, ps, en) x (AFN, USD).
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

		foreach( $targets as $pair )
		{
			$key = $pair[0] . '/' . $pair[1];

			if( isset( $rows[$key] ) )
			{
				if( $key === 'fa/AFN' ) {
					$rows[$key]->setPosition( 0 )->setStatus( 1 )->setCountryId( 'AF' );
				} elseif( $rows[$key]->getPosition() == 0 ) {
					$rows[$key]->setPosition( ++$position );
				}
				$manager->saveItem( $rows[$key] );
				$this->info( sprintf( 'Locale row ok: %1$s', $key ) );
			}
			else
			{
				$item = $manager->create();
				$item->setLanguageId( $pair[0] )->setCurrencyId( $pair[1] )->setCountryId( 'AF' )->setStatus( 1 )
					->setPosition( $key === 'fa/AFN' ? 0 : ++$position );
				$manager->saveItem( $item );
				$this->info( sprintf( 'Locale row created: %1$s (%2$s)', $key, $key === 'fa/AFN' ? 'default' : $position ) );
			}
		}
	}


	/**
	 * Translates the demo catalog products, categories and attributes.
	 */
	protected function translateCatalog( $context ) : void
	{
		$this->translateProducts( $context );
		$this->translateCategories( $context );
		$this->translateAttributes( $context );
	}


	/**
	 * Adds or updates Dari/Pashto texts for the demo products.
	 */
	protected function translateProducts( $context ) : void
	{
		$manager = MShop::create( $context, 'product' );

		foreach( $this->products as $label => $translations )
		{
			$filter = $manager->filter()->add( 'product.label', '==', $label )->add( 'product.status', '>=', 0 );
			$item = $manager->search( $filter )->first();

			if( $item === null )
			{
				$this->warn( sprintf( 'Product "%1$s" not found, skipping', $label ) );
				continue;
			}

			$this->translateItemTexts( $context, $manager, $item, $label, $translations );
		}
	}


	/**
	 * Adds or updates Dari/Pashto texts for the demo categories.
	 */
	protected function translateCategories( $context ) : void
	{
		$manager = MShop::create( $context, 'catalog' );

		foreach( $this->categories as $label => $translations )
		{
			$filter = $manager->filter()->add( 'catalog.label', '==', $label );
			$item = $manager->search( $filter )->first();

			if( $item === null )
			{
				$this->warn( sprintf( 'Category "%1$s" not found, skipping', $label ) );
				continue;
			}

			$this->translateItemTexts( $context, $manager, $item, $label, $translations, true );
		}
	}


	/**
	 * Adds or updates Dari/Pashto labels for the demo attributes.
	 */
	protected function translateAttributes( $context ) : void
	{
		$manager = MShop::create( $context, 'attribute' );

		foreach( $this->attributes as $label => $translations )
		{
			$filter = $manager->filter()->add( 'attribute.label', '==', $label );
			$item = $manager->search( $filter )->first();

			if( $item === null )
			{
				$this->warn( sprintf( 'Attribute "%1$s" not found, skipping', $label ) );
				continue;
			}

			foreach( ['fa', 'ps'] as $lang )
			{
				$existing = $item->getListItems( 'text', null, null, false );
				$found = false;

				foreach( $existing as $listItem )
				{
					if( ( $refItem = $listItem->getRefItem() ) !== null
						&& $refItem->getLanguageId() === $lang && $refItem->getType() === 'name' )
					{
						$refItem->setContent( $translations[$lang === 'fa' ? 0 : 1] )->setLabel( 'ASAAN translation/' . $lang );
						$found = true;
						break;
					}
				}

				if( !$found )
				{
					$listItem = $manager->createListItem();
					$refItem = MShop::create( $context, 'text' )->create()
						->setLanguageId( $lang )->setType( 'name' )->setStatus( 1 )
						->setContent( $translations[$lang === 'fa' ? 0 : 1] )
						->setLabel( 'ASAAN translation/' . $lang );
					$item->addListItem( 'text', $listItem, $refItem );
				}
			}

			$manager->saveItem( $item );
			$this->info( sprintf( 'Attribute "%1$s" translated', $label ) );
		}
	}


	/**
	 * Adds or updates Dari/Pashto texts of the given item type.
	 *
	 * @param mixed $context Aimeos context
	 * @param mixed $manager Domain manager
	 * @param mixed $item Domain item
	 * @param string $label English label used for console output
	 * @param array $translations [fa name, ps name, fa short, ps short, fa long, ps long]
	 * @param bool $catalog True if the item is a catalog node
	 */
	protected function translateItemTexts( $context, $manager, $item, string $label, array $translations, bool $catalog = false ) : void
	{
		$textManager = MShop::create( $context, 'text' );
		$existing = $item->getListItems( 'text', null, null, false );

		foreach( ['fa', 'ps'] as $lang )
		{
			foreach( ['name', 'short', 'long'] as $idx => $type )
			{
				$content = $translations[$idx * 2 + ( $lang === 'fa' ? 0 : 1 )] ?? null;

				if( $content === null || $content === '' ) {
					continue;
				}

				$found = false;

				foreach( $existing as $listItem )
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

		$manager->saveItem( $item );
		$count = $catalog ? 'Category' : 'Product';
		$this->info( sprintf( '%1$s "%2$s" translated to Dari and Pashto', $count, $label ) );
	}
}