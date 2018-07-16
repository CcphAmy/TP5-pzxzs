<?php
namespace app\index\controller;
use think\Controller;
use think\Session;
use think\Validate;
use HttpSvc;
use Fun;
use Base;

class Today extends Controller
{
    private $domainCookies = array();
    public function _initialize()
    {
        Session::prefix('think');//指定tink作用域
        self::hasInfo();
    }
    public function _empty($name)
    {
        return $this->error("非法操作.");
    }
    public function hasInfo()
    {
        if (!Session::has('name','think') || !Session::has('password','think') || !Session::has('domainCookies','think')) {
            Session::clear('think');
            return $this->error("暂未登录!","location:/index/login/index");
        }
        $this->domainCookies=Session::get('domainCookies','think');
    }

    public function getInfo()
    {
        $base = new Base();
        echo json_encode($base->getMhInfo($this->domainCookies),JSON_UNESCAPED_UNICODE);
        return;
    }

    public function index()
    {
        $newHttp = new HttpSvc();
        $tableTrTd ="";
        //教务处网站
        $whileCount = 0;
        while (empty($body)) {
            $body = $newHttp->CHttp("http://mh.gzpyp.edu.cn/pnull.portal?action=informationCenterAjax&.pen=pe261",'get',$this->domainCookies)['body'];
            trace($body);
            $whileCount ++;
            if ($whileCount == 5) {
                // Session::clear('think');
                return $this->error("数据获取失败 0.");
            }
        }

        if (strpos($body,'已获得学分')!==false) {
            $jsonData = json_decode($body,true); 
            if (isset($jsonData[1])) {
                trace($jsonData);
                foreach ($jsonData as $key => $value) {
                    if (isset($jsonData[$key]['description']) && isset($jsonData[$key]['title'])) {
                            $tableTrTd = $tableTrTd . "<tr>";
                            $tableTrTd = $tableTrTd ."<td class='trwidth'>".strtr($jsonData[$key]['title'],array('[ '=>'','] '=>'','目前已'=>'','在校'=>'','#e7675a'=>'#000')) ."</td><td>". strtr($jsonData[$key]['description'],array('，'=>'<br/>')) ."</td>";
                            $tableTrTd = $tableTrTd . "</tr>";
                    }
                }
                if (isset($tableTrTd)) {
                    Session::set('domainCookies',$this->domainCookies,'think');//更新一下 domainCookies
                }else return $this->error("数据获取失败 1 .");
            }else return $this->error("数据获取失败 2.");


        }else{

            $this->domainCookies['mh.gzpyp.edu.cn'] = '';
            
            // var_dump($newHttp->CHttp("http://mh.gzpyp.edu.cn/login.portal",'get', $this->domainCookies));
            Session::set('domainCookies',$this->domainCookies,'think');

            return $this->error("请重新刷新.");
        } 



        return view('index',['tableTrTd'=>$tableTrTd]);
    }
/*      
        $newHttp->CHttp("http://mh.gzpyp.edu.cn/login.portal",'get',$domainCookies);
        $body = $newHttp->CHttp("http://mh.gzpyp.edu.cn/alone.portal?.pen=personalLibrary",'get',$domainCookies)['body'];
        preg_match_all("~<ul>([\\s\\S]*?)</ul>~", $body, $matches); 
        var_dump($matches[1]);

        
        //查询剩余余额
        //局域网 
        var_dump($newHttp->CHttp("http://192.168.79.102:9090/smart_web_online/ajax/login/sso",'get',$domainCookies));
        var_dump($domainCookies);
        var_dump($newHttp->CHttp("http://192.168.79.102:9090/smart_web_online/ajax/card/list.json",'post',$domainCookies));
*/

