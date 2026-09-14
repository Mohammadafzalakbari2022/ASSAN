<?php

return [
	'site' => [
		'groups' => 'no-access',
	],
	'locale' => [
		'groups' => ['admin', 'super'],
		'site' => [
			'groups' => 'no-access',
		],
		'language' => [
			'groups' => ['admin', 'super'],
		],
		'currency' => [
			'groups' => ['admin', 'super'],
		],
	],
];