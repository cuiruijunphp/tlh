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
		$banner_list = $banner_model->get_list(['is_show' => 1]);

		foreach($banner_list as $k => $v){
			$banner_list[$k]['img'] = UPLOAD_URL . $v['img'];
		}
		$this->result_return($banner_list);
	}
}