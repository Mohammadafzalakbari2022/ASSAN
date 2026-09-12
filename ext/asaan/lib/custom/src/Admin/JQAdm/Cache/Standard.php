<?php

namespace Aimeos\Admin\JQAdm\Cache;

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
			. $this->context()->translate( 'admin', 'The cache panel is not available in this version. Use the "aimeos:cache" artisan command to clear the shops caches.' )
			. '</p></div></div>';
	}
}