<?php
return [
	'propel' => 
	[
		'general' => 
		[
			'project' => 'PST-Stack',
			'version' => '1.0.0',
		],
		'paths' => 
		[
			'phpDir' => '../app/Models',
			'composerDir' => '../',
		],
		'database' => 
		[
			'connections' => 
			[
				'default' => 
				[
					'adapter' => 'mysql',
					'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=pst-stack',
					'user' => 'root',
					'password' => 'pass',
					'settings' => 
					[
						'charset' => 'utf8',
					],
				],
			],
		],
		'generator' => 
		[
			'schema' => 
			[
				'autoPackage' => true,
			],
			'namespaceAutoPackage' => false,
		],
	],
];