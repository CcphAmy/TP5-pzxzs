<?php
namespace app\index\model;

use \think\Model;
use think\Input;
use Fun;
class User extends Model
{
    /*登录验证*/
    public static function login($name, $password)
    {
        $where['stduentid'] = $name;
        $where['password']  = $password;

        $user = User::name('user')->where($where)->find();
        if ($user) {
            //echo json_encode($user);
            return $user;
        }else{
            return false;
        }
    }




    public static function allUser($stduentid,$password,$openid='',$ip='',$atime=''){
        
        if (empty($atime)) $atime = time();
        if (empty($ip)) $ip       = Fun::getIp();

        $findUser = User::get($stduentid);
        if (!empty($findUser)) $user = $findUser;
        else                   $user = new User;

        $user->stduentid = $stduentid;
        $user->password  = $password;
        $user->openid    = $openid;
        $user->ip        = $ip;
        $user->atime     = $atime;
        $user->save();

/*        $user = new User([
            'stduentid'=>$stduentid,
            'password'=>$password,
            'openid'=>$openid,
            'ip'=>$ip,
            'atime'=>$atime
        ]);
        $user->save();*/
    }

    public static function getAtime($dataId)
    {
        if (empty($dataId)) return false;

        $where   = 'stduentid';

        $reQuery = User::name('user')->where($where,$dataId)->find();

        return $reQuery ? $reQuery['atime'] : false;
    }

    public static function setAtime($dataId,$atime)
    {
        if (empty($dataId)) return false;
        $where   = 'stduentid';

        $findUser = User::get($dataId);
        if (!empty($findUser)) $user = $findUser;
        else return false;
        $user->atime     = $atime;
        return $user->save();

    }
/*

CREATE TABLE `xzs_info` (
    `stduentid` VARCHAR(20) NOT NULL COMMENT '学号',
    `password` VARCHAR(20) NOT NULL COMMENT '教务处密码',
    `openid` VARCHAR(20) NULL DEFAULT NULL COMMENT '微信',
    `name` VARCHAR(20) NULL DEFAULT NULL COMMENT '姓名',
    `gender` VARCHAR(5) NULL DEFAULT NULL COMMENT '性别',
    `idcard` VARCHAR(18) NULL DEFAULT NULL COMMENT '身份证',
    `nation` VARCHAR(20) NULL DEFAULT NULL COMMENT '民族',
    `department` VARCHAR(20) NULL DEFAULT NULL COMMENT '信息工程学院',
    `specialty` VARCHAR(20) NULL DEFAULT NULL COMMENT '专业',
    `grade` VARCHAR(20) NULL DEFAULT NULL COMMENT '年级',
    `class` VARCHAR(20) NULL DEFAULT NULL COMMENT '班级',
    PRIMARY KEY (`stduentid`),
    INDEX `password` (`password`),
    INDEX `openid` (`openid`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM
;
*/
    public static function allUserInfo($stduentid,$password,$openid='',$name='',$gender='',$idcard='',$nation='',$department='',$specialty='',$grade='',$class='')
    {
        if (empty($atime)) $atime = time();

        $user = new User;
        $user->name('info');

        $dataArr = array(
                    'stduentid' => $stduentid,
                    'password'  => $password,
                    'openid'    => $openid,
                    'name'      => $name,
                    'gender'    => $stduentid,
                    'idcard'    => $password,
                    'nation'    => $openid,
                    'specialty' => $name,
                    'grade'     => $stduentid,
                    'class'     => $password
        );

        if (User::name('info')->where('stduentid',$stduentid)->find()) $user->update($dataArr);
        else $user->data($dataArr,true)->save();


    }

} 