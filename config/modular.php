<?php
/**
 * Файл для хранения пула настроек.
 */

return [
	'path' => base_path() . '/app/Modules',
	'base_namespace' => 'App\Modules',
	'groupWithoutPrefix' => 'Public',

	'groupMiddleware' => [
		'Admin' => [
			'web' => ['auth'],
			'api' => ['auth.api'],
		],
	],

	'modules' => [
		'Admin' => [
			'Users',
		],
		'Public' => [
		],
	],
];