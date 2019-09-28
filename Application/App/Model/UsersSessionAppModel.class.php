<?php
namespace App\Model;
use Think\Model;

class UsersSessionAppModel extends CommonModel{

	/*
	 * 插入用户的登录信息
	 */
	public  function insert_user_session_app($uuid, $password, $user_id){

		if (!$uuid)
		{
			return false;
		}

		$token_id = $this->add([
			'id' => random_from_dev(168),
			'uuid' => $uuid,
			'modified' => time(),
			'lifetime' => 30 * 24 * 60 * 60,
			'user_id' => intval($user_id),
			'password' => $password,
		]);

		return $this->get_one(['uuid' => $uuid]);
	}
}