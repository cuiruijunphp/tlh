<?php
namespace Manage\Controller;
use Think\Controller;
class BannerController extends BaseController {

    public function index(){

		$banner_model = D('Banner');

		$page = I('get.p');
		$banner_list = $banner_model->get_page_list(null, $page);
		$banner_count = $banner_model->get_count();
		$data['list'] = $banner_list;
		// 加上分页
		$data['page'] = $this->page_new($banner_count);

		$this->assign($data);
		$this->display();
    }

	public function edit(){
		$banner_model = D('Banner');

		if (IS_POST) {
			$params = I('post.');

			if (!$params['desc'] || !$params['url'] ||! $params['sort']) {
				$this->result_return(null, 500, '请检查参数是否填写完整');
			}

			$insert_data = [
				'desc' => $params['desc'],
				'url' => $params['url'],
				'sort' => (int)$params['sort'],
				'is_show' => (int)$params['show'],
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
				$result = $banner_model->insert_one($insert_data);

				if($result){
					$this->result_return(['result' => 1]);
				}else{
					$this->result_return(null, 500, '添加技能类型失败');
				}
			}else{
				//修改
				if($file_path){
					$insert_data['img'] = $file_path;
				}

				$update_result = $banner_model->update_data(['id' => $params['id']], $insert_data);
				if($update_result !== false){
					$this->result_return(['result' => 1]);
				}else{
					$this->result_return(null, 500, 'banner图修改失败');
				}
			}
		}

		//参数
		if (!empty(I('get.id'))){
			$banner_list = $banner_model -> get_one(['id' => I('get.id')]);
		}

		$data['list'] = $banner_list;
		$this->assign($data);
		$this->display();
	}

	public function del(){
		$id = I('post.id');
		$banner_model = D('Banner');
		$delete_data = $banner_model->delete_data(['id' => $id]);

		if($delete_data){
			$this->result_return(['result' => 1]);
		}else{
			$this->result_return(null, 500, '删除失败');
		}
	}
}