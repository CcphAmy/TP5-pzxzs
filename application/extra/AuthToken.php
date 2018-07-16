<?php 
use Gamegos\JWT\Token;
use Gamegos\JWT\Encoder;
use Gamegos\JWT\Validator;
use Gamegos\JWT\Exception\JWTException;

use app\index\model\User;
/**
 * 
 */
class AuthToken
{
	
	const JW_TOKEN_KEY         = '1204621F01979E148820E3A9101348C132A25614C5C6CB5127306';
	const JW_ALG               = 'HS256';
	
	private $HTTP_HEADER_TOKEN = 'X_TOKEN';

	private $token;
	private $encoder;
	private $headerToken;

	function __construct()
	{
		$this->headerToken = isset($_SERVER[$this->HTTP_HEADER_TOKEN]) ? $_SERVER[$this->HTTP_HEADER_TOKEN] : '';
	}


//有一个跨域的问题 header头设置

	public function createToken($userid,$atime)
	{
		if (empty($userid) || empty($atime)) return false;

		$token   = new Token();
		$encoder = new Encoder();

		$nowtime = time();

		$token->setClaim('iss', 'https://www.pzxzs.cn');
		$token->setClaim('sub', 'https://www.pzxzs.cn');
		$token->setClaim('aud', 'pzxzs');
		$token->setClaim('iat', $nowtime);
		$token->setClaim('nbf', $nowtime + 10);
		$token->setClaim('exp', $nowtime + 60*365);
		$token->setClaim('data',array('userid' => $userid,'atime'=>$atime));

		$encoder->encode($token, self::JW_TOKEN_KEY, self::JW_ALG);
		$this->token  = $token->getJWT();
		return $this;
	}

	public function getToken()
	{
		if (isset($this->token))
			return $this->token;
		else 
			return false;
	}



	public function validateToken($jwt_token='',$refresh = false)
	{
			$res['result']='error';

			if (!empty($jwt_token)) $this->headerToken = $jwt_token;

		    if (empty($this->headerToken)) {
		        $res['msg'] = 'token不存在';
		        // echo json_encode($res,JSON_UNESCAPED_SLASHES);
		        return $res;
		    }
			try {
				$validator = new Validator();

				$token    = $validator->validate($this->headerToken, self::JW_TOKEN_KEY);
				$userInfo = $token->getClaim('data');

				//校验数据库是否有此人
				$reAtime  = User::getAtime($userInfo['userid'],0);
				//校验Token中的atime字段
				if (!$reAtime || ($reAtime != $userInfo['atime'])) {
					$res['msg']     = '已被其它终端登录';
					/*
					$res['reAtime'] = $reAtime;
					$res['atime']   = $userInfo['atime'];
					*/
					// echo json_encode($res,JSON_UNESCAPED_SLASHES);
					return $res;
				}

			    if ($token->getExpirationTime() < time() || ($userInfo['atime'] + 600) < time()) {

			    	if ($refresh) {
				    	$nowTime = time();
				    	$this->createToken($userInfo['userid'],$nowTime);
				    	User::setAtime($userInfo['userid'],$nowTime);

				    	if ($this->token) {
							$res['result']   = 'refresh';
							$res['newToken'] = $this->token;
							$res['info']     = $userInfo;
				    	}else{
							$res['result'] = 'error';
							$res['msg']    = 'Token刷新失败';
				    	}
			    	}else $res['msg'] = '身份认证失败';

			    } else {
					$res['result'] = 'success';
					$res['info']   = $userInfo;
			    }

			} catch (JWTException $e) {
				$res['msg'] =  $e->getMessage();
			    // printf("Invalid Token:\n  %s\n", $e`->getMessage());
			    //var_dump($e->getToken());
			}
			// echo json_encode($res,JSON_UNESCAPED_SLASHES);
			return $res;
	}



}
 ?>