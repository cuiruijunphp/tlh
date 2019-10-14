<?php
namespace Manage\Model;
use Think\Model;

class DialogModel extends CommonModel{

	/*
	 * 获取对话框列表
	 */
	public function get_dialog_list(){
		return $this->field()
			->where()
			->join('users on ')
			->select();
	}
}