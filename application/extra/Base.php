 <?php 
	/**
	 * 
	 */
    
use app\index\model\User;
use think\Session;
class Base 
{
    const RZ_LT_URL      = 'http://rz.gzpyp.edu.cn/authserver/login';
    const RZ_CAPTCHA_URL = 'http://rz.gzpyp.edu.cn/authserver/captcha.html';
    const RZ_LOGIN_URL   = 'http://rz.gzpyp.edu.cn/authserver/login?service=http://mh.gzpyp.edu.cn/login.portal';
    
    const MH_INFO_URL    = 'http://mh.gzpyp.edu.cn/pnull.portal?action=informationCenterAjax&.pen=pe261';
    
    const JW_INFO_URL    = 'http://jw.gzpyp.edu.cn/detachPage.portal?.pn=p522_p523';
    const X_HEADER       = 'HTTP_X_TOKEN';

    private $tokenCont;
    private $x_token;
    private $isLogin;
    private $isRefresh;
    private $userid;

    function __construct()
    {

    }

    public function getInfo(&$domainCookies)
    { 
        $reText['result'] = 'error';
        $reText['msg']    = '其它地方登录';

        if ($this->valid()->isLogin) {
            if ($this->getUserId()) {

                $where['stduentid'] = $this->userid;
                $user = User::name('user')->where($where)->find();
                
                $res  = self::getPeopleInfo($domainCookies,true,$user['stduentid'],$user['password']);
                if (is_array($res)) return $res;
                
            }
            $reText['msg']  = '获取失败';
        }
        
        return $reText;
    }


    public function getMhInfo(&$domainCookies)
    {

        $reText['result'] = 'error';
        $reText['msg']    = '其它地方登录';

        if ($this->valid()->isLogin) {
            if ($this->getUserId()) {

                $whileCount = 0;
                while (empty($body)) {
                    $body = HttpSvc::CHttp(self::MH_INFO_URL,'get',$domainCookies)['body'];
                    $whileCount ++;
                    if ($whileCount == 5) {
                        return $this->error("数据获取失败");
                    }
                }

                if (strpos($body,'已获得学分')!==false) {

                    $jsonData = json_decode($body,true); 

                    if (isset($jsonData[1])) {
                        $reData = array();
                        foreach ($jsonData as $key => $value) {
                            if (isset($jsonData[$key]['description']) && isset($jsonData[$key]['title'])) {
                                    $reData[strtr($jsonData[$key]['title'],array('[ '=>'','] '=>'','目前已'=>'','在校'=>'','#e7675a'=>'#000'))] = strtr($jsonData[$key]['description'],array('，'=>'<br/>'));
                            }
                        }
                        if (count($reData>1)) {
                            Session::set('domainCookies',$domainCookies,'think');//更新一下 domainCookies
                            return $reData;
                        }else $reText['msg']  = '获取失败 1';
                    }else $reText['msg']  = '获取失败 2';
                }else{
                    $domainCookies['mh.gzpyp.edu.cn'] = '';
                    Session::set('domainCookies',$domainCookies,'think');
                    $reText['msg']  = '获取失败 3';
                } 
            }
            $reText['msg']  = '获取失败 0';
        }
        return $reText;





    }



    public function reLogin()
    {
        if ($this->valid()) {
            $userId = $this->getUserId();
            if ($userId) {
                $where['stduentid'] = $userId;
                $user               = User::name('user')->where($where)->find();
                if ($user) {

                    $res                  = Base::GetSessionAndLT();
                    $studentId            = $user['stduentid'];
                    $password             = $user['password'];
                    $temp                 = 'rz.gzpyp.edu.cn';
                    $domainCookies[$temp] = $res['session'];
                    $resData = Base::login($domainCookies,$studentId,$password,$res['lt']);
                    if ($resData === true) {
                        Session::set('domainCookies',$domainCookies,'think');
                        $this->domainCookies = $domainCookies;
                    }else{
                        var_dump($resData);
                    }
                    
                }
            }
            

        }
    }

    public function getUserId()
    {
        if (isset($this->userid))
            return $this->userid;
        else 
            return false;
    }

    public function getToken()
    {
        if (isset($this->x_token))
            return $this->x_token;
        else 
            return false;
    }

    public function getRefresh()
    {
        if (isset($this->isRefresh))
            return $this->isRefresh;
        else 
            return false;
    }

    public function valid($echoJSON = false)
    {
        if (empty($this->tokenCont)) $this->tokenCont = new AuthToken();
        if (empty($this->x_token))  $this->x_token = isset($_SERVER[self::X_HEADER]) ? $_SERVER[self::X_HEADER] : '';
        if (empty($this->x_token)) return false;

        $res =  $this->tokenCont->validateToken($this->x_token,$echoJSON);//不输出的话就不刷新TOKEN
        if ($res['result'] == 'refresh') $this->isRefresh = true;
        if (isset($res['info']['userid'])) $this->userid = $res['info']['userid'];
        if ($echoJSON) echo json_encode($res,JSON_UNESCAPED_UNICODE);
        $this->isLogin = $res['result'] == 'error' ? false : true;
        return $this;
    }
    /**
     * 获取自定义的header数据
     * $header = $this->get_all_headers();
     * echo json_encode($header);
     */
    public function get_all_headers(){

        // 忽略获取的header数据
        $ignore = array('host','accept','content-length','content-type');

        $headers = array();

        foreach($_SERVER as $key=>$value){
            if(substr($key, 0, 5)==='HTTP_'){
                $key = substr($key, 5);
                $key = str_replace('_', ' ', $key);
                $key = str_replace(' ', '-', $key);
                $key = strtolower($key);

                if(!in_array($key, $ignore)){
                    $headers[$key] = $value;
                }
            }
        }

        return $headers;

    }

