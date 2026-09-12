<?php

namespace Aimeos\Admin\JQAdm\Settings;

class Asaan
	extends \Aimeos\Admin\JQAdm\Settings\Standard
{
	/**
	 * Creates new and updates existing items using the data array
	 *
	 * The stock implementation always writes a "resource/email/from-name"
	 * config key, which the locale site manager rejects ("Site configuration
	 * key 'resource' is not allowed"), so saving the settings panel failed
	 * with a 500 for every shop. This override drops the "resource" subtree
	 * (site-level e-mail config) while keeping label, theme, code, logo and
	 * icon handling unchanged.
	 *
	 * @param array $data Data array
	 * @return \Aimeos\MShop\Locale\Item\Site\Iface New settings item object
	 */
	protected function fromArray( array $data ) : \Aimeos\MShop\Locale\Item\Site\Iface
	{
		$item = $this->context()->locale()->getSiteItem();
		$config = (array) ( $data['locale.site.config'] ?? [] );
		unset( $config['resource'] );

		$files = (array) $this->view()->request()->getUploadedFiles();

		$item = $this->fromArrayIcon( $item, $files );
		$item = $this->fromArrayLogo( $item, $files );

		return $item->setConfig( array_replace_recursive( $item->getConfig(), $config ) )
			->setTheme( (string) $data['locale.site.theme'] )
			->setLabel( (string) $data['locale.site.label'] )
			->setCode( (string) $data['locale.site.code'] );
	}
}