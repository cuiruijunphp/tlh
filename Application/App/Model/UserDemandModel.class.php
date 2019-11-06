<?php
namespace App\Model;
use Think\Model;

class UserDemandModel extends CommonModel{
	/*
	 * 获取今天/查询条件下发布的条数
	 */
	public function get_pulish_count($where){
		return $this->where($where)->count();
	}
}