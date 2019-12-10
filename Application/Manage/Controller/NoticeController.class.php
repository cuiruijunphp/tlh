<?php
namespace Manage\Controller;
use Think\Controller;
class NoticeController extends BaseController {

    public function index(){

		$notice_model = D('Notice');

		$page = I('get.p');
		$notice_list = $notice_model->get_page_list(null, $page);
		$notice_count = $notice_model->get_count();
		$data['list'] = $notice_list;
		// 加上分页
		$data['page'] = $this->page_new($notice_count);

		$this->assign($data);
		$this->display();
    }

    public function edit(){
		$notice_model = D('Notice');

		if (IS_POST) {
			$params = I('post.');

			if (!$params['title'] || !$params['content']) {
				$this->result_return(null, 500, '标题和内容不能为空');
			}

			$insert_data = [
				'title' => $params['title'],
				'content' => $params['content'],
			];

			if(!$params['id']){
				//新增
				$result = $notice_model->insert_one($insert_data);

				if($result){
					$this->result_return(['result' => 1]);
				}else{
					$this->result_return(null, 500, '添加公告失败');
				}
			}else{
				//修改
				$update_result = $notice_model->update_data(['id' => $params['id']], $insert_data);
				if($update_result !== false){
					$this->result_return(['result' => 1]);
				}else{
					$this->result_return(null, 500, '修改公告失败');
				}
			}
		}

		//参数
		if (!empty(I('get.id'))){
			$skill_type_list = $notice_model -> get_one(['id' => I('get.id')]);
		}

		$data['list'] = $skill_type_list;
		$this->assign($data);
		$this->display();
	}

	public function del()
	{
		$notice_model = D('Notice');

		//判断当前传的参数和数据库中是否相同,如果相同则报错
		$where['id'] = I('post.id');
		$result = $notice_model->del_data($where);
		if ($result) {
			$this->result_return(['result' => 1]);
		} else {
			$this->result_return(null, 500, '修改失败,请重试');
		}
	}
}