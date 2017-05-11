<?PHP
/**
 * Memcached封装类
 * 如果使用多台Memcahced服务器的话，需要在config/cache_config.php中做如下配置
 * <code>
 * // host, port, persistent, weight
 * $config['MemcacheServers'] = array(
 * array('192.168.1.31', '11211', true, 40),
 * array('192.168.1.23', '11211', true, 80)
 * );
 * </code>
 */
class Cache_MemoryCache {

	/**
	 * memcache对象
	 */
	private $memcache = null;

	private static $instance = null;

	private $prefix = '';

	/**
	 * 构造函数，私有，singleton模式
	 */
	private function __construct() {
		// 创建实例
		// $this->memcache = new Memcache;
		$this->memcache = new Memcached ();
		$this->memcache->setOption ( Memcached::OPT_BINARY_PROTOCOL, true );
		// 获取配置
		$conf = $this->getMemcacheParam ();
		if (! empty ( $conf )) {
			$this->memcache->addServer ( $conf ['host'], $conf ['port'] );
			if (method_exists ( $this->memcache, "setSaslAuthData" ) && $conf ['username'] && $conf ['password']) {
				$this->memcache->setSaslAuthData ( $conf ['username'], $conf ['password'] );
			}
		} else {
			// 抛出异常，记录日志
			Error_Exception::throwException ( '1', 'invalid memcached config. ' );
		}
	}

	private function getMemcacheParam() {
		$sysConfig = Yaf_Registry::get ( 'sysConfig' );
		$result ['host'] = $sysConfig->memcache->host;
		$result ['port'] = $sysConfig->memcache->port;
		$result ['username'] = $sysConfig->memcache->username;
		$result ['password'] = $sysConfig->memcache->password;
		$result ['debug'] = $sysConfig->memcache->debug;
		$this->prefix = $sysConfig->memcache->prefix;
		return $result;
	}

	/**
	 * 析构函数
	 */
	public function __destruct() {
		// 释放内嵌对象
		unset ( $this->memcache );
	}

	/**
	 * 静态方法，返回Memcached类
	 *
	 * @return <object> Memcached类实例
	 */
	public static function getInstance() {
		if (self::$instance == null) {
			self::$instance = new Cache_MemoryCache ();
		}
		return self::$instance;
	}

	/**
	 * 对传入的参数进行加密作为缓存中的key值
	 * memcached对key的长度限制是250个字符
	 *
	 * @param string $key
	 *        	自定义的
	 */
	protected function makekey($key) {
		return $this->prefix . md5 ( $key );
	}

	/**
	 * 根据参数生成设定过期时间，如果为空默认为10分钟
	 * Memcache的最大有效时长为30天，赋值超过此值会造成获取不到数据
	 *
	 * @param int $expire
	 *        	数据的过期时间，以秒为单位
	 */
	protected function setExpire($expire) {
		$expire = ( int ) $expire;
		$expire = $expire > 2592000 ? 2592000 : $expire; // 60*60*24*30=2592000
		                                                 // return empty($expire) ? 600 : $expire;
		return $expire;
	}

	/**
	 * 往缓存服务器里面存储一条数据，假如$key不存在会创建一个$key
	 *
	 * @param string $key
	 *        	用于关联数据的key值
	 * @param mixed $value
	 *        	要存储的数据，除String 和 integer 类型以外的数据都会被序列化后存入
	 * @param int $expire
	 *        	数据的过期时效，以秒为单位，默认10分钟，最长30天
	 */
	public function set($key, $value, $expire = 600) {
		if (empty ( $key ) || trim ( $key ) == "") {
			throw new Exception ( "empty key in Memcache" );
		}
		$key = $this->makekey ( $key );
		$expire = $this->setExpire ( $expire );
		// return $this->memcache->set($key, $value, false, $expire);
		return $this->memcache->set ( $key, $value, $expire );
	}

	/**
	 * 往缓存服务器里面替换一条数据
	 *
	 * @param string $key
	 *        	用于关联数据的key值
	 * @param mixed $value
	 *        	要存储的数据，除String 和 integer 类型以外的数据都会被序列化后存入
	 * @param int $expire
	 *        	数据的过期时效，以秒为单位，默认10分钟
	 */
	public function replace($key, $value, $expire = 600) {
		if (empty ( $key ) || trim ( $key ) == "") {
			throw new Exception ( "empty key in Memcache" );
		}
		$key = $this->makekey ( $key );
		$expire = $this->setExpire ( $expire );
		return $this->memcache->replace ( $key, $value, $expire );
	}

	/**
	 * 往缓存服务器里面替换一条数据
	 *
	 * @param string $key
	 *        	用于关联数据的key值
	 * @param mixed $value
	 *        	要存储的数据，除String 和 integer 类型以外的数据都会被序列化后存入
	 * @param int $expire
	 *        	数据的过期时效，以秒为单位，默认10分钟
	 */
	public function replaceNew($key, $value, $expire = "") {
		if (empty ( $key ) || trim ( $key ) == "") {
			throw new Exception ( "empty key in Memcache" );
		}
		$key = $this->makekey ( $key );
		if (! empty ( $expire )) {
			$expire = $this->setExpire ( $expire );
		}
		return $this->memcache->replace ( $key, $value );
	}

	/**
	 * 获取缓存服务器里面一条数据
	 *
	 * @param string $key
	 *        	关联数据的key值
	 * @return 对应key的数据
	 */
	public function get($key) {
		if (empty ( $key ) || trim ( $key ) == "") {
			throw new Exception ( "empty key in Memcache" );
		}
		return $this->memcache->get ( $this->makekey ( $key ) );
	}

	/**
	 * 删除缓存服务器里面一条数据，PHP手册上的第二个参数的说明有误，其值必须传，且必须为0
	 *
	 * @param string $key
	 *        	关联数据的key值
	 */
	public function delete($key) {
		if (empty ( $key ) || trim ( $key ) == "") {
			throw new Exception ( "empty key in Memcache" );
		}
		$key = $this->makekey ( $key );
		return $this->memcache->delete ( $key, 0 );
	}

	/**
	 * 删除所有服务器上的缓存数据，即设置所有数据过期失效
	 */
	public function flush() {
		$this->memcache->flush ();
	}
}
?>
