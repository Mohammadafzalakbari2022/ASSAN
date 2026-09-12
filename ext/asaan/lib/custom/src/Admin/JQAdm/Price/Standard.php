<?php

namespace Aimeos\Admin\JQAdm\Price;

class Standard
	extends \Aimeos\Admin\JQAdm\Base
{
	public function search() : ?string
	{
		return $this->getNoticeHtml();
	}

	public function get() : ?string
	{
		return $this->getNoticeHtml();
	}

	protected function getNoticeHtml() : string
	{
		return '<div class="content"><div class="box"><p style="padding:1rem">'
			. $this->context()->translate( 'admin', 'The standalone price panel is not available in this version. Prices are managed in the Product panel via the "prices" tab.' )
			. '</p></div></div>';
	}
}