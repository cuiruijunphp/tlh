<?php
namespace App\Model;
use Think\Model;

class DialogModel extends CommonModel{

	/*
	 * 根据uid获取对话框id
	 */
	public function get_dialog_by_uids($sender_uid, $received_uid){

		$where = '(sender_uid = ' . $sender_uid . ' and recived_uid = ' . $received_uid . ') or (sender_uid = ' . $received_uid . ' and recived_uid = ' . $sender_uid . ')';

		return $this->where($where)->find();
	}

	/*
	 * 更新对话框为启用状态
	 */
	public function update_dialog_active($dialog_id, $sender_uid){

		$result = $this->get_one(['id' => $dialog_id]);
		if($result['sender_uid'] == $sender_uid){
			$update_data['sender_remove'] = 0;
		}else{
			$update_data['recived_remove'] = 0;
		}

		return $this->update_data(['id' => $dialog_id], $update_data);
	}

	/*
	 * 创建对话框
	 */
	public function create_dialog($sender_uid, $received_uid){
		$insert_data = [
			'sender_uid' => $sender_uid,
			'recived_uid' => $received_uid,
		];
		return $this->insert_one($insert_data);
	}
}