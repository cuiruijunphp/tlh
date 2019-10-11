<?php
namespace App\Model;
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
}