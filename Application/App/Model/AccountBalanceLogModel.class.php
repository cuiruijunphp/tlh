<?php
namespace App\Model;
use Think\Model;

class AccountBalanceLogModel extends CommonModel{

	/*
	 * 获取该条件下,消费记录总数
	 */
	public function get_sum_withdraw_price($where){
		return $this->where($where)->sum('balance');
	}

	/*
	 * 获取代理用户的信息
	 */
	public function get_proxy_user_info($where, $page = 1, $page_size = 5){

		// 拼接where查询条件
		$where_keys = array_map(function($v)
		{
			return 'a.' . $v;
		}, array_keys($where));

		$where_tmp = array_combine($where_keys, array_values($where));

		return $this->field('u.id as user_id,u.head_img,u.birthday,a.add_time,u.user_name')
			->alias('a')
			->join('users u on a.item_id = u.id', 'left')
			->where($where_tmp)
			->page($page, $page_size)
			->select();
	}
}