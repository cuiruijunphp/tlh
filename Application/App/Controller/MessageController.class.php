<?php
namespace App\Controller;
use Think\Controller;
class MessageController extends BaseController {

	/**
	 * 发送消息
	 * @author cuirj
	 * @date   2019/9/27 下午12:48
	 *
	 * @param  int param
	 * @method post
	 * @return  array
	 */
    public function send_message(){
//		$params = I('post.');

		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$sender_uid = $this->user_id;
		$received_uid = $params['received_uid'];
		$content = $params['content'];
		$type = $params['type'] ? $params['type'] : 1;

		//如果$type=2,则取判断当前需求是否存在
		if($type == 2){

		}

		$dialog_model = D('Dialog');
		$message_model = D('Message');

		$where = '(sender_uid = ' . $sender_uid . ' and recived_uid = ' . $received_uid . ') or (sender_uid = ' . $received_uid . ' and recived_uid = ' . $sender_uid . ')';

		$dialog_query_result = $dialog_model->where($where)->find();

		$dialog_id = $dialog_query_result['id'];

		if(!$dialog_query_result){
			//创建dialog对话框
			$insert_data = [
				'sender_uid' => $sender_uid,
				'recived_uid' => $received_uid,
				'add_time' => time(),
			];
			$insert_dialog_result = $dialog_model->insert_one($insert_data);

			if($insert_dialog_result === false){
				$this->result_return(null, 500, '发送失败');
			}

			$dialog_id = $insert_dialog_result;
		}

		//插入本次的数据
		$message_insert_data = [
			'uid' => $sender_uid,
			'content' => $content,
			'dialog_id' => $dialog_id,
			'add_time' => time(),
		];

		$message_insert_result = $message_model->insert_one($message_insert_data);

		if($message_insert_result === false){
			$this->result_return(null, 500, '发送消息失败');
		}

		//更新对话框为启用状态
		$result = $dialog_model->get_one(['id' => $dialog_id]);
		if($result['sender_uid'] == $sender_uid){
			$update_data['sender_remove'] = 0;
		}else{
			$update_data['recived_remove'] = 0;
		}

		$dialog_model->update_data(['id' => $dialog_id], $update_data);

		$this->result_return(['result' => 1]);
    }

	/**
	 * 获取当前登录用户的消息列表
	 * @author cuirj
	 * @date   2019/9/27 下午6:27
	 * @url    app/message/get_dialog_list/
	 * @method post
	 *
	 * @param  int page
	 * @param  int page_size
	 * @return  array
	 */
    public function get_dialog_list(){

    	$page =  I('get.page') ? I('get.page') : 1;
    	$page_size =  I('get.page_size') ? I('get.page_size') : 6;

		$uid = $this->user_id;
		$where = '(sender_uid = ' . $uid .' and sender_remove = 0) or (recived_uid = ' . $uid . ' and recived_remove = 0)';

		$limit = ($page - 1) * $page_size;

		$dialog_model = D('Dialog');
		$message_model = D('Message');

		$dialog_list = $dialog_model->get_list($where, $limit. ',' . $page_size);

		if($dialog_list){
			$user_model = D('Users');

			//读取对话信息
			foreach($dialog_list as $k => $v){
				//取用户信息和最后一条记录
				if($v['sender_uid'] == $uid){
					$user_info = $user_model->get_one(['id' => $v['recived_uid']]);
				}else{
					$user_info = $user_model->get_one(['id' => $v['sender_uid']]);
				}

				//用户信息
				$dialog_list[$k]['user_info'] = [
					'user_name' => $user_info['user_name'],
					'is_online' => $user_info['is_online'],
					'head_img' => $user_info['head_img'],
					'uid' => $user_info['id'],
				];

				// 最后一条记录
				$message_info = $message_model->get_one(['dialog_id' => $v['id']], 'add_time desc');
				$dialog_list[$k]['message_info'] = [
					'content' => $message_info['content'],
					'add_time' => $message_info['add_time'],
					'uid' => $message_info['uid'],
				];
			}

			$this->result_return($dialog_list);
		}
	}

	/**
	 * 获取和某人聊天的消息
	 * @author cuirj
	 * @date   2019/9/27 下午7:00
	 * @url    app/message/get_dialog_detail/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function get_dialog_detail(){

		$received_uid = I('get.user_id');
		$sender_uid = $this->user_id;

		$page =  I('get.page') ? I('get.page') : 1;
		$page_size =  I('get.page_size') ? I('get.page_size') : 6;

		$limit = ($page - 1) * $page_size;

		$where = '(sender_uid = ' . $sender_uid . ' and recived_uid = ' . $received_uid . ') or (sender_uid = ' . $received_uid . ' and recived_uid = ' . $sender_uid . ')';

		$dialog_model = D('Dialog');
		$user_model = D('Users');

		$dialog_info = $dialog_model->where($where)->find();

		$user_info = $user_model->get_one(['id' => $received_uid]);
		$part_user_info = [
			'user_name' => $user_info['user_name'],
			'is_online' => $user_info['is_online'],
			'head_img' => $user_info['head_img'],
			'uid' => $user_info['id'],
		];


		if(!$dialog_info){
			$this->result_return(['message_list' => [], 'user_info' => $part_user_info]);
		}

		$dialog_id = $dialog_info['id'];

		if($dialog_info['sender_uid'] == $this->user_id){
			$is_del = $dialog_info['sender_remove'];
			$where['sender_remove'] = 0;
		}else{
			$is_del = $dialog_info['recived_uid'];
			$where['recived_remove'] = 0;
		}

		if($is_del == 1){
			$this->result_return([]);
		}

		$where['dialog_id'] = $dialog_id;

		$message_model = D('Message');
		$message_list = $message_model->get_list($where, $limit. ',' . $page_size,  'add_time desc');

		$data = [
			'message_list' => $message_list,
			'user_info' => $part_user_info,
		];

		$this->result_return($data);
	}

	/**
	 * 删除和某个用户的对话框
	 * @author cuirj
	 * @date   2019/9/27 下午12:48
	 *
	 * @param  int param
	 * @method post
	 * @return  array
	 */
	public function del_dialog(){

		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$dialog_id = $params['dialog_id'];

		//查询是否是自己的，否则不允许删除
		$dialog_model = D('Dialog');
		$message_model = D('Message');

		$dialog_info = $dialog_model->get_one(['id' => $dialog_id]);
		if($this->user_id != $dialog_info['sender_uid'] && $this->user_id != $dialog_info['recived_uid']){
			$this->result_return(null, 500, '不能删除别人的对话哦');
		}

		if($this->user_id == $dialog_info['sender_uid']){
			// 把dialog对话框置为删除状态
			$update_result = $dialog_model->update_data(['id' => $dialog_id], ['sender_remove' => 1]);

			//将相关对话也置为删除状态
			$update_message_result = $message_model->update_data(['dialog_id' => $dialog_id], ['sender_remove' => 1]);
		}else{
			$update_result = $dialog_model->update_data(['id' => $dialog_id], ['revived_remove' => 1]);

			//将相关对话也置为删除状态
			$update_message_result = $message_model->update_data(['dialog_id' => $dialog_id], ['revived_remove' => 1]);
		}

		if($update_result === false){
			$this->result_return(null, 500, '删除对话失败');
		}

		$this->result_return(['result' => 1]);
	}

}