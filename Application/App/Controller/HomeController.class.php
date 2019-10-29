<?php
namespace App\Controller;

use Think\Controller;

class HomeController extends BaseController
{

	/**
	 * banner图
	 * @author cuirj
	 * @date   2019/9/27 下午12:48
	 * @url    /app/home/get_banner_list/
	 *
	 * @param  int param
	 * @method post
	 *
	 * @return  array
	 */
	public function get_banner_list()
	{
		$banner_model = D('Banner');
		$banner_list = $banner_model->get_list(['is_show' => 1], null, 'sort desc');

		foreach($banner_list as $k => $v){
			$banner_list[$k]['img'] = UPLOAD_URL . $v['img'];
		}
		$this->result_return($banner_list);
	}


	/**
	 * 方法说明
	 * @author cuirj
	 * @date   2019/10/29 下午4:08
	 * @url    /app/home/get_list/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function get_list(){
		//分不同情况
		//默认根据发布时间倒序
		//根据距离优先
		//根据人气最高-预约人数
		//根据分类
		//根据技能类型

		$params = I('get.');

		$type_id = $params['type_id'];
		$type = $params['type'];
		$latitude = $params['latitude'];
		$longitude = $params['longitude'];

		$demand_model = D('UserDemand');
		$skill_model = D('UserSkill');

		if($type_id){
			// 类型
			$result = $skill_model->get_skill_demand_by_type_id($type_id, $latitude, $longitude);
		}elseif($type == 'distance'){
			// 距离最近的
			$result = $skill_model->get_skill_demand_order_by_distance($latitude, $longitude);
		}

		// 对结果进行处理
		if($result){
			foreach($result as $k => $v){
				if($type_id){
					// 如果是技能类型查到的结果,则用php计算距离
					$result[$k]['distance'] = get_distance($v['longitude'], $v['latitude'], $longitude, $latitude);
				}

				//头像加上地址前缀
				$result[$k]['img'] = UPLOAD_URL . $v['img'];
				$result[$k]['head_img'] = UPLOAD_URL . $v['head_img'];
			}
		}

		$this->result_return($result);
	}

	/**
	 * 方法说明
	 * @author cuirj
	 * @date   2019/10/29 下午6:12
	 * @url    app/home/get_user_list_by_type_id/
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function get_user_list_by_type_id(){
		// 根据分类
		//根据年龄
		//根据性别筛选
		//根据技能类型

		$params = I('get.');

		$demand_model = D('UserDemand');
		$skill_model = D('UserSkill');
	}

	/**
	 * 上传当前用户的当前位置
	 * @author cuirj
	 * @date   2019/10/29 下午6:24
	 * @url    app/home/submit_location
	 * @method get
	 *
	 * @param  int param
	 *             return  array
	 */
	public function submit_location(){

		$get_param = file_get_contents('php://input');
		$params = json_decode($get_param, true);

		$user_id = $this->user_id;
		$lat = $params['latitude'];
		$long = $params['longitude'];

		$data = [
			'latitude' => $lat,
			'longitude' => $long,
			'user_id' => $user_id
		];
		$location_model = D('UserLocation');
		$location_info = $location_model->get_one(['user_id' => $user_id]);
		if($location_info){
			//更新
			$result = $location_model->update_data(['id' => $location_info['id']], $data);
		}else{
			// 新增
			$result = $location_model->insert_one($data);
		}

		$this->result_return(['result' => 1]);
	}
}