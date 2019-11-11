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
}