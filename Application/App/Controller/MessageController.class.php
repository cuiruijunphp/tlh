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

		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$sender_uid = $this->user_id;
		$received_uid = $params['received_uid'];
		$content = $params['content'];
		$type = $params['type'] ? $params['type'] : 1;

		if(!$received_uid){
			$this->result_return(null, 1, '参数错误');
		}

		$dialog_model = D('Dialog');
		$message_model = D('Message');

		// 如果当天联系的人已经超过了20条，则给出提示
//        $vip_expire_time = $this->user_info['vip_expire_time'];
//
//        if($vip_expire_time < time()){
//            $is_vip = 0;
//        }else{
//            $is_vip = 1;
//        }

        // 今天已经联系的人
//        $message_model = D('Message');
//        $start_time = strtotime(date('Y-m-d', time()));
//        $end_time = $start_time + 24 * 3600;

        // vip每天只能和20个人联系
//        $message_count = $message_model->get_user_message_count($this->user_id, $start_time, $end_time);
//
//        $limit_count = $is_vip ? C('max_publish_limit')['vip_contact_limit'] : C('max_publish_limit')['isnot_vip_contact_limit'];
//        if($message_count >= $limit_count){
//            $this->result_return(null, 1, '今天已经超过联系人数上限，请明日再重试');
//        }

