<?php

class BaseModel {

    public function __construct() {}

    /**
     * 抛出异常
     *
     * @param string $name            
     * @param string $code            
     * @throws Exception
     */
    protected function throwException($name, $code) {
        throw new Exception($name, $code);
    }

    /**
     * 检验page
     *
     * @param int $page            
     * @return int
     */
    public function checkPage($page) {
        $page = intval($page);
        return ($page < 1 || ! $page) ? 1 : $page;
    }

    /**
     * 检验limit
     *
     * @param int $limit            
     * @param int $number            
     * @return int
     */
    public function checkLimit($limit, $number = 4) {
        $limit = intval($limit);
        $number = intval($number);
        return ($limit <= 0 || $limit > 9999) ? $number : $limit;
    }

    /**
     * 为空验证
     *
     *
     * @param mix $value            
     * @param string $propertyName            
     * @param string $errorMsg            
     * @throws Exception
     * @return mix
     */
    protected function validateNull($value, $propertyName, $errorMsg = '') {
        if (! $value) {
            $errorMsg = $errorMsg ? $errorMsg : "{$propertyName}参数错误";
            throw new Exception($errorMsg, 2);
        }
        return $value;
    }
}

?>
