<?php
namespace Manage\Model;
use Think\Model;

class UserSkillModel extends CommonModel{

	public function get_skill_list($where = null, $page = 1, $page_size = 10, $order = 's.add_time desc'){

		return $this->field('s.id,s.skill_name,s.type_id,s.desc,s.price,s.superiority,s.mode,s.img,s.status,s.good_at,s.user_id,u.user_name,t.type_name,s.add_time,s.update_time')
			->alias('s')
			->where($where)
			->join('skill_type as t on s.type_id = t.id', 'left')
			->join('users as u on s.user_id = u.id', 'left')
			->order($order)
			->limit($page_size)
			->page($page)
			->select();
	}

	/*
	 * 根据id获取结果
	 */
	public function get_skill_by_id($id){

		return $this->field('s.id,s.skill_name,s.type_id,s.desc,s.price,s.superiority,s.mode,s.img,s.status,s.good_at,s.user_id,u.user_name,t.type_name')
			->alias('s')
			->where(['s.id' => $id])
			->join('skill_type as t on s.type_id = t.id', 'left')
			->join('users as u on s.user_id = u.id', 'left')
			->find();
	}
}