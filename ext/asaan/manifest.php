<?php

/**
 * @license MIT, https://opensource.org/licenses/MIT
 * @copyright Aimeos (aimeos.org), 2015-2026
 */

return [
	'name' => 'z-asan',
	'depends' => [
		'aimeos-core',
		'ai-controller-frontend',
		'ai-controller-jobs',
		'ai-client-html',
		'ai-admin-jqadm',
		'ai-laravel',
		'ai-cms-grapesjs',
	],
	'include' => [
		'lib/custom/src',
	],
	'config' => [
		'config',
	],
	'i18n' => [
		'client' => 'i18n/client',
		'client/code' => 'i18n/client/code',
		'admin' => 'i18n/admin',
		'admin/code' => 'i18n/admin/code',
		'controller/frontend' => 'i18n/controller/frontend',
	'controller/jobs' => 'i18n/controller/jobs',
		'controller/jobs/code' => 'i18n/controller/jobs/code',
		'mshop' => 'i18n/mshop',
		'mshop/code' => 'i18n/mshop/code',
		'language' => 'i18n/language',
		'currency' => 'i18n/currency',
		'country' => 'i18n/country',
	],
	'setup' => [
		'setup',
	],
	'template' => [
		'client/html/templates' => [
			'client/html/templates',
		],
		'controller/jobs/templates' => [
			'controller/jobs/templates',
		],
		'admin/jqadm/templates' => [
			'admin/jqadm/templates',
		],
	],
];