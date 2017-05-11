<?php
abstract class Interceptor_Base {
    public abstract function before(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response);
    public abstract function after(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response);
}

?>