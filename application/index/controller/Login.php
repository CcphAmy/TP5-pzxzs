<?php
namespace app\index\controller;
use think\Controller;
use think\View;
use think\Session;
use app\index\model\User;

use HttpSvc;
use Base;
use Fun;
use AuthToken;

use WechatApi;
class Login extends Controller
{
    private $domainCookies = array();
    private $base;
    public function _initialize()
    {
        $this->base          = new Base();
        $this->domainCookies = array();//这个获取还是必要的.

        Session::prefix('think');//指定tink作用域

        if (Session::has('name','think') && Session::has('password','think') && Session::has('domainCookies','think')){
            $this->error("请勿重复登录.","location:/index/index/index");
        }

    }
    public function _empty($name)
    {
        return $this->error("非法操作.");
    }
    /**
     * [index 渲染index.html]
     * @return [type] [description]
     */
    public function index()
    {
        
        $res = $this->base->GetSessionAndLT();
        if ($res['code'] == '10004') {
            return $this->error("服务器故障,请稍后再试!.","location:/index/login/index");
        }
        $newHttp = new HttpSvc();
        $this->domainCookies['rz.gzpyp.edu.cn'] = $res['session'];
        return $this->fetch('index',[
                                     'imgSrc' => $this->base->getCaptcha($this->domainCookies),
                                     'rz'     => $this->domainCookies['rz.gzpyp.edu.cn'],
                                     'lt'     => $res['lt']]
                                    );
    }

    /**
     * [login 登录]
     * @return [type] [description]
     */
    public function login()
    {
        if (!input('?post.name') || !input('?post.password') || !input('?post.captcha')) {
            return $this->error("数据提交错误.","location:/index/login/index");
        }

        $newHttp   = new HttpSvc();
        $studentId = $newHttp->CTrim(input('request.name'));
        $password  = $newHttp->CTrim(input('request.password'));
        $captcha   = $newHttp->CTrim(input('request.captcha'));
        $rz        = $newHttp->CTrim(input('request.rz'));
        $lt        = $newHttp->CTrim(input('request.lt'));

        if (strlen($studentId) < 10 || empty($password) || empty($captcha) || empty($rz) || empty($lt)) {//1713440101
            return $this->error("信息填入错误.","location:/index/login/index");
        }
      //登录
        $this->domainCookies['rz.gzpyp.edu.cn'] = $rz;

        $resData = $this->base->login($this->domainCookies,$studentId,$password,$lt,$captcha);

        if ($resData == true) {
            $newHttp->CHttp("http://jw.gzpyp.edu.cn/detachPage.portal?.pn=p522_p523",'get',$this->domainCookies);
            $newHttp->CHttp("http://mh.gzpyp.edu.cn/pnull.portal?action=informationCenterAjax&.pen=pe261",'get',$this->domainCookies);
            
            Session::set('name',$studentId,'think');
            Session::set('password',$password,'think');
            Session::set('domainCookies',$this->domainCookies,'think');

            $token = new AuthToken();
            $atime = time();

            $token->createToken($studentId,$atime);
            echo Fun::SetLocalStorage('X-TOKEN',$token->getToken());

            
            $openid = '';
            $addU =  new User();

            $addU->allUser($studentId,$password,$openid,Fun::getIp(),$atime);

            // $addU->login($studentId,$password);
            
            // $token->createToken()->getToken();

            return $this->success('登录成功',"location:/index/index/index");
        }
        return $this->error("服务器故障,请稍后再试","location:/index/login/index");
    }
}