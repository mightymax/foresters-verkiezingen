<?php

return [
	'url' => 'http://deforesters.test/verkiezingen',
	'dbPath' => __DIR__ . '/verkiezingen.sqlite3',
	'Gmail' => [
		'From' => [
			'verkiezingen@deforesters.nl' => 'Foresters Verkiezingen'
		],
		'ReplyTo' => [
			'noreply@deforesters.nl' => 'Foresters Verkiezingen'
		],
		/* See README.md for mail instructions */
		'AuthConfig' => [
			"client_id" => "<your-client-id>.apps.googleusercontent.com",
			"project_id" => "<your-project-id>",
			"client_secret" => "<your-client-secret>",
			"redirect_uris" => [
				"urn:ietf:wg:oauth:2.0:oob",
				"http://localhost"
			]
		]
	],
	'admin' => [
		/* Which IP Adresses are allowed to rhe Admin API */
		'ip_addresses' => [
			'127.0.0.1'
		]
	]
];
