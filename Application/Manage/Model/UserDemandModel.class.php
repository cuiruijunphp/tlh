<?php
namespace Manage\Model;
use Think\Model;

class UserDemandModel extends CommonModel{

	public function get_demand_list($where = null, $page = 1, $page_size = 10){

		return $this->field('d.id,d.title,d.type_id,d.status,d.add_time,d.earnest_money,d.user_id,d.applicants,d.selected_uid,d.start_time,d.end_time,u.user_name,t.type_name')
			->alias('d')
			->where($where)
			->join('skill_type as t on d.type_id = t.id', 'left')
			->join('users as u on d.user_id = u.id', 'left')
			->limit($page_size)
			->page($page)
			->select();
	}

	/*
	 * 根据id获取结果
	 */
	public function get_demand_by_id($id){

		return $this->field('d.id,d.title,d.type_id,d.status,d.add_time,d.earnest_money,d.user_id,d.applicants,d.selected_uid,d.start_time,d.end_time,u.user_name,t.type_name')
			->alias('d')
			->where(['d.id' => $id])
			->join('skill_type as t on d.type_id = t.id', 'left')
			->join('users as u on d.user_id = u.id', 'left')
			->find();
	}
}