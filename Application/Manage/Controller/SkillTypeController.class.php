<?php
namespace Manage\Controller;
use Think\Controller;
class SkillTypeController extends BaseController {

    public function index(){

		$skill_type_model = D('SkillType');

		$page = I('get.p');
		$skill_type_list = $skill_type_model->get_page_list(null, $page);
		$skill_type_count = $skill_type_model->get_count();
		$data['list'] = $skill_type_list;
		// 加上分页
		$data['page'] = $this->page_new($skill_type_count);

		$this->assign($data);
		$this->display();
    }

    public function edit(){
		$skill_type_model = D('SkillType');

		if (IS_POST) {
			$params = I('post.');

			if (!$params['type_name']) {
				$this->result_return(null, 500, '技能类型名称不能为空');
			}

			$insert_data = [
				'type_name' => $params['type_name'],
				'is_show' => (int)$params['is_show'],
				'parent_id' => (int)$params['parent_id'],
				'free_type' => (int)$params['free_type'],
			];

			//判断是否有图片
			//上传技能图片
			if($_FILES['img']['tmp_name']){
				$upload = new \Think\Upload();// 实例化上传类
				$upload->maxSize   =     2048000 ;// 设置附件上传大小
				$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
				$upload->rootPath  =      './Uploads/'; // 设置附件上传根目录
				$upload->savePath  =      'banner'. '/' ; // 设置附件上传（子）目录
				//		$upload->saveName  =      $this->user_id . '_' . time(); // 设置附件上传（子）目录
				// 上传文件
				$info  =  $upload->uploadOne($_FILES['img']);
				if(!$info) {// 上传错误提示错误信息
					$this->result_return(null, 500, $upload->getError());
				}else{// 上传成功 获取上传文件信息
					$file_path = $info['savepath'].$info['savename'];
				}
			}


			if(!$params['id']){
				//新增
				if(!$file_path){
					$this->result_return(null, 500, '请上传技能照片');
				}

				$insert_data['img'] = $file_path;
				$result = $skill_type_model->insert_one($insert_data);

				if($result){
					$this->result_return(['result' => 1]);
				}else{
					$this->result_return(null, 500, '添加技能类型失败');
				}
			}else{
				//修改
				//修改
				if($file_path){
					$insert_data['img'] = $file_path;
				}

				$update_result = $skill_type_model->update_data(['id' => $params['id']], $insert_data);
				if($update_result !== false){
					$this->result_return(['result' => 1]);
				}else{
					$this->result_return(null, 500, '修改技能类型失败');
				}
			}
		}

		//参数
		if (!empty(I('get.id'))){
			$skill_type_list = $skill_type_model -> get_one(['id' => I('get.id')]);
		}

		//加上所有一级分类的信息
		$parent_skill_type = $skill_type_model->get_list(['parent_id' => 0]);
		$data['parent_list'] = $parent_skill_type;

		$data['list'] = $skill_type_list;
		$this->assign($data);
		$this->display();
	}

	public function change_status()
	{
		$skill_type_model = D('SkillType');

		//判断当前传的参数和数据库中是否相同,如果相同则报错
		$where['id'] = I('post.id');
		$data['is_show'] = I('post.is_show');
		$result = $skill_type_model->update_data($where, $data);
		if ($result) {
			$this->result_return(['result' => 1]);
		} else {
			$this->result_return(null, 500, '修改失败,请重试');
		}
	}
}