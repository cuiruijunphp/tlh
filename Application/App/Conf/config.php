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
			'price' => '0.01',//套餐价格
			'time' => 365,//套餐时间
			'note' => '一年',//套餐描述
			'invite_income' => '0.01',//邀请收益
			'proxy_income' => '0.01',//代理收益
		]
	],

	//诚意金列表
	'earnest_money_arr' => [
		'0.01',
		'0.02',
		'0.03',
		'0.04'
	],

	//每天能发布技能和需求限制
	'max_publish_limit' => [
		'skill' => 50,
		'demand' => 50,
		//会员能每天免费预约3天
		'vip_free_reserve' => 3,
	],
);