<?php
namespace app\index\controller;
use think\Controller;
use think\Session;
use Fun;
class logout extends Controller
{
    public function _initialize(){
        Session::prefix('think');//指定tink作用域
    }
    public function _empty($name)
    {
        return $this->error("非法操作.");
    }
    public function index()
    {
    	# code...
    }
    public function logout()
    {
    	Session::clear();
        Fun::removeLocalStorage('X_TOKEN');
    	return $this->success("注销成功.","location:/index/login/index");
    }
}