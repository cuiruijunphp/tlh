<?php
namespace App\Model;
use Think\Model;

class UserSkillModel extends CommonModel{

	public function _before_update(&$data, $options)
	{
		$data['update_time'] = time();
	}
}