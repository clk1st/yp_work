<?php
class Interceptor_ParamAuth extends \Interceptor_Base {

	/**
	 * (non-PHPdoc) @see Interceptor_Base::before()
	 */
	public function before(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
		$sysConfig = Yaf_Registry::get ( 'sysConfig' );
		$debugInfo = array ();
		$paramType = strtolower ( $request->getQuery ( "paramType" ) );
		if ($paramType == "get") {
			$paramList = $request->getQuery ();
			unset ( $paramList ['paramType'] );
		} elseif ($paramType == 'json') {
			$paramList = json_decode ( file_get_contents ( "php://input" ), true );
		} else {
			$paramList = $request->getPost ();
		}
		$timestamp = $paramList ["timestamp"];
		$paramSign = $paramList ['sign'];
		unset ( $paramList ['sign'] );
		$sign = Auth_Login::genSign ( $paramList );
		if ($sysConfig->api->checkSign) {
			if (empty ( $timestamp )) {
				Error_Exception::throwException ( Enum_ErrorCode::ERROR_BUS_PARAMAUTH_TIME_NULL, "未检测到时间戳" );
			}
			if ($timestamp < time () - 60 * 5) {
				Error_Exception::throwException ( Enum_ErrorCode::ERROR_BUS_PARAMAUTH_TIME_EXPIRE, "时间戳过期" );
			}
			if ($sign !== $paramSign) {
				Error_Exception::throwException ( Enum_ErrorCode::ERROR_BUS_PARAMAUTH_SIGN_CHECK, "验证戳校验错误" );
			}
		}
		unset ( $paramList ['timestamp'] );
		$request->setParam ( "paramList", $paramList );
	}

	/**
	 * (non-PHPdoc) @see Interceptor_Base::after()
	 */
	public function after(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
	}

	private function getUserAgent() {
		return isset ( $_SERVER ["HTTP_USER_AGENT"] ) ? $_SERVER ["HTTP_USER_AGENT"] : "";
	}

	private function isSpider() {
		$userAgent = $this->getUserAgent ();
		if (empty ( $userAgent )) {
			return false;
		}
		$spiderAgents = array ("QunarBot","Mediapartners","Yahoo","AdsBot","LWP","Sogou","curl","bingbot","lwp-trivial","HuaweiSymantecSpider","msnbot","ezooms",'Sosospider','Googlebot','Mediapartners-Google','YodaoBot','Baiduspider' );
		foreach ( $spiderAgents as $spiderAgent ) {
			if (stripos ( $userAgent, $spiderAgent ) !== false)
				return true;
		}
		return false;
	}
}
?>
