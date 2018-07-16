<?php 
/**
 * 个人自用的PHP的HTTP操作类,表示番某院网站重定向很坑爹
 * 问题越修越多...
 */
    class HttpSvc
    {
        const TIME_OUT=3;
        static  $ALL_CURL=array('post','get');
        /**
         * [CHttp cookie问题,再再再再次封装一下,再次表示番禺某职院的网页坑]
         * @param [type] $url            [description]
         * @param string $method         [get and post]
         * @param [type] &$domainCookies [array('domanin'=>'cookie')]
         * @param array  $param          [post param]
         */
        public static function CHttp($url,$method='get',&$domainCookies,$param=array())
        {
            //$url = "http://mh.gzpyp.edu.cn/login.portal";
            $domain = self::getDomain($url);
            if (!isset($domainCookies[$domain])) $domainCookies[$domain] = "";
            $res = self::httpReqUrl($url,$method,$reCookie,$param,$domainCookies[$domain],false,10);
            $domainCookies[$domain] = self::DistinctCookie($domainCookies[$domain],$reCookie);
            while ($res['code'] == '302') { //重定向
                if (strpos($res['header'], 'Location')!==false) {
                    $location = self::getLocationUrl($res['header']);
                    if (!empty($location)) {
                        //cookie处理
                        $domain = self::getDomain($location);
                        /*var_dump($location ."  ".$domain);*/
                        if (!isset($domainCookies[$domain])) $domainCookies[$domain] = "";
                        $consoleText="302重定向,访问域名为:".$domain ."  完全URL:".$location;
                        trace($consoleText);
                        $res = self::httpReqUrl($location,'get',$tempCookie,array(),$domainCookies[$domain],false);
                        $domainCookies[$domain] = self::DistinctCookie($domainCookies[$domain],$tempCookie);
                        // echo('<script>console.log("'.$consoleText.'");</script>');
                        $tempCookie ="";
                        /*var_dump($res);*/
                    }
                }
            }
            return $res;
        }
        /**
         * 自用HTTP操作类
         * @param  [type]  $url      [description]
         * @param  string  $method   [description]
         * @param  [type]  &$cookie  [description]
         * @param  array   $param    [description]
         * @param  string  $Tcookie  [description]
         * @param  boolean $Location [default:false]
         * @param  integer $timeout  [description]
         * @return [type]            [description]
         */
        public static function httpReqUrl($url,$method='get',&$cookie,$param=array(),$Tcookie="",$Location=false,$timeout=1)
        {
            /*var_dump("访问url:".$url);*/
            if(!in_array(strtolower($method),self::$ALL_CURL))
                return $url;
            if(!$url)
                return ;
/*            $urlarr = explode("?",$url,2);
            $url = $urlarr[0];
            if ($method='get') {
               if(isset($urlarr[1]) && !empty($urlarr[1])) {
                    parse_str($urlarr[1],$param);
                }
            }*/
            $res=self::HttpReqArr($url,$param,$timeout,strtolower($method),$Tcookie,null,$Location);
            if ($res['code'] == '200' || $res['code']=='302') {
                $cookie = $res['cookie'];
            }
            return $arrayName = array('code'=>$res['code'],'header' => $res['header'], 'body' => $res['data']);
        }
        /**
         * 封装curl
         * @param [type]  $url      [description]
         * @param array   $opts     [description]
         * @param integer $timeout  [description]
         * @param string  $mothod   [description]
         * @param string  $Tcookie  [description]
         * @param [type]  $proxy    [description]
         * @param boolean $Location [description]
         */
        public static function HttpReqArr($url,$opts=array(),$timeout=1,$mothod='post',$Tcookie="",$proxy=null,$Location=true)
        {
            $data=array();
            try{
                trace('####################HttpReqArr#########################');//调试
                trace($mothod.' url:'.$url);//调试
                $stime = microtime(true);
                $url   = trim($url);
                $ch    = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, true); 
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
                //ssl的
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:60.0) Gecko/20100101 Firefox/60.0");
/*                $header = array(  
                    'Content-Type:application/x-www-form-urlencoded',  
                );  
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header); */
                if (!empty($Tcookie)) {
                    /*var_dump("有提交cookies:".$Tcookie);*/
                    curl_setopt($ch, CURLOPT_COOKIE, $Tcookie);
                    trace('#'.$mothod.' cookie:'.$Tcookie);//调试
                }
                if($proxy)
                    curl_setopt($ch,CURLOPT_PROXY,$proxy);
                if(isset($opts) && count($opts)>0)
                {
                    $pstring='';
                    foreach($opts as $key => $val)
                        $pstring .= trim($key) . '=' . urlencode(trim($val)) . "&";
                    $pstring = substr($pstring,0,-1);
                    trace('#'.$mothod.' pstring:'.$pstring);//调试
                    if(strtolower($mothod)=='post') {
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $pstring);
                        curl_setopt($ch, CURLOPT_URL, $url);
                    }
                    else
                    {
                        curl_setopt($ch, CURLOPT_HTTPGET, true);
                        curl_setopt($ch, CURLOPT_URL, $url."?".$pstring);
                    }
                }
                else
                {
                    if(strtolower($mothod)=='post')
                        curl_setopt($ch, CURLOPT_POST, true);
                    else
                        curl_setopt($ch, CURLOPT_HTTPGET, true);
                    curl_setopt($ch, CURLOPT_URL, $url);
                }

                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                if (!$Location) {
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
                }
                $r = curl_exec($ch);
                // $reCookie =preg_match_all('/Set-Cookie:stest=(.*)/i', $r, $results);
                // var_dump($reCookie);
                $http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
                $etime=microtime(true);
                $usetime=$etime-$stime;
                // $logger->debug("[response] code: $http_code, usetime: $usetime, url:$url");
                $error = curl_error($ch);
                if($error){
                    //$toast ="code: $http_code, usetime: $usetime, callbackUrl:$url?$pstring method:$mothod  opt=".var_export($opts,true).'&proxy='.$proxy;
                }
            }catch(Exception $e) {
                //  $e->getMessage();
            } 
            $tempCookie="";
            if ($http_code == '200' || $http_code =='302') {
                $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $header = substr($r, 0, $headerSize);
                $body = substr($r, $headerSize);
                preg_match_all("/set\-cookie:([^\r\n]*)/i", $header, $matches); 
                if (isset($matches[1]) && count($matches[1]) > 0) {
                    $tempCookie = $matches[1][0];
                    for ($i=0; $i < count($matches[1])-1; $i++) { 
                        $tempCookie = self::DistinctCookie($tempCookie,$matches[1][$i+1]);
                    }
                    if (!empty($Tcookie)) {
                        $tempCookie = self::DistinctCookie($Tcookie,$tempCookie);
                    }
                }else{
                    $tempCookie = $Tcookie;
                }

                $r = self::RemoveBom($body);
            }else{
                $header="";
                $body="";
            }
            curl_close($ch);

            //$cookie = str_replace('Path=/;','' ,$tempCookie);
            /*var_dump("set-cookie(&):".$tempCookie);*/
            $data=array('code'=>$http_code,'header'=>$header,'cookie'=>$tempCookie,'data'=>$body);
            trace('-return '.$mothod.' Header:'.$header);//调试
            trace('-return '.$mothod.' Cookie:'.$tempCookie);//调试
            trace('------------------------END---------------------------');//调试
            return $data;
        }
        /**
         * 清除Bom
         * @param [type] $content [description]
         */
        public static function RemoveBom($content)
        {
            if(substr($content, 0,3) == pack("CCC",0xef,0xbb,0xbf)) {
                $content=substr($content, 3);
            }
            return $content;
        }
        public static function CTrim($str){
            $str = self::TrimLinkAndSpace($str);
            $newStr = mb_ereg_replace('^(　| )+', '', $str);
            return mb_ereg_replace('(　| )+$', '', $newStr); 
        }
        /**
         * 清除空格和换行
         * @param [type] $str [description]
         */
        public static function TrimLinkAndSpace($str){  
            $qian=array("/\s/","\t","\n","\r");
            return str_replace($qian, '', $str);    
        }
        public static function getDomain($url)
        {
            $arr = parse_url($url);
            /*var_dump($arr);*/
            if (isset($arr['host'])) {

                return $arr['host'];
            }else{
            return null;
            }
        }
        /**
         * 获取Location的URL
         * @param  [type] $header [description]
         * @return [type]         [description]
         */
        public static function getLocationUrl($header)
        {
            preg_match("~Location: (.*?)\n~", $header, $matches); 
            if (isset($matches[1])) {
                $location = self::TrimLinkAndSpace($matches[1]);
                return $location;
            }else{
            return null;
            }
        }
        /**
         * 取中间文本
         */
        public static function getMidText($textData,$header,$wei)
        {
            preg_match("~$header(.*?)$wei~", $textData, $matches); 
            if (isset($matches[1])) {
                return $matches;
            }
            return array();
        }
        /**
         * 合并cookie
         * @param [type] $cookie1 [description]
         * @param [type] $cookie2 [description]
         */
        public static function DistinctCookie($cookie1,$cookie2){
            trace("提交的cookie:".$cookie1 ."  返回的cookie:".$cookie2);
            if (empty(self::CTrim($cookie1))) {return $cookie2;}
            if (empty(self::CTrim($cookie2))) {return $cookie1;}
            $distinctExplodeArr =array();
            $cookie1 = self::CTrim($cookie1);
            $cookie2 = self::CTrim($cookie2);
            $distinctExplodeArr[]=explode(';',$cookie1);
            $distinctExplodeArr[]=explode(';',$cookie2);
            trace($distinctExplodeArr);
            //2018年6月19日16:47:24 修bug的时候来了  
            if (count($distinctExplodeArr)>1) {
                foreach ($distinctExplodeArr[1] as $key => $value) {//第二个(新)与第一个(旧)对比
                    if (strpos($value,"=")!==false) {//第二个
                        list($attribute, $listValue) = explode('=',$value);
                        $attribute =self::CTrim($attribute);
                        foreach ($distinctExplodeArr[0] as $tempKey => $tempValue) {
                            if (!empty($tempValue) && strpos($tempValue,"=")!==false) {
                                list($tempAttribute, $listTempValue) = explode('=',$tempValue);
                                $tempAttribute = self::CTrim($tempAttribute);
                               if (strcmp($tempAttribute,$attribute)==0) {
                                   $distinctExplodeArr[0][$key] = "";
                               }
                            }
                        }
                    }
                }
            }

            //遍历
            $reText="";
            foreach ($distinctExplodeArr as $key => $value) {
                foreach ($distinctExplodeArr[$key] as $tempKey => $tempValue) {
                    if (!empty(self::CTrim($tempValue))) {//妈诶,还有一个奇葩情况 == ""
                        if (strpos($tempValue,"Expires") === false && strpos($tempValue,"Path") === false && strpos($tempValue,"HttpOnly") === false && $tempValue!='""'  && $tempValue!='"') {
                            $reText = $reText . $tempValue . ";";
                        }
                    }
                }
            }
            trace("合成的cookie:".$reText);
            return $reText;
        }
    }
 ?>
