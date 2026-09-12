<?php

namespace Aimeos\Admin\JQAdm\Common\Decorator;

/**
 * Restricts the ASAAN admin toolbar to English, Dari and Pashto.
 *
 * The Aimeos admin backend assembles its language menu by scanning the
 * translation directories of all installed packages (aimeos-core,
 * ai-admin-jqadm, ai-client-html, ...). Those directories list many more
 * languages (de, es, fr, ...) than ASAAN actually offers, so the language
 * switcher would show languages that are not enabled in the shop.
 *
 * This decorator runs after the Page decorator has built the list and keeps
 * only the three languages ASAAN ships: English (en), Dari (fa) and Pashto (ps).
 */
class Asaan extends Base
{
	/**
	 * Sets the view object and filters the admin language list to the ASAAN languages.
	 *
	 * @param \Aimeos\Base\View\Iface $view The view object which generates the admin output
	 * @return \Aimeos\Admin\JQAdm\Iface Reference to this object for fluent calls
	 */
	public function setView( \Aimeos\Base\View\Iface $view ) : \Aimeos\Admin\JQAdm\Iface
	{
		parent::setView( $view );

		$view->pageI18nList = array_values( array_intersect( (array) $view->pageI18nList, ['en', 'fa', 'ps'] ) );

		return $this;
	}
}