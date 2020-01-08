<?php
namespace Manage\Model;
use Think\Model;

class AccountBalanceLogModel extends CommonModel{

	/*
	 * 获取代理用户的信息
	 */
	public function get_proxy_user_info($where, $page = 1, $page_size = 10){

		// 拼接where查询条件
		$where_keys = array_map(function($v)
		{
			return 'a.' . $v;
		}, array_keys($where));

		$where_tmp = array_combine($where_keys, array_values($where));

		return $this->field('u.id,u.head_img,u.birthday,a.add_time,u.user_name,u.sex,u.type,u.mobile_number,u.is_vip,u.vip_expire_time,u.is_vefify,u.add_time as register_time,a.order_id')
			->alias('a')
			->join('users u on a.item_id = u.id', 'left')
			->where($where_tmp)
			->page($page, $page_size)
			->select();
	}
}