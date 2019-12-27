<?php
namespace App\Model;
use Think\Model;

class MessageModel extends CommonModel{

	/*
	 * 将消息插入到消息列表中
	 */
	public function insert_message($insert_message_data){

		//插入message表
		$result = $this->insert_one($insert_message_data);

		if($result){
			//更新未读消息列表中未读消息数量
			$dialog_model = D('Dialog');
			$dialog_res = $dialog_model->get_one(['id' => $insert_message_data['dialog_id']]);

			$dialog_model->update_data(['id' => $insert_message_data['dialog_id']], ['sender_unread' => $dialog_res['sender_unread'] + 1, 'recived_unread' => $dialog_res['recived_unread'] + 1]);
		}

		return $result;
	}
}