<?php
namespace Manage\Controller;
use App\Controller\CommonController;
use Think\Controller;
class BaseController extends CommonController {
	/**
	 * 接口登录验证
	 * @author cuirj
	 * @date   2019/9/27 下午12:48
	 *
	 * @param  int param
	 * @return  array
	 */
    public function __construct(){
    	parent::__construct();
//    	$menu_model = D('menu');
//		$data['menu'] = $menu_model->get_list();

		$data['menu'] = $this->menu();
		$this->assign($data);
    }

	protected function menu($type = 1) {

		$str = '';
		$icons = [
			'company' => 'fa-home',
			'course' => 'fa-graduation-cap',
			'question' => 'fa-book',
			'exam' => 'fa-desktop',
			'account' => 'fa-trophy',
		];

		$menu_model = D('menu');
		$data['menu'] = $menu_model->get_list();

		$menu = $menu_model->get_list();

		foreach ($menu as $items) {
			$icon = '';
			$current = '';
			$urilist = explode('/', $items['uri']);
			if ($urilist[1] == strtolower(CONTROLLER_NAME)) {
				$current = ' class="current-page"';
				isset($icons[$urilist[1]]) && $icon = $icons[$urilist[1]];
			}
			$str .= '<li' . $current . '><a href= ' . U(ucfirst($items['uri'])) . '><i class="fa ' . $icon . '"></i> ' . $items['name'] . ' </a></li>' . PHP_EOL;
		}

		return $str;
	}
}