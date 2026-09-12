<?php

namespace Aimeos\Admin\JQAdm\Config;

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
			. $this->context()->translate( 'admin', 'The configuration panel is not available in this version. The shop configuration is managed via the "config/shop.php" file and its overrides.' )
			. '</p></div></div>';
	}
}