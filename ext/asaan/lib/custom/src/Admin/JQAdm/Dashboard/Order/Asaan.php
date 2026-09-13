<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2016-2026
 */

namespace Aimeos\Admin\JQAdm\Dashboard\Order;

class Asaan
	extends \Aimeos\Admin\JQAdm\Dashboard\Order\Standard
{
	/**
	 * Returns the list of sub-client names configured for the client
	 *
	 * The "countcountry" widget (orders by country) is omitted because the
	 * ASSAN shop only sells in Afghanistan and the widget is meaningless
	 * there. It can't be removed via the "subparts" configuration because
	 * the config overlay merges with array_replace_recursive() which never
	 * deletes existing keys.
	 *
	 * @return array List of JQAdm client names
	 */
	protected function getSubClientNames() : array
	{
		return ['quick', 'latest', 'salesday', 'salesmonth', 'salesweekday', 'countday', 'countpaystatus', 'counthour', 'servicepayment', 'servicedelivery'];
	}
}