//		$where = '(sender_uid = ' . $sender_uid . ' and recived_uid = ' . $received_uid . ') or (sender_uid = ' . $received_uid . ' and recived_uid = ' . $sender_uid . ')';

		$dialog_query_result = $dialog_model->get_dialog_by_uids($sender_uid, $received_uid);

		$dialog_id = $dialog_query_result['id'];

		if(!$dialog_query_result){
			//创建dialog对话框
			$insert_data = [
				'sender_uid' => $sender_uid,
				'recived_uid' => $received_uid,
			];
			$insert_dialog_result = $dialog_model->insert_one($insert_data);

			if($insert_dialog_result === false){
				$this->result_return(null, 1, '发送失败');
			}

			$dialog_id = $insert_dialog_result;
		}

		//插入本次的数据
		$message_insert_data = [
			'uid' => $sender_uid,
			'content' => $content,
			'dialog_id' => $dialog_id,
		];

		$message_insert_result = $message_model->insert_message($message_insert_data);

		if($message_insert_result === false){
			$this->result_return(null, 1, '发送消息失败');
		}

		//更新对话框为启用状态
		$result = $dialog_model->update_dialog_active($dialog_id, $sender_uid);

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

		$dialog_list = $dialog_model->get_list($where, $limit. ',' . $page_size, 'update_time desc');

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
					'head_img' => $user_info['head_img'] ? UPLOAD_URL . $user_info['head_img'] : '',
					'uid' => $user_info['id'],
				];

				// 最后一条记录
				$message_info = $message_model->get_one(['dialog_id' => $v['id'], 'type' => 1], 'add_time desc');
				$dialog_list[$k]['message_info'] = [
					'content' => $message_info['content'],
					'add_time' => $message_info['add_time'],
					'uid' => $message_info['uid'],
				];
			}
		}

		$dialog_list = $dialog_list ? $dialog_list : [];

		$this->result_return($dialog_list);
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

		if($sender_uid == $received_uid){
			$this->result_return(null, 1, '不能给自己发消息');
		}

		if(!$received_uid){
			$this->result_return(null, 1, '缺少参数');
		}

		$page =  I('get.page') ? I('get.page') : 1;
		$page_size =  I('get.page_size') ? I('get.page_size') : 6;

		$limit = ($page - 1) * $page_size;

		$dialog_model = D('Dialog');
		$user_model = D('Users');

		$dialog_info = $dialog_model->get_dialog_by_uids($sender_uid, $received_uid);

		$user_info = $user_model->get_one(['id' => $received_uid]);
		$part_user_info = [
			'user_name' => $user_info['user_name'],
			'is_online' => $user_info['is_online'],
			'head_img' => $user_info['head_img'] ? UPLOAD_URL . $user_info['head_img'] : '',
			'uid' => $user_info['id'],
		];

		if(!$dialog_info){
			$this->result_return(['message_list' => [], 'user_info' => $part_user_info]);
		}

		$dialog_id = $dialog_info['id'];

		if($dialog_info['sender_uid'] == $this->user_id){
			$is_del = $dialog_info['sender_remove'];
			$message_where['sender_remove'] = 0;
			$dialog_update_data['sender_unread'] = 0;
		}else{
			$is_del = $dialog_info['recived_remove'];
			$message_where['recived_remove'] = 0;
			$dialog_update_data['recived_unread'] = 0;
		}

		// 更新未读消息数量
		$dialog_model->update_data(['id' => $dialog_id], $dialog_update_data);

		if($is_del == 1){
			$this->result_return(['message_list' => [], 'user_info' => $part_user_info]);
		}

		$message_where['dialog_id'] = $dialog_id;

		$common_message_where = $message_where;
		$special_message_where = $message_where;

		$common_message_where['type'] = 1;
		$special_message_where['type'] = ['in', '2,3'];

		$message_model = D('Message');
		$message_list = $message_model->get_list($common_message_where, $limit. ',' . $page_size,  'add_time desc');

		// 取最近的一条有效的需求/技能
		if($page == 1){
			$skill_demand = $message_model->get_list($special_message_where, null, 'add_time desc');
			$demand_model = D('UserDemand');
			$skill_model = D('UserSkill');
			$skill_reserve_model = D('SkillReserve');

			$type_result = [];

			if($skill_demand){
				foreach($skill_demand as $s_k => $s_v){
					if($s_v['type'] == 2){
						// 查看需求是否完成
						$demand_info = $demand_model->get_one(['id' => $s_v['type_id']]);
						if($demand_info['status'] == 3){
							$type_result = $s_v;

							$type_result['title'] = $demand_info['title'];
							$type_result['earnest_money'] = $demand_info['earnest_money'];

							break;
						}
					}else{
						// 技能
						$skill_reserve_info = $skill_reserve_model->get_skill_info_by_reserve_id($s_v['type_id']);

						if($skill_reserve_info['status'] == 3){
							$type_result = $s_v;

							$type_result['title'] = $skill_reserve_info['skill_name'];
							$type_result['earnest_money'] = $skill_reserve_info['price'];
							$type_result['desc'] = $skill_reserve_info['desc'];

							break;
						}
					}
				}
			}

			//当做一条普通消息处理
			if($type_result){
				$message_list[] = $type_result;
			}
		}

		$data = [
			'message_list' => $message_list ? array_reverse($message_list) : [],
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
			$this->result_return(null, 1, '不能删除别人的对话哦');
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
			$this->result_return(null, 1, '删除对话失败');
		}

		$this->result_return(['result' => 1]);
	}

	/**
	 * 获取新消息
	 * @date   2019/12/27 上午11:03
	 * @url    app/message/get_new_message/
	 * @method get
	 *
	 * @param  int param
	 *             return  array
	 */
	public function get_new_message(){
		$dialog_id = I('get.dialog_id');

		$dialog_model = D('Dialog');
		$dialog_info = $dialog_model->get_one(['id' => $dialog_id]);

		if($dialog_info['sender_uid'] == $this->user_id){
			$unread = $dialog_info['sender_unread'];
			$message_where['sender_remove'] = 0;
			$other_uid = $dialog_info['recived_uid'];

			// 未读消息置为0
			$dialog_update_data['sender_unread'] = 0;
		}else{
			$unread = $dialog_info['recived_unread'];
			$message_where['recived_remove'] = 0;
			$other_uid = $dialog_info['sender_uid'];

			$dialog_update_data['recived_unread'] = 0;
		}

		// 如果未读消息是 0,则返回空数组
		if($unread == 0){
			$this->result_return([]);
		}

		//更新未读消息为0
		$dialog_model->update_data(['id' => $dialog_id], $dialog_update_data);

		// 取对话的用户信息
		$user_model = D('Users');
		$user_info = $user_model->get_one(['id' => $other_uid]);
		$part_user_info = [
			'user_name' => $user_info['user_name'],
			'is_online' => $user_info['is_online'],
			'head_img' => $user_info['head_img'] ? UPLOAD_URL . $user_info['head_img'] : '',
			'uid' => $user_info['id'],
		];

		$message_where['dialog_id'] = $dialog_id;

		$message_model = D('Message');

		// 取未读消息的条数
		$message_list = $message_model->get_list($message_where, '0,' . $unread,  'add_time desc');
		$demand_model = D('UserDemand');

		$message_list = array_reverse($message_list);

		foreach($message_list as $s_k => $s_v){
			if($s_v['type'] == 2){
				// 查看需求是否完成
				$demand_info = $demand_model->get_one(['id' => $s_v['type_id']]);
				if($demand_info['status'] == 3){
					$type_result = $s_v;

					$type_result['title'] = $demand_info['title'];
					$type_result['earnest_money'] = $demand_info['earnest_money'];
				}
			}elseif($s_v['type'] == 3){
				// 技能
				$skill_reserve_model = D('SkillReserve');
				$skill_reserve_info = $skill_reserve_model->get_skill_info_by_reserve_id($s_v['type_id']);

				if($skill_reserve_info['status'] == 3){
					$type_result = $s_v;

					$type_result['title'] = $skill_reserve_info['skill_name'];
					$type_result['earnest_money'] = $skill_reserve_info['price'];
					$type_result['desc'] = $skill_reserve_info['desc'];
				}
			}else{
				// 取普通的消息
				$result[] = $s_v;
			}
		}

		if($type_result){
			if($result){
				$message_list_result[] = $type_result;
				$message_list_result = array_merge($message_list_result, $message_list_result);
			}else{
				$message_list_result[] = $type_result;
			}
		}else{
			$message_list_result = $result;
		}

		$data = [
			'message_list' => $message_list_result,
			'user_info' => $part_user_info,
		];

		$this->result_return($data);
	}

	/*
	 * 获取是否有新消息提醒
	 */
	public function get_new_message_notice(){
        $last_request_time = I('get.last_time');
        $message_model = D('Dialog');

        $new_message_info = $message_model->get_new_message($this->user_id);
        $last_flush_new_message_info = $message_model->get_last_flush_new_message($this->user_id, $last_request_time);

        $new_message_notice = [];
        if($new_message_info){
            foreach($new_message_info as $k => $v){
                if($v['sender_uid'] == $this->user_id){
                    if($v['sender_unread'] > 0){
                        $new_message_notice[] = [
                            'dialog_id' => $v['id'],
                            'unread' => $v['sender_unread'],
                        ];
                    }
                }else{
                    if($v['recived_unread'] > 0){
                        $new_message_notice[] = [
                            'dialog_id' => $v['id'],
                            'unread' => $v['recived_unread'],
                        ];
                    }
                }
            }
        }

        $last_flush_new_message_notice = [];
        if($last_flush_new_message_info){
            foreach($last_flush_new_message_info as $kk => $vv){
                if($vv['sender_uid'] == $this->user_id){
                    if($vv['sender_unread'] > 0){
                        $last_flush_new_message_notice[] = [
                            'dialog_id' => $vv['id'],
                            'unread' => $vv['sender_unread'],
                        ];
                    }
                }else{
                    if($vv['recived_unread'] > 0){
                        $last_flush_new_message_notice[] = [
                            'dialog_id' => $vv['id'],
                            'unread' => $vv['recived_unread'],
                        ];
                    }
                }
            }
        }

        $data['new_message_notice'] = $new_message_notice;
        $data['last_flush_new_message_notice'] = $last_flush_new_message_notice;

        $this->result_return($data);
    }
}