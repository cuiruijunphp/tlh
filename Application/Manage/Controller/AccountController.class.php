<?php
namespace Manage\Controller;
use Think\Controller;
class AccountController extends Controller {

    public function index(){
		$this->display('company/index');
    }
}