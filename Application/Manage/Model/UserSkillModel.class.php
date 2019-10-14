<?php
namespace Manage\Model;
use Think\Model;

class UserSkillModel extends CommonModel{

	public function _before_insert(&$data, $options)
	{
		$data['update_time'] = time();
	}

	public function _before_update(&$data, $options)
	{
		$data['update_time'] = time();
	}
}