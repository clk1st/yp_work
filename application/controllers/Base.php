<?php
abstract class BaseController extends Yaf_Controller_Abstract {

	protected $baseParam;

	public function init() {
		$this->baseParam = $this->getRequest ()->getParam ( "paramList" );
		$controllerName = $this->getRequest ()->getControllerName ();
		$actionName = $this->getRequest ()->getActionName ();
		if ($controllerName != "Error" && $actionName != "error") {
			$requestPath = strtolower ( "{$controllerName}_{$actionName}" );
			if ($requestPath) {
				Log_File::writeLog ( $requestPath, "success", "", $this->baseParam );
			}
		}
	}

	/**
	 * 接口访问成功
	 *
	 * @param array $data
	 *        	成功返回的数据
	 * @return string
	 */
	public function echoSuccessData($data = array()) {
		if (! is_array ( $data ) && ! is_object ( $data )) {
			$data = array ($data );
		}
		$this->echoAndExit ( 0, "success", $data );
	}

	public function echoJson($data) {
		header ( "Content-type:application/json;charset=utf8" );
		$response = $this->getResponse ();
		$response->setBody ( json_encode ( $data ) );
	}

	/**
	 * 接口访问输出
	 *
	 * @param int $code
	 *        	状态代码
	 * @param string $msg
	 *        	访问结果信息
	 * @param array $data
	 *        	成功返回数据
	 * @param array $debugInfo
	 *        	错误调试信息
	 */
	public function echoAndExit($code, $msg, $data, $debugInfo = null) {
		@header ( "Content-type:application/json" );
		$data = $this->clearNullNew ( $data );
		if (is_null ( $data ) && ! is_numeric ( $data )) {
			$data = array ();
		}
		$echoList = array ('code' => $code,'msg' => $msg,'data' => $data );
		$sysConfig = Yaf_Registry::get ( 'sysConfig' );
		if ($sysConfig->api->debug) {
			$echoList ['debugInfo'] = is_null ( $debugInfo ) ? ( object ) array () : $debugInfo;
		}
		$this->getResponse ()->setBody ( json_encode ( $echoList ) );
		if ($code != 0) {
			$paramList = $this->getRequest ()->getParam ( "paramList" );
			$requestPath = 'errlog';
			Log_File::writeLog ( $requestPath, json_encode ( $echoList ), "", $paramList );
		}
	}
}
?>
