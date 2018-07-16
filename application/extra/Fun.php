<?php 

class Fun
{
 	public static function getIp(){
	    $ip='未知IP';
	    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
	        return self::is_ip($_SERVER['HTTP_CLIENT_IP'])?$_SERVER['HTTP_CLIENT_IP']:$ip;
	    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
	        return self::is_ip($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$ip;
	    }else{
	        return self::is_ip($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:$ip;
	    }
	}

	public static function is_ip($str){
	    $ip=explode('.',$str);
	    for($i=0;$i<count($ip);$i++){ 
	        if($ip[$i]>255){ 
	            return false; 
	        } 
	    } 
	    return preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/',$str); 
	}
	/**
	 * [tbodyArr description]
	 * @param  [type] &$tempData [description]
	 * @param  [type] $data      [description]
	 * @return [type]            [count]
	 */
	public static function tbodyArr(&$tempData,$data,$header,$tail,$bool=false)
	{
        preg_match('~'.$header.'([\\s\\S]*?)'.$tail.'~', $data, $matches); 
        //数据处理
        $dataExp=array();
        $tempData = array();
        if (isset($matches[0])) {

            $matches[0] = str_replace('</td></tr></tbody>' ,'',$matches[0]);
            $matches[0] = str_replace('</tr>' ,'||',$matches[0]);
            $matches[0] = str_replace('</td>' ,'&&',$matches[0]);
            $matches[0] = self::TrimLinkAndSpace(strip_tags($matches[0]));

            if ($bool) $matches[0] = str_replace(' ','',self::CTrim($matches[0]));

            $dataExp = explode("&&||",$matches[0]);

            if (count($dataExp)>0) {
                foreach ($dataExp as $key => $value) {
                    if (strpos($value,'&&')!==false) {
                        $tempData[] = explode("&&",$value);
                    }
                }
                return count($dataExp);
            }
        }
	}

	public static function SetLocalStorage($item,$value){

		$scrpit =	'window.localStorage'.
					'.setItem("'. $item .'","' . $value .'");';
		return self::echoScript($scrpit);
	}
	public static function removeLocalStorage($item){

		$scrpit =	'window.localStorage.removeItem("'.$item.'")';
		return self::echoScript($scrpit);
	}
	public static function echoScript($scrpit){
		return "<script type='text/javascript'>".$scrpit."</script>" ;
	}
	public static function cutstr_html($string)
	{
	    $string = strip_tags($string);
	    $string = preg_replace ('/ |　/is', '', $string);
	    $string = preg_replace ('/&nbsp;/is', '', $string);

	    preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $string, $t_string);
	    return $string;
	}
    public static function CTrim($str){

		$newStr = mb_ereg_replace('^(　| )+', '', $str);
		$str    = mb_ereg_replace('(　| )+$', '', $newStr); 

        return self::TrimLinkAndSpace($str);
    }
        /**
     * 清除空格和换行
     * @param [type] $str [description]
     */
    public static function TrimLinkAndSpace($str){  
        $qian=array("/\s/","\t","\n","\r");
        return str_replace($qian, '', $str);
    }
    /**
     * 取中间文本
     */
    public static function midText($textData,$header,$tail)
    {	
        preg_match('~'.$header.'[\w\W]+'.$tail .'~', $textData, $matches); 
        if (isset($matches[0])) {
            return $matches[0];
        }
        return false;
    }    

	/*
	 * php输入毫秒部分的代码
	 * */
	 public static function msectime() {
	    list($msec, $sec) = explode(' ', microtime());
	    $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
	    return $msectime;
	 }








}
 ?>