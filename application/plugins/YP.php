<?php

/**
 * @name YPPlugin
 * @desc Yaf定义了如下的6个Hook,插件之间的执行顺序是先进先Call
 * @see http://www.php.net/manual/en/class.yaf-plugin-abstract.php
 */
class YPPlugin extends Yaf_Plugin_Abstract {

    private $defaultInterceptor = 'Interceptor_ParamAuth';

    public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
        set_error_handler(array($this, 'site_error_handler'));
    }

    public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
        $interceptorConfig = Interceptor_Config::getConfig();
        $controllerName = $request->getControllerName();
        $actionName = $request->getActionName();
        $interceptorMethodList = isset($interceptorConfig[$controllerName . ',' . $actionName]) ? $interceptorConfig[$controllerName . ',' . $actionName] : $interceptorConfig[$controllerName . ','];
        $interceptorMethodList = is_array($interceptorMethodList) && count($interceptorMethodList) > 0 ?$interceptorMethodList:array ($this->defaultInterceptor);
        foreach ($interceptorMethodList as $value) {
            $interceptor = new $value();
            $interceptor->before($request, $response);
        }
    }

    public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {}

    public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {}

    public function postDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {}

    public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
        $interceptorConfig = Interceptor_Config::getConfig();
        $controllerName = $request->getControllerName();
        $actionName = $request->getActionName();
        $interceptorMethodList = isset($interceptorConfig[$controllerName.','.$actionName])?$interceptorConfig[$controllerName.','.$actionName]:$interceptorConfig[$controllerName.','];
        $interceptorMethodList = is_array($interceptorMethodList) && count($interceptorMethodList) > 0 ?$interceptorMethodList:array ($this->defaultInterceptor);
        if (is_array($interceptorMethodList) && count($interceptorMethodList) > 0){
            foreach ($interceptorMethodList as $value){
                $interceptor = new $value();
                $interceptor->after($request,$response);
            }
        }
    }
    
    /**
     * 自定义的错误处理函数。
     * 在系统发生严重错误或者trigger_error()被呼叫时执行。
     * @param <int> $errno 错误的级别，整型。为系统预定义错误代码。
     * @param <string> $errstr 错误信息，字符。
     * @param <string> $errfile 产生错误的文件名，可选。
     * @param <int> $errline 产生错误的代码所在的行数，可选。
     * @param <array> $errcontext 错误的上下文变量数组。手机版新架构测试用
     */
    public function site_error_handler($errno, $errstr = "", $errorfile = "", $errline = 0, $errcontext = array()) {
        //根据错误级别记录日志
        $errno = intval($errno);
        $error_log_level = array(1, 2, 4, 16, 32, 64, 4096);
    
        if (in_array($errno, $error_log_level)) {
            Log_File::writeSysErrorLog($errno, $errstr, $errorfile, $errline, true);
        }
    }
}
