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

		if($sender_uid == $received_uid){
			return false;
		}

		$insert_data = [
			'sender_uid' => $sender_uid,
			'recived_uid' => $received_uid,
		];
		return $this->insert_one($insert_data);
	}

    /*
     * 上次刷新时间以后有没有新的消息
     */
    public function get_last_flush_new_message($user_id, $last_request_time){

        $where['sender_uid']  = $user_id;
        $where['recived_uid']  = $user_id;
        $where['_logic'] = 'or';
        $map['_complex'] = $where;
        $map['update_time'] = ['gt',$last_request_time];

        return $this->where($map)->select();
    }

    /*
     * 未读消息
     */
    public function get_new_message($user_id){

        $where['sender_uid']  = $user_id;
        $where['recived_uid']  = $user_id;
        $where['_logic'] = 'or';
        $map['_complex'] = $where;

        return $this->where($map)->select();
    }
}