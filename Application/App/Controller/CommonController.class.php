<?php
namespace App\Controller;
use Think\Controller;
class CommonController extends Controller {

	/**
	 * 接口通用返回
	 * @author cuirj
	 * @date   2019/9/27 下午12:48
	 *
	 * @param  int param
	 * @return  array
	 */
    public function result_return($data, $code = 200, $message = ''){

    	$return_data = [
			'code' => $code,
			'message' => $message,
			'data' => $data
		];

    	echo json_encode($return_data);
		exit;
    }
}