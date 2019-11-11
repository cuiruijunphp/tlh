<?php
namespace App\Controller;

use Think\Controller;

class BalanceController extends BaseController
{

	/**
	 * 我发布的需求列表
	 * @author cuirj
	 * @date   2019/9/27 下午12:48
	 * @url    /app/demand/get_my_demand_list/
	 *
	 * @param  int param
	 * @method post
	 *
	 * @return  array
	 */
	public function get_my_demand_list()
	{

	}

	/**
	 * 用户提现
	 * @date   2019/11/11 上午11:20
	 * @url    app/balance/withdraw
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	public function withdraw(){
		//先判断是否已经绑定了支付宝账号

	}
}