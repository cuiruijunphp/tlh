<?php
namespace App\Controller;
use Think\Controller;
class SkillController extends BaseController {

	/**
	 * 获取技能类型列表
	 * @author cuirj
	 * @date   2019/9/27 下午12:48
	 * @url    /app/skill/get_skill_type_list/
	 * @param  int param
	 * @method post
	 * @return  array
	 */
    public function get_skill_type_list(){

		$skill_type_model = D('SkillType');
		$skill_type_result = $skill_type_model->get_list();

		$this->result_return($skill_type_result);
    }

	/**
	 * 获取当前登录用户的消息列表
	 * @author cuirj
	 * @date   2019/9/27 下午6:27
	 * @url    app/skill/publish_skill/
	 * @method post
	 *
	 * @param  int page
	 * @param  int page_size
	 * @return  array
	 */
    public function publish_skill(){
		$params = I('post.');

		$skill_name = $params['skill_name'];
		$type_id = $params['type_id'];
		$desc = $params['desc'];
		$price = $params['price'];
		$superiority = $params['superiority'];
		$mode = $params['mode'];
		$good_at = $params['good_at'];
		$longitude = $params['longitude'];
		$latitude = $params['latitude'];


		// 不存在的技能则不插入数据库中
		$skill_type_model = D('SkillType');
		$skill_type_info = $skill_type_model->get_one(['id' => $type_id]);
		if(!$skill_type_info || !$skill_type_info['is_show']){
			$this->result_return(null, 500, '技能类型有误');
		}

		//上传技能图片
		if(!$_FILES){
			$this->result_return(null, 500, '请上传技能图片');
		}

		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize   =     2048000 ;// 设置附件上传大小
		$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
		$upload->rootPath  =      './Uploads/'; // 设置附件上传根目录
		$upload->savePath  =      $this->user_id . '/' ; // 设置附件上传（子）目录
		//		$upload->saveName  =      $this->user_id . '_' . time(); // 设置附件上传（子）目录
		// 上传文件
		$info  =  $upload->upload();
		if(!$info) {// 上传错误提示错误信息
			$this->result_return(null, 500, $upload->getError());
		}else{// 上传成功 获取上传文件信息
			foreach($info as $file){
				$file_path = $file['savepath'] . $file['savename'];
			}
		}

		$skill_mode = D('UserSkill');
		$skill_insert_data = [
			'skill_name' => $skill_name,
			'type_id' => $type_id,
			'desc' => $desc,
			'price' => $price,
			'superiority' => $superiority,
			'mode' => $mode,
			'good_at' => $good_at,
			'add_time' => time(),
			'img' => $file_path,
			'user_id' => $this->user_id,
			'longitude' => $longitude,
			'latitude' => $latitude,
		];

		$insert_result = $skill_mode->insert_one($skill_insert_data);

		if($insert_result === false){
			$this->result_return(null, 500, '发布技能失败');
		}

		$this->result_return(['result' => 1]);
	}

	/**
	 * 获取技能列表
	 * @author cuirj
	 * @date   2019/10/11 下午6:14
	 * @url    app/skill/get_skill_list/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function get_skill_list(){
		$user_id = I('get.user_id');

		if(!$user_id){
			// 如果是在自己的列表里,则需要给个标识
			$user_id = $this->user_id;
			$is_self = 1;
		}

		$where = [
			'user_id' => $user_id,
		];

		if(!$is_self){
			$where['status'] = 1;
		}

		$skill_mode = D('UserSkill');
		$skill_list = $skill_mode->get_list($where);

		if($skill_list){
			foreach($skill_list  as $k => $v){
				$skill_list[$k]['img'] = UPLOAD_URL . $v['img'];
			}
		}

		$this->result_return($skill_list);
	}

	/**
	 * 获取技能详细信息
	 * @author cuirj
	 * @date   2019/10/11 下午6:14
	 * @url    app/skill/get_skill_info/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function get_skill_info(){
		$skill_id = I('get.skill_id');
		$user_id = I('get.user_id');

		if($user_id){
			$is_other = 1;
		}

		$skill_mode = D('UserSkill');
		$skill_info = $skill_mode->get_one(['id' => $skill_id]);

		if(!$skill_info){
			$this->result_return(null, 500, '没有找到该项技能');
		}

		if($is_other && $skill_info['status'] != 1){
			$this->result_return(null, 500, '该项技能未审核通过或者待审核');
		}

		$skill_list['img'] = UPLOAD_URL . $skill_info['img'];

		$this->result_return($skill_info);
	}
}