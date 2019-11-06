<?php
namespace App\Model;
use Think\Model;

class SkillReserveModel extends CommonModel{

	/*
	 * 取某个技能下预约成功,预约中和预约被拒绝的预约信息
	 */
	public function get_skill_reserve_by_skill_id($skill_id, $page = 1, $page_size = 3){
		$where = [
			'r.skill_id' => $skill_id,
			'r.status' => ['in', '2,3,4'],
		];

		if(!$page && !$page_size){
			$limit = null;
		}else{
			$limit = ($page - 1) * $page_size . ',' . $page_size;
		}

		return $this->field('r.*,u.user_name,u.head_img,u.is_vefify,u.birthday,s.province,s.city,s.address,s.area')
			->alias('r')
			->join('users u on r.user_id = u.id', 'left')
			->join('user_address as s on r.user_id=s.user_id', 'left')
			->where($where)
			->limit($limit)
			->select();
	}

	/*
	 * 根据reserve_id 获取技能信息
	 */
	public function get_skill_info_by_reserve_id($reserve_id){

		return $this->field('r.*,s.user_id as publish_user_id,s.status as pulish_status,t.free_type')
			->alias('r')
			->join('user_skill s on r.skill_id = s.id', 'left')
			->join('skill_type t on s.type_id = t.id', 'left')
			->where(['r.id' => $reserve_id])
			->find();
	}

	/*
	 * 获取预约详情
	 */
	public function get_reserve_info_by_id($reserve_id){
		return $this->field('r.*,k.user_id as publish_user_id,k.status as pulish_status,u.user_name,u.head_img,u.is_vefify,u.birthday,s.province,s.city,s.address,s.area,u.weixin_account,u.mobile_number')
			->alias('r')
			->join('user_skill k on r.skill_id = k.id', 'left')
			->join('users u on r.user_id = u.id', 'left')
			->join('user_address as s on r.user_id=s.user_id', 'left')
			->where(['r.id' => $reserve_id])
			->find();
	}
}