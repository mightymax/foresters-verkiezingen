<?php

return [
	'url' => 'http://deforesters.test/verkiezingen',
	'email' => [
		'SMTPDebug'  => 0,
		'SMTPAuth'   => TRUE,
		'SMTPSecure' => "tls",
		'Port'       => 587,
		'Username'   => "your-gmail-address@gmail.com",
		'Password'   => "YOUR-VERY-SECRET-PASSWORD",
		'Host'       => "smtp.gmail.com",
		'Mailer'     => "smtp",
		'From' => [
			'email' => 'your-gmail-address@gmail.com',
			'name'  => 'Your fancy website name'
		],
		'ReplyTo' => [
			'email' => 'your-gmail-address@gmail.com',
			'name'  => 'Your fancy website name'
		]
	],
	'admin' => [
		'ip_addresses' => [
			'127.0.0.1'
		]
	]
];
