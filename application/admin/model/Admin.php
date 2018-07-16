<?php
namespace app\admin\model;

use \think\Model;
use think\Input;
use Fun;
class Admin extends Model
{
    const PASSKEY = 'hwywxhnqnrwhm';

    public static function login($uidOrName, $password)
    {
        $pass              = substr(md5($password.self::PASSKEY),0 ,20);

        $whereUid['uid']   = $uidOrName;
        $whereUid['pass']  = $pass;
        //
        $whereName['user'] = $uidOrName;
        $whereName['pass'] = $pass;
        

        $user = Admin::name('admin')->where($whereUid)->find();
        if ($user) return $user;
        $user = Admin::name('admin')->where($whereName)->find();
        if ($user) return $user;

        return false;
    }

    public static function addAdmin($uid,$user,$pass,$type=0,$off=0,$openid='',$ip='',$atime=''){
        
        if (empty($atime)) $atime = time();
        if (empty($ip)) $ip       = Fun::getIp();

        $findUser = Admin::get($uid);
        if (!empty($findUser)) return false;
        else                   $contr = new Admin;

        $contr->name('admin');

        $contr->uid    = $uid;
        $contr->user   = $user;
        $contr->pass   = substr(md5($pass.self::PASSKEY),0 ,20);
        $contr->type   = $type;
        $contr->off    = $off;
        $contr->openid = $openid;
        $contr->ip     = $ip;
        $contr->atime  = $atime;

        return $contr->save();
    }


} 