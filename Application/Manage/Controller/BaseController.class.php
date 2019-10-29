<?php
namespace Manage\Controller;
use App\Controller\CommonController;
use Think\Controller;
class BaseController extends CommonController {

	protected $user_id;
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
		
		$is_login = $this->is_login();

		if(!$is_login){
			$this->redirect(U('Manage/login/login'));
			exit;
		}

		$this->user_id = $is_login['id'];
		$data['user_name'] = $is_login['user_name'];
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

	/**
	 * Thinkphp默认分页样式转Bootstrap分页样式
	 * @author H.W.H
	 * @param string $page_html tp默认输出的分页html代码
	 * @return string 新的分页html代码
	 */
	public function bootstrap_page_style($page_html){
		if ($page_html) {
			$page_show = str_replace('<div>','<nav><ul class="pagination">',$page_html);
			$page_show = str_replace('</div>','</ul></nav>',$page_show);
			$page_show = str_replace('<span class="current">','<li class="active"><a>',$page_show);
			$page_show = str_replace('</span>','</a></li>',$page_show);
			$page_show = str_replace(array('<a class="num"','<a class="prev"','<a class="next"','<a class="end"','<a class="first"'),'<li><a',$page_show);
			$page_show = str_replace('</a>','</a></li>',$page_show);
		}
		return $page_show;
	}

	/**
	 * 分页类的改写
	 * @author cuirj
	 * @date   2019/10/27 上午11:01
	 * @method get
	 *
	 * @param  int $count 总条数
	 * @param  int $page_size 每页多少条
	 * @return  array
	 */
	public function page_new($count, $page_size = 10){

		$Page = new \Think\Page($count,$page_size);
		$Page->lastSuffix = false;//最后一页不显示为总页数
		$Page->setConfig('header','<li class="disabled hwh-page-info"><a>共<em>%TOTAL_ROW%</em>条  <em>%NOW_PAGE%</em>/%TOTAL_PAGE%页</a></li>');
		$Page->setConfig('prev','上一页');
		$Page->setConfig('next','下一页');
		$Page->setConfig('last','末页');
		$Page->setConfig('first','首页');
		$Page->setConfig('theme','%HEADER% %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%');
		$page_show = $this->bootstrap_page_style($Page->show());//重点在这里
		return $page_show;
	}

	/**
	 * 判断是否登录
	 * @author cuirj
	 * @date   2019/10/29 上午1:38
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
	protected function is_login(){
		if(cookie('user_id') || session('user_id')){
			$user_id = cookie('user_id') ? cookie('user_id') : session('user_id');
			$admin_model = D('Admin');
			$admin_info = $admin_model->get_one(['id' => $user_id]);

			return $admin_info;
		}else{
			return false;
		}
	}
}