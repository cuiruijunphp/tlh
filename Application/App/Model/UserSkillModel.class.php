<?php
namespace App\Model;
use Think\Model;

class UserSkillModel extends CommonModel{

	public function get_skill_demand_by_type_id($type_id){
		$sql = 'select * from user_skill where type_id = ' . $type_id;
		$sql .= 'select * from user_demand where type_id = ' . $type_id;

		return $this->query($sql);
	}
}