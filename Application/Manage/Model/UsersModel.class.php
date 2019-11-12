<?php
namespace Manage\Model;
use Think\Model;

class UsersModel extends CommonModel{

	/*
	 * 通过uids,查询个人信息, 逗号隔开的
	 */
	public function get_user_info_part_by_uids($uids){

		$where['u.id'] = ['in', $uids];
		return $this->field('u.id,u.user_name,u.head_img,u.is_vefify,u.birthday,s.province,s.city,s.address,s.area')
			->alias('u')
			->where($where)
			->join('user_address as s on u.id=s.user_id', 'left')
			->select();
	}

	/*
	 * 通过uids和技能类型id,查询个人信息, 技能信息和地址信息
	 */
	public function get_user_info_skill_by_uids($uids, $type_id){

		$where['u.id'] = ['in', $uids];
		$where['l.type_id'] = $type_id;
		return $this->field('u.id,u.user_name,u.head_img,u.is_vefify,u.birthday,u.weixin_account, u.mobile_number,s.province,s.city,s.address,s.area,l.desc,l.superiority')
			->alias('u')
			->where($where)
			->join('user_address as s on u.id=s.user_id', 'left')
			->join('user_skill as l on u.id=l.user_id', 'left')
			->select();
	}

	/*
	 * 根据传入的where条件来取用户信息
	 */
	public function get_user_info_by_where($where, $page = 1, $page_size = 10){
		return $this->field('u.*,s.province,s.city,s.address,s.area')
			->alias('u')
			->where($where)
			->join('user_address as s on u.id=s.user_id', 'left')
			->page($page, $page_size)
			->select();
	}

	/*
	 * 根据传入的where条件来取用户总数
	 */
	public function get_user_count_by_where($where){
		return $this->alias('u')
			->where($where)
			->join('user_address as s on u.id=s.user_id', 'left')
			->count();
	}
}