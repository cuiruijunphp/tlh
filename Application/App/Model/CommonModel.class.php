<?php
namespace App\Model;
use Think\Model;

class CommonModel extends Model{

	/*
	 * 获取一条记录
	 */
	public function get_one($where){

		return $this->where($where)->limit(1)->find();
	}

	/*
	 * 插入一条记录
	 */
	public function insert_one($data){
		return $this->add($data);
	}

	/*
	 * 更新
	 */
	public function update_data($where, $data){
		return $this->where($where)->save($data);
	}
}