<?php
class Interceptor_Config {

	private static $config = array ('User,' => array ('Interceptor_ParamAuth' ) );

	/**
	 * 获取拦截器配置
	 *
	 * @return array
	 */
	public static function getConfig() {
		return self::$config;
	}
}