	/**
	 * [login description]
	 * @param  [type] &$domainCookies [description]
	 * @param  [type] $studentId      [description]
	 * @param  [type] $password       [description]
	 * @param  [type] $lt             [description]
	 * @param  string $captcha        [description]
	 * @return [type]                 [description]
	 */
	public static function login(&$domainCookies,$studentId,$password,$lt,$captcha='')
	{

        $postParam = array(
                            'username'  => $studentId, 
                            'password'  => $password,
                            'lt'        => $lt,
                            'execution' => 'e1s1',
                            '_eventId'  => 'submit',
                            'rmShown'   => '1'
                            );

        if (!empty($captcha)) $postParam['captchaResponse']= $captcha;

        $res = HttpSvc::CHttp(self::RZ_LOGIN_URL,'post',$domainCookies,$postParam);

        if ($res['code'] == '200') {
            
            if (strpos($res['body'],'callback_err_login') !== false) {
            	$matches = self::getMidText($res['body'],"callback_err_login\">","<\/div>");
                if (isset($matches[1])) return $matches[1];
            }else{
                return true;
            }
        }
        return "服务器故障,请稍后再试.";
	}
    /**
     * [getCaptcha 获取验证码]
     * @param  [type] &$domainCookies [description]
     * @return [type]                 [description]
     */
    public function getCaptcha(&$domainCookies)
    {
        $res     = HttpSvc::CHttp(self::RZ_CAPTCHA_URL,'get',$domainCookies);
        $base_64 = base64_encode($res['body']);
        $imgSrc  = "data:image/jpeg;base64,{$base_64}";
        return $imgSrc;
    }
    /**
     * [GetSessionAndLT 获取登录的cookie和lt]
     */
    public static function GetSessionAndLT()
    {

        $body            = HttpSvc::httpReqUrl(self::RZ_LOGIN_URL,'get',$cookie);
        $loginJSESSIONID = $cookie;
        $matches         = self::getMidText($body['body'],"name=\\\"lt\\\" value=\\\"","\\\"");
        if (!empty($matches)) $loginLT = $matches[1];

        if (empty($loginJSESSIONID) || empty($loginLT)) {

            $data = array('code'=>'10004');

        }else{
            $data = array(
                'code'    =>'10000',
                'session' =>$loginJSESSIONID,
                'lt'      =>$loginLT
            );
        }
        return $data;
    }

    public static function getPeopleInfo(&$domainCookies,$reLogin=false,$name='',$password='')
    {
        //防止读取失败,最多重复 5 次.
        $whileCount = 0;
        while (empty($body)) { 
            $body = HttpSvc::CHttp(self::JW_INFO_URL,'get',$domainCookies)['body'];
            $whileCount ++;
            if ($whileCount == 5) {
                // return $this->error("数据获取失败 0.","location:/index/login/index");
                return false;
            }
        }

        if (strpos($body,'身份证号')!==false) {

            preg_match('~<table class="pa-main-table">([\\s\\S]*?)</table>~', $body, $matches); 

            if (isset($matches[1])) {

                preg_match_all('~<b>([\\s\\S]*?)&nbsp;~', $matches[1], $oldMatches); //取出所有odd

                if (isset($oldMatches[1]) && count($oldMatches[1]) >= 10) {

                    preg_match_all('~even">&nbsp;([\\s\\S]*?)<\/td>~', $matches[1], $evenMatches); 

                    if (isset($oldMatches[1]) && count($oldMatches[1]) == count($evenMatches[1])) {
                        $tempArr = array();
                        foreach ($oldMatches[1] as $key => $value) {
                            if (strpos($value,'身份证') === false) $tempArr [$value] =  $evenMatches[1][$key];
                        }
                    }
                    return $tempArr;

                }else return false;
            }else return false;
        }else{
            //var_dump($body);
            if (self::isLogin($body)){

                if ($reLogin) {
                    //清空数组
                    array_splice($domainCookies, 0, count($domainCookies));

                    $res                  = self::GetSessionAndLT();
                    $temp                 = 'rz.gzpyp.edu.cn';
                    $domainCookies[$temp] = $res['session'];
                    $studentId            = $name;
                    $password             = $password;

                    $resData = self::login($domainCookies,$studentId,$password,$res['lt']);

                    return self::getPeopleInfo($domainCookies,false);
                }
/*              重新登录
                $base           = new Base();
                $res            = $base->GetSessionAndLT();
                $temp           = 'rz.gzpyp.edu.cn';
                $tempArr[$temp] = $res['session'];
                $studentId      = Session::get('name','think');
                $password       = Session::get('password','think');

                $resData = $base->login($tempArr,$studentId,$password,$res['lt']);
                */

            }
            return false;
        } 

    }

    public static function isLogin($body)
    {
        return strpos($body,'统一身份认证') !== false ? true : false;
    }


    /**
     * 取中间文本
     */
    public static function getMidText($textData,$header,$tail)
    {
        preg_match("~$header(.*?)$tail~", $textData, $matches); 
        if (isset($matches[1])) {
            return $matches;
        }
        return array();
    }






}?>