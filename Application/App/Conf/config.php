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
			'price' => '20',//套餐价格
			'time' => 365,//套餐时间
			'note' => '一年',//套餐描述
			'invite_income' => '1',//邀请收益
			'vip_invite_income' => '10',//会员邀请收益
			'proxy_income' => '0',//代理收益,线下结算
		]
	],

	//诚意金列表
	'earnest_money_arr' => [
		'1',
		'2',
		'3',
		'4'
	],

	//每天能发布技能和需求限制
	'max_publish_limit' => [
		'skill' => 5, //每天发布技能的次数限制
		'demand' => 5,//每天发布需求的次数限制
		'vip_free_reserve' => 1,//会员能每天免费预约次数、
	],
);