    public function find($name='')
    {
        $newHttp = new HttpSvc();
        if ($name=='') return $this->fetch('find',['headerStr'=>'','bodyStr'=>'']);
        $body = $newHttp->CHttp("http://mh.gzpyp.edu.cn/authorizeUsers.portal",'post',$this->domainCookies,array('limit' => 20,'term'=>urldecode($name)))['body'];
        $jsonData = json_decode($body,true); 
        $bodyStr ="";
        if (isset($jsonData)) {
            if (isset($jsonData['recordCount'])) {
                if ($jsonData['recordCount']>0) {
                    if(isset($jsonData['principals'])){
                        $bodyStr = "<div>";
                        foreach ($jsonData['principals'] as $key => $value) {
                            $description =isset($jsonData['principals'][$key]['description'])?'Description, '.$jsonData['principals'][$key]['description'].'<br/>':'';
                            $bodyStr = $bodyStr . '<address class="address"><strong>'.$jsonData['principals'][$key]['name'].'</strong><br />Metier, '.$jsonData['principals'][$key]['metier'].'<br />'.$description.'School ID , '.$jsonData['principals'][$key]['id'].'</address>';
                        }
                        $bodyStr = $bodyStr .'</div><br/><div class="clear" /><font style="padding-left:20px;">共找到: '.$jsonData['recordCount'].' 条记录,暂只显示 20 条记录.</font>';
                        Session::set('domainCookies',$this->domainCookies,'think');//更新一下 domainCookies
                    }
                }
                return $this->fetch('find',['headerStr'=>$name,'bodyStr'=>$bodyStr]);
            }
        }
    }
    public function mark($year='0',$term='0')
    {
        $data = array('year' => $year,'term'=>$term);

        $validate = new Validate([
            'year' =>'length:4',
            'term'=>'in:1,2'
        ]);

        if(!$validate->check($data)){
            return $this->fetch('course',['courseData'=>$validate->getError()]);
        }
        $newHttp = new HttpSvc();
        $postDataP ='?.pn=p7507_p223';
        $body = $newHttp->CHttp("http://jw.gzpyp.edu.cn/detachPage.portal?.pn=p7507_p223",'get',$this->domainCookies);
        //需要检查是否已经登录
        if (strpos($body['body'],'正在加载该栏目') !== false) {
            $postDataP =  $newHttp->getMidText($body['body'],"url='","&")[1];
        }
        //http://jw.gzpyp.edu.cn/detachPage.portal?.p=Znxjb20ud2lzY29tLnBvcnRhbC5zaXRlLnYyLmltcGwuRnJhZ21lbnRXaW5kb3d8ZjIwNnx2aWV3fG5vcm1hbHxhY3Rpb249eHNncmNqY3hrdmlldw__
        //
        //http://jw.gzpyp.edu.cn/pnull.portal?.p=Znxjb20ud2lzY29tLnBvcnRhbC5zaXRlLnYyLmltcGwuRnJhZ21lbnRXaW5kb3d8ZjIwNnx2aWV3fG5vcm1hbHw_&.nctp=true&.ll=true&.random=201807141922239323
        $res = $newHttp->CHttp("http://jw.gzpyp.edu.cn/detachPage.portal".$postDataP."&.nctp=true&.ll=true&.random=201807142010596810",'get',$this->domainCookies,array("newSearch"=>"true","xndm"=>$year,"xqdm"=>$term));
        if (strpos($body['body'],'正在加载该栏目') !== false) {

            $url      = $newHttp->getMidText($res['body'],"url='","'")[1];
            $body     = $newHttp->CHttp("http://jw.gzpyp.edu.cn/pnull.portal".$url,'get',$this->domainCookies);

            $data     = array();
            $tempData = array('code' => '10004','count' => '0');
            $count    = Fun::tbodyArr($data,$body['body'],'<tbody>','</tbody>',true);

            if ($count > 0) {
                $tempData['data']  = $data;
                $tempData['code']  = '10000';
                $tempData['count'] = $count;

            $data     = array();
            $count    = Fun::tbodyArr($data,$body['body'],'<table class="portlet-table">','</table>',true);
                if ($count > 0) {
                    $tempData['extend']  = $data;
                }

            }


            echo json_encode($tempData,JSON_UNESCAPED_UNICODE);
            exit();
        }

    }
    /**
     * [course 课程读取]
     * @param  [type] $year [description]
     * @param  [type] $term [description]
     * @return [type]       [description]
     */
    public function course($year='0',$term='0')
    {
        $data = array('year' => $year,'term'=>$term);

        $validate = new Validate([
            'year' =>'length:4',
            'term'=>'in:1,2'
        ]);

        if(!$validate->check($data)){
            return $this->fetch('course',['courseData'=>$validate->getError()]);
        }
        $newHttp = new HttpSvc();

        $body = $newHttp->CHttp("http://jw.gzpyp.edu.cn/detachPage.portal?.pn=p6982_p6983_p7350",'get',$this->domainCookies);
        if (strpos($body['body'],'表暂未开放查询') !== false) {
            $matches = $newHttp->getMidText($body['body'],"hxsbh_jwxsgrkbcx\" value=\"","\">");
            if (isset($matches[1])) {
                $xsbh = $matches[1];
                //来个post
                $res = $newHttp->CHttp("http://jw.gzpyp.edu.cn/pnull.portal?.pen=jwxsgrkbcx&.f=jwxsgrkbcx&.pmn=view&action=getkfrq",'post',$this->domainCookies,array('xndm'=>$year,"xqdm"=>$term));
                if ($res['body'] == 'true') {

                    $res = $newHttp->CHttp("http://jw.gzpyp.edu.cn/pnull.portal?.f=jwxsgrkbcx&.pmn=view&action=report&xn=".$year."&xq=".$term."&xsbh=". $xsbh ."&saveAsName",'get',$this->domainCookies);

                    //获取总共有多少页
                    $getPageNum = $newHttp->getMidText($res['body'],"共","页")[1];
                    if (empty($getPageNum)) echo "页数获取失败";
/*                    //取出 .p
                    $getPageNum = $newHttp->getMidText($res['body'],"value = '","'");
                       if (empty($getPageNum)) echo ".p获取失败";*/
                    $tableData  = '<script src="https://cdn.bootcss.com/jquery/3.3.1/jquery.min.js"></script>';
                    $tableData .= "<table id='father'>";
                    for ($nowPage=1; $nowPage <= $getPageNum; $nowPage++) {
                        if ($res['code']!= 200) break;
                        preg_match('~</colgroup>([\\s\\S]*?)</table>~', $res['body'], $matches); 
                        if (isset($matches[1])) {
                            $tableData .= $matches[1];//第一页表格
                        }
                        if ($nowPage < $getPageNum) {
                            $nextPage = $nowPage + 1;
                            $res = $newHttp->CHttp("http://jw.gzpyp.edu.cn/pnull.portal?.f=jwxsgrkbcx&.pmn=view&action=report&xn=".$year."&xq=".$term."&xsbh=". $xsbh ."&saveAsName" ."&report_jwxsgrkbcx_currPage=".$nextPage,'get',$this->domainCookies);
                        }
                    }

                    $tableData .= "</table>";
                    echo $tableData;

                    //
                    echo '<br/>';
                    echo strip_tags($tableData);
                }

            }
        }
    }    
    /**
     * [course 课程读取]
     * @param  [type] $year [description]
     * @param  [type] $term [description]
     * @return [type]       [description]
     */
    public function changeCourse()
    {
        $newHttp = new HttpSvc();
        //查询调课
        $body = $newHttp->CHttp("http://jw.gzpyp.edu.cn/detachPage.portal?.pn=p6982_p6983_p6984",'get',$this->domainCookies)['body'];
        // preg_match('~<table class=\"portlet-table\" id=\"queryGridxskbtz\">([\\s\\S]*?)</table>~', $body, $matches); 
        preg_match('~<tbody>[\w\W]+</td></tr></tbody>~', $body, $matches);
        $dataExp=array();
        $tempData = array('code' => '10004','count' => '0');
        if (isset($matches[0])) {
            $matches[0] = str_replace('</td></tr></tbody>' ,'',$matches[0]);
            $matches[0] = str_replace('</tr>' ,'||',$matches[0]);
            $matches[0] = str_replace('</td>' ,'&&',$matches[0]);
            $matches[0] = $newHttp->TrimLinkAndSpace(strip_tags($matches[0]));
            $dataExp = explode("&&||",$matches[0]);
            if (count($dataExp)>0) {
                foreach ($dataExp as $key => $value) {
                    if (strpos($value,'&&')!==false) {
                        $tempData['data'][] = explode("&&",$value);
                    }
                }
                $tempData['code'] = '10000';
                $tempData['count'] = count($dataExp);
            }
        }
        echo json_encode($tempData,JSON_UNESCAPED_UNICODE);
        return;
    }
    public function mp3()
    {
        //歌曲赋值就暂时不要了,内定一首歌先
        return view('mp3');
    }
    public function calendar()
    {
        //预留做校历,现做个
        return $this->fetch('calendar',['schoolStarts'=>'2018/03/05']);
    }
    public function test()
    {
        echo "<pre>";
        var_dump($this->domainCookies);
        echo "</pre>";
    }
}