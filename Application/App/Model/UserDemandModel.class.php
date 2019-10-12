<?php
namespace App\Model;
use Think\Model;

class UserDemandModel extends CommonModel{

	public function _before_insert(&$data, $options)
	{
		$data['update_time'] = time();
		$data['add_time'] = time();
	}

	public function _before_update(&$data, $options)
	{
		$data['update_time'] = time();
	}
}