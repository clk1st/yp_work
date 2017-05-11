<?php
/**
 * @name Bootstrap
 * @author ryan
 * @desc 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * @see http://www.php.net/manual/en/class.yaf-bootstrap-abstract.php
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
class Bootstrap extends Yaf_Bootstrap_Abstract{

    public function _initConfig() {
        //把配置保存起来
        $arrConfig = Yaf_Application::app()->getConfig();
        Yaf_Registry::set('sysConfig', $arrConfig);
    }

    public function _initPlugin(Yaf_Dispatcher $dispatcher) {
        //注册一个插件
        $YPPlugin = new YPPlugin();
        $dispatcher->registerPlugin($YPPlugin);
    }

    public function _initRoute(Yaf_Dispatcher $dispatcher) {
        //在这里注册自己的路由协议,默认使用简单路由
        $dispatcher->getRouter()->addRoute("name",
                new Yaf_Route_Regex(
                    "/^\/(\w+)\/(\w+)$/i", //match request uri leading "/product"
                    array(
                        'controller' => ":name",  //使用上面匹配的:name, 也就是$1作为controller
                        'action' => ":action",
                        ),
                    array(
                        1 => "name",   // now you can call $request->getParam("name")
                        2 => "action",     // to get the first captrue in the match pattern.
                        )
                    )
                );
    }
    
    public function _initView(Yaf_Dispatcher $dispatcher){
        //在这里注册自己的view控制器，例如smarty,firekylin
        Yaf_Dispatcher::getInstance()->autoRender(false);
    }
}
