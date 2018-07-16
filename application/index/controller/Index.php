<?php
namespace app\index\controller;
use think\Controller;
use think\Session;
use app\index\model\User;

use HttpSvc;
use Base;

class Index extends Controller
{
    private $domainCookies = array();
    private $Base;
    private $isAuthToken;
    public function _initialize()
    {
        //验证是否为登录状态
        $this->base = new Base();

        Session::prefix('think');//指定tink作用域
        self::hasInfo();

    }
    public function _empty($name)
    {
        return $this->error("非法操作.");
    }
    /**
     * [hasInfo 检查一下设定并检测是否能用]
     * @return boolean [description]
     * 设置的seession必须有以下三个
     *       Session::set('name',$studentId,'think');
     *       Session::set('password',$password,'think');
     *       Session::set('domainCookies',$domainCookies,'think');
     * Rem:时间充足下写存储数据库.
     */
    public function hasInfo()
    {
        if (!Session::has('domainCookies','think')) {
            Session::clear('think');
            return false;
            // return $this->error("暂未登录!","location:/index/login/index");
        }
        $this->domainCookies = Session::get('domainCookies','think');
        return true;
    }
    public function reValid()
    {
        if ($this->base->valid(true)) {
            if (!$this->hasInfo()) $this->base->reLogin();
            
        }
    }

    public function getInfo()
    {
        $reText['result'] = 'error';
        $reText['msg']    = '请重新登录';
        if (!$this->hasInfo()){
            echo json_encode($reText,JSON_UNESCAPED_UNICODE);
            return;
        }

        $res = $this->base->getInfo($this->domainCookies);
        if (is_array($res)) {
            echo json_encode($res,JSON_UNESCAPED_UNICODE);
            return;
        }else{
        //var_dump($this->domainCookies);
        } $reText['msg'] = '其它地方登录';
        echo json_encode($reText,JSON_UNESCAPED_UNICODE);
    }

    public function index(){
        return view('index');
    }

    public function getUrl($url='www.baidu.com')
    {
    	$newHttp = new HttpSvc();
    	var_dump($newHttp->httpReqUrl($url)) ;
    	//get test
    }
}	
