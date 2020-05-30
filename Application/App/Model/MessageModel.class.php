<?php
namespace App\Model;
use Think\Model;

class MessageModel extends CommonModel{

	/*
	 * 将消息插入到消息列表中
	 * @insert_message_data 要插入的数据
	 */
	public function insert_message($insert_message_data){

		//插入message表
		$result = $this->insert_one($insert_message_data);

		if($result){
			//更新未读消息列表中未读消息数量
			$dialog_model = D('Dialog');
			$dialog_res = $dialog_model->get_one(['id' => $insert_message_data['dialog_id']]);

			if($insert_message_data['uid'] == $dialog_res['recived_uid']){
			    $unread = [
                    'sender_unread' => $dialog_res['sender_unread'] + 1
                ];
            }else{
                $unread = [
                    'recived_unread' => $dialog_res['recived_unread'] + 1
                ];
            }

			$dialog_model->update_data(['id' => $insert_message_data['dialog_id']], $unread);
		}

		return $result;
	}

	/*
	 * 获取用户今日已经跟多少人联系过了 
	 * @param user_id 要获取的用户的id
	 * @param begin_time 开始的时间
	 * @param end_time 结束的时间
	 */
	public function get_user_message_count($user_id, $begin_time, $end_time){
        return $this->distinct(true)
            ->field('dialog_id')
            ->where(['type' => 1, 'user_id' => $user_id, 'add_time' => ['gt' => $begin_time, 'lt' => $end_time]])
            ->count();
    }
}