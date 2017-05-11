<?php
class Convertor_Base {

	public function __construct() {
	}

	public function commonConvertor(array $result) {
		$data = array ();
		$data ['code'] = $result ['code'];
		if (isset ( $result ['code'] ) && ! $result ['code']) {
			$data ['data'] = $result ['data'];
		} else {
			$data ['code'] = empty ( $result ['code'] ) ? 1 : $result ['code'];
			$data ['msg'] = $result ['msg'];
		}
		return $data;
	}

	/**
	 * 用于update和insert的数据转换器
	 *
	 * @param array|bool $result
	 *        	当$result 为数组时 必须有id key-vaule 否则是否insert失败
	 * @return array | exception
	 */
	public function statusConvertor($result) {
		$data = array ();
		if (! $result || (is_array ( $result ) && ! $result ['id'])) {
			Error_Exception::throwException ( Enum_ErrorCode::ERROR_BUS_CONVERTOR_STATUS, '操作失败' );
		} elseif (is_array ( $result )) {
			$data ['id'] = $result ['id'];
		}
		return $data;
	}
}