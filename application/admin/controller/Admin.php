<?php 
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\Validate;

use app\admin\model\Admin as dataAdmin;
/**
 * 
 */
class Admin extends Controller
{

	public function index()
	{
		return $this->fetch('index');
	}

	public function login()
	{
		$request = Request::instance();
		$getData = $request->param();
		// post param name | password

        $validate = new Validate([
			'name'     =>'require|max:25',
			'password' =>'require|max:25',
			'__token__'=>'require|token'
        ]);

        if(!$validate->check($getData)){ //$validate->getError();
            return $this->error("请勿刷新登录提交页面.");
        }

		$login  = dataAdmin::login($getData['name'],$getData['password']);
		$reInfo = array('uid'  => $login['uid'], 'user' => $login['user']);
		if ($login) return $this->fetch('admin',$reInfo);
		return $this->error("账号或密码错误.");
	}

}

 ?>