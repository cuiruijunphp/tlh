<?php

//define('UPLOAD_URL', '/Uploads/');
//define('UPLOAD_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/Uploads/');

return array(
	//'配置项'=>'配置值'
	'AUTOLOAD_NAMESPACE' => array(
		'Lib' => APP_PATH.'App/Lib',
	),

	//会员套餐列表
	'source_type_arr' => [
		1 => [
			'price' => '0.01',
			'time' => 365,
			'note' => '一年'
		]
	],

	//诚意金列表
	'earnest_money_arr' => [
		'0.01',
		'0.02',
		'0.03',
		'0.04'
	],
);