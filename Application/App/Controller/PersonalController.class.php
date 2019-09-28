<?php
namespace App\Controller;
use Think\Controller;
class PersonalController extends BaseController {

	/**
	 * 设置用户名
	 * @author cuirj
	 * @date   2019/9/27 下午12:48
	 *
	 * @param  int param
	 * @method post
	 * @return  array
	 */
    public function set_user_name(){
		$params = I('post.');

		$user_name = $params['user_name'];

		$user_model = D('Users');

		$update_result = $user_model->update_data(['id' => $this->user_id], ['user_name' => $user_name]);

		if($update_result === false){
			$this->result_return(null, 500, '更新用户名失败,请换一个用户名试试');
		}

		$this->result_return(['result' => 1]);
    }

	/**
	 * 设置基本资料
	 * @author cuirj
	 * @date   2019/9/27 下午6:27
	 * @url    app/personal/set_pro
	 * @method post
	 *
	 * @param  int param
	 * @return  array
	 */
    public function set_profile(){
		$params = I('post.');

		$sex = $params['sex'] ? $params['sex'] : 1;
		$birthday = $params['birthday'] ? strtotime($params['birthday']) : 0;
		$company = $params['company'];
		$desc = $params['desc'];
		$address = $params['address'];
		$province = $params['province'];
		$city = $params['city'];
		$area = $params['area'];

		$user_model = D('Users');

		$user_data = [
			'sex' => $sex,
			'birthday' => $birthday,
			'company' => $company,
			'desc' => $desc,
		];
		$users_update_result = $user_model->update_data(['id' => $this->user_id], $user_data);

		if($users_update_result === false){
			$this->result_return(null, 500, '更新用户资料失败');
		}

		//更新地址信息
		$user_address_model = D('UserAddress');

		$address_info = $user_address_model->get_one(['user_id' => $this->user_id]);
		$address_data = [
			'province' => $province,
			'city' => $city,
			'area' => $area,
			'address' => $address,
		];

		if($address_data){
			//更新地址信息
			if($address_info){
				//更新
				$address_result = $user_address_model->update_data(['id' => $address_info['id']], $address_data);
			}else{
				//插入
				$address_data['user_id'] = $this->user_id;
				$address_result = $user_address_model->insert_one($address_data);
			}

			if($address_result === false){
				$this->result_return(null, 500, '更新地址信息失败');
			}
		}

		$this->result_return(['result' => 1]);

	}

	/**
	 * 设置头像
	 * @author cuirj
	 * @date   2019/9/27 下午7:00
	 * @url    app/personal/set_head_img/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function set_head_img(){
		if(!$_FILES){
			$this->result_return(null, 500, '请上传文件');
		}

		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize   =     2048000 ;// 设置附件上传大小
		$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
		$upload->rootPath  =      './Uploads/'; // 设置附件上传根目录
		$upload->savePath  =      ''; // 设置附件上传（子）目录
		$upload->saveName  =      $this->user_id . '_' . time(); // 设置附件上传（子）目录
		// 上传文件
		$info  =  $upload->upload();
		if(!$info) {// 上传错误提示错误信息
			$this->error($upload->getError());
		}else{// 上传成功 获取上传文件信息
			foreach($info as $file){
				$file_path =  $file['savepath'] . $file['savename'];

				$user_model = D('Users');

				$update_result = $user_model->update_data(['id' => $this->user_id], ['head_img' => $file_path]);

				if($update_result === false){
					$this->result_return(null, 500, '更新头像失败');
				}

				$this->result_return(['result' => 1]);
			}
		}
	}

	/**
	 * 设置在线状态
	 * @author cuirj
	 * @date   2019/9/27 下午12:48
	 *
	 * @param  int param
	 * @method post
	 * @return  array
	 */
	public function set_online(){
		$params = I('post.');

		$is_online = $params['is_online'];

		$user_model = D('Users');

		$update_result = $user_model->update_data(['id' => $this->user_id], ['is_online' => $is_online]);

		if($update_result === false){
			$this->result_return(null, 500, '设置在线状态失败');
		}

		$this->result_return(['result' => 1]);
	}

	/**
	 * 获取个人信息
	 * @author cuirj
	 * @date   2019/9/27 下午7:49
	 * @url    domain/xxxx/xxxx
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function get_user_info(){
		//实例化model
		$user_model = D('Users');

		$user_info = $user_model->get_one(['id' => $this->user_id]);

		if(!$user_info)
		{
			$this->result_return(null, 500, '未查询到个人信息');
		}

		//返回个人信息和token信息
		$data = [
			'user_name' => $user_info['user_name'],
			'is_vefify' => $user_info['is_vefify'],
			'is_vip' => $user_info['is_vip'],
			'head_img' => UPLOAD_URL . $user_info['head_img'],
			'sex' => $user_info['sex'],
			'is_online' => $user_info['is_online'],
			'company' => $user_info['company'],
			'desc' => $user_info['desc'],
			'birthday' => date('Y-m-d', $user_info['birthday']),
		];

		//地址信息
		$user_address_model = D('UserAddress');

		$address_info = $user_address_model->get_one(['user_id' => $this->user_id]);
		if($address_info){
			$data['province'] = $address_info['province'];
			$data['city'] = $address_info['city'];
			$data['area'] = $address_info['area'];
			$data['address'] = $address_info['address'];
		}

		$this->result_return($data);
	}
}