<?php
return array(
	'logs'=>array(
		'path'=>'logs/log',
		'type'=>'file'
	),
	'DB'=>array(
		'type'=>'mysql',
        'tablePre'=>'xph_',
		'read'=>array(
			array('host'=>'db.shop.xunlei.com','user'=>'xunlei','passwd'=>'xunlei','name'=>'xiaopihai'),
			//array('host'=>'localhost:3306','user'=>'root','passwd'=>'admin','name'=>'xiaopihai'),
		),

		'write'=>array(
			'host'=>'db.shop.xunlei.com','user'=>'xunlei','passwd'=>'xunlei','name'=>'xiaopihai',
			//'host'=>'localhost:3306','user'=>'root','passwd'=>'admin','name'=>'xiaopihai',
		),
	),
	'langPath' => 'language',
	'viewPath' => 'views',
    'classes' => 'classes.*',
    'rewriteRule' =>'url',
	'theme' => 'default',		//主题
	'skin' => 'default',		//风格
	'timezone'	=> 'Etc/GMT-8',
	'upload' => 'upload',
	'dbbackup' => 'backup/database',
	'safe' => 'cookie',
	'safeLevel' => 'none',
	'lang' => 'zh_sc',
	'debug'=> true,
	'configExt'=> array('site_config'=>'config/site_config.php'),
	'encryptKey'=>'3dc6d57b24120128e849511c13fa36de',
);
?>