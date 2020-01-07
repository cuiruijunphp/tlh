<?php

return array(
	//'配置项'=>'配置值'
	//'配置项'=>'配置值'
	//默认动作和控制器的设置
	'DEFAULT_MODULE'        =>  'Manage',  // 默认模块
	'DEFAULT_CONTROLLER'    =>  'User', // 默认控制器名称
	'DEFAULT_ACTION'        =>  'index', // 默认操作名称

	//页面trace功能
	'SHOW_PAGE_TRACE'       =>   false,

	//开启路由模式
	'URL_ROUTER_ON'   => true,

	//开启url模式
	'URL_MODEL'             =>   0,

	//url大小写敏感设置
//	'URL_CASE_INSENSITIVE'  =>  true,   // 默认false 表示URL区分大小写 true则表示不区分大小写

	//会员套餐列表
	'source_type_arr' => [
		//		1 => [
		//			'price' => '200',//套餐价格
		//			'time' => 365,//套餐时间
		//			'note' => '一年',//套餐描述
		//			'invite_income' => '20',//邀请收益5%
		//			'vip_invite_income' => '100',//会员邀请收益50%
		//			'proxy_income' => '0',//代理收益,线下结算
		//		]
		1 => [
			'price' => '0.01',//套餐价格
			'time' => 365,//套餐时间
			'note' => '一年',//套餐描述
			'invite_income' => '20',//邀请收益5%
			'vip_invite_income' => '100',//会员邀请收益50%
			'proxy_income' => '0',//代理收益,线下结算
		]
	],
);