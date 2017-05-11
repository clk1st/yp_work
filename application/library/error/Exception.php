<?php
class Error_Exception {

	/**
	 * 抛出异常
	 *
	 * @param string $code        	
	 * @param string $msg        	
	 * @throws Exception
	 */
	public static function throwException($code, $msg) {
		throw new Exception ( $msg, $code );
	}
}