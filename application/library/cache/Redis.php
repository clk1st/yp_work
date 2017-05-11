<?PHP
/**
 * Redis封装类
 */
class Cache_Redis {

	/**
	 * redis对象
	 */
	private $redis = null;

	private static $instance = null;

	/**
	 * 构造函数，私有，singleton模式
	 */
	private function __construct() {
		$conf = $this->getParam ();
		if (! empty ( $conf )) {
			try {
				$this->redis = new Redis ();
				$this->redis->connect ( $conf ['host'], $conf ['port'], 2 );
				$this->redis->auth ( $conf ['password'] );
				$this->redis->setOption ( Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE );
				if (isset ( $conf ['prefix'] )) {
					$this->redis->setOption ( Redis::OPT_PREFIX, $conf ['prefix'] . ':' );
				}
			} catch ( RedisException $e ) {
				Log_File::writeLog ( 'redis', $e->getCode () . ' : ' . $e->getMessage () );
			}
		} else {
			Error_Exception::throwException ( Enum_ErrorCode::ERROR_CACHE_CONFIG_NOTFOUND, 'invalid redis config. ' );
		}
	}

	private function getParam() {
		$sysConfig = Yaf_Registry::get ( 'sysConfig' );
		$result ['host'] = $sysConfig->redis->host;
		$result ['port'] = $sysConfig->redis->port;
		$result ['password'] = $sysConfig->redis->password;
		$result ['prefix'] = $sysConfig->redis->prefix;
		return $result;
	}

	/**
	 * 静态方法，返回Redis类
	 *
	 * @return <object> Redis类实例
	 */
	public static function getInstance() {
		if (self::$instance == null) {
			self::$instance = new self ();
		} else {
			$flag = self::$instance->ping ();
			if (! $flag) {
				self::$instance = new self ();
			}
		}
		return self::$instance;
	}

	public function ping() {
		$flag = false;
		try {
			$result = $this->redis->ping ();
			$flag = $result == '+PONG' ? true : false;
		} catch ( RedisException $e ) {
			$flag = false;
			Log_File::writeLog ( 'redis', $e->getCode () . ' : ' . $e->getMessage () );
		}
		return $flag;
	}

	/**
	 * 设置值 构建一个字符串
	 *
	 * @param string $key
	 *        	KEY名称
	 * @param string $value
	 *        	设置值
	 * @param int $timeOut
	 *        	时间 0表示无过期时间
	 */
	public function set($key, $value, $timeOut = 0) {
		$retRes = $this->redis->set ( $key, $value );
		if ($timeOut > 0) {
			$this->redis->expire ( "{$key}", $timeOut );
		}
		return $retRes;
	}

	/**
	 * 构建一个集合(无序集合)
	 *
	 * @param string $key
	 *        	集合Y名称
	 * @param string|array $value
	 *        	值
	 */
	public function sadd($key, $value) {
		return $this->redis->sadd ( $key, $value );
	}
	// 取集合对应元素
	public function smembers($setName) {
		return $this->redis->smembers ( $setName );
	}

	public function ssize($setName) {
		return $this->redis->sSize ( $setName );
	}

	public function sdiff($set1, $set2) {
		return $this->redis->sDiff ( $set1, $set2 );
	}

	public function sinter($setName) {
		return $this->redis->sInter ( $setName );
	}

	public function sunion($setName, $item) {
		return $this->redis->sUnion ( $setName, $item );
	}

	public function scontains($setName, $item) {
		return $this->redis->scontains ( $setName, $item );
	}

	public function sremove($setName, $item) {
		return $this->redis->sremove ( $setName, $item );
	}

	public function sCountRandMember($setName, $count) {
		return $this->redis->sRandMember ( $setName, $count );
	}

	public function sRandMember($setName) {
		return $this->redis->sRandMember ( $setName );
	}

	/*
	 * 构建一个集合(有序集合)
	 * @param string $key 集合名称
	 * @param string|array $value 值
	 */
	public function zadd($key, $score, $value) {
		return $this->redis->zAdd ( $key, $score, $value );
	}

	public function zdelete($key, $value) {
		return $this->redis->zDelete ( $key, $value );
	}

	public function zscore($key, $value) {
		return $this->redis->zScore ( $key, $value );
	}

	public function zsize($key) {
		return $this->redis->zSize ( $key );
	}

	public function zRangeByScore($key, $start, $end, $where) {
		return $this->redis->zRangeByScore ( $key, $start, $end, $where );
	}

	public function zRange($key, $start, $end, $withscores = false) {
		return $this->redis->zRange ( $key, $start, $end, $withscores );
	}
	// 倒序返回
	public function zRevRange($key, $start, $end, $withscores = false) {
		return $this->redis->zRevRange ( $key, $start, $end, $withscores );
	}

	public function zunion($ukey, $keys, $weights = array()) {
		return $weights ? $this->redis->zUnion ( $ukey, $keys, $weights ) : $this->redis->zUnion ( $ukey, $keys );
	}

	public function zinter($zkey, $keys, $weights = array()) {
		return $weights ? $this->redis->zInter ( $zkey, $keys, $weights ) : $this->redis->zInter ( $zkey, $keys );
	}

	/**
	 * 构建一个列表(先进后去，类似栈)
	 *
	 * @param sting $key
	 *        	KEY名称
	 * @param string $value
	 *        	值
	 */
	public function lpush($key, $value) {
		return $this->redis->lpush ( $key, $value );
	}

	/**
	 * 构建一个列表(先进先去，类似队列)
	 *
	 * @param sting $key
	 *        	KEY名称
	 * @param string $value
	 *        	值
	 */
	public function rpush($key, $value) {
		return $this->redis->rpush ( $key, $value );
	}

	public function lindex($key, $index) {
		return $this->lget ( $key, $index );
	}

	public function lget($key, $index) {
		return $this->redis->lGet ( $key, $index );
	}

	/**
	 * 获取所有列表数据（从头到尾取）
	 *
	 * @param sting $key
	 *        	KEY名称
	 * @param int $head
	 *        	开始
	 * @param int $tail
	 *        	结束
	 */
	public function lranges($key, $head, $tail) {
		return $this->redis->lrange ( $key, $head, $tail );
	}

	/**
	 * HASH类型
	 *
	 * @param string $tableName
	 *        	表名字key
	 * @param string $key
	 *        	字段名字
	 * @param sting $value
	 *        	值
	 */
	public function hset($tableName, $field, $value) {
		return $this->redis->hSet ( $tableName, $field, $value );
	}

	public function hget($tableName, $field) {
		return $this->redis->hGet ( $tableName, $field );
	}

	public function hdel($tableName, $field) {
		return $this->redis->hDel ( $tableName, $field );
	}

	public function hexists($tableName, $field) {
		return $this->redis->hExists ( $tableName, $field );
	}

	public function hlen($tableName) {
		return $this->redis->hLen ( $tableName );
	}

	public function hkeys($tableName) {
		return $this->redis->hKeys ( $tableName );
	}

	public function hvals($tableName) {
		return $this->redis->hVals ( $tableName );
	}

	public function hgetAll($tableName) {
		return $this->redis->hGetAll ( $tableName );
	}

	/**
	 * 设置多个值
	 *
	 * @param array $keyArray
	 *        	KEY名称
	 * @param string|array $value
	 *        	获取得到的数据
	 * @param int $timeOut
	 *        	时间
	 */
	public function sets($keyArray, $timeout) {
		if (is_array ( $keyArray )) {
			$retRes = $this->redis->mset ( $keyArray );
			if ($timeout > 0) {
				foreach ( $keyArray as $key => $value ) {
					$this->redis->expire ( $key, $timeout );
				}
			}
			return $retRes;
		} else {
			return "Call  " . __FUNCTION__ . " method  parameter  Error !";
		}
	}

	/**
	 * 通过key获取数据
	 *
	 * @param string $key
	 *        	KEY名称
	 */
	public function get($key) {
		$result = $this->redis->get ( $key );
		return $result;
	}

	/**
	 * 同时获取多个值
	 *
	 * @param ayyay $keyArray
	 *        	获key数值
	 */
	public function gets($keyArray) {
		if (is_array ( $keyArray )) {
			return $this->redis->mget ( $keyArray );
		} else {
			return "Call  " . __FUNCTION__ . " method  parameter  Error !";
		}
	}

	/**
	 * 获取所有key名，不是值
	 */
	public function keyAll() {
		return $this->redis->keys ( '*' );
	}

	/**
	 * 删除一条数据key
	 *
	 * @param string $key
	 *        	删除KEY的名称
	 */
	public function delete($key) {
		return $this->redis->delete ( $key );
	}

	/**
	 * 同时删除多个key数据
	 *
	 * @param array $keyArray
	 *        	KEY集合
	 */
	public function dels($keyArray) {
		if (is_array ( $keyArray )) {
			return $this->redis->del ( $keyArray );
		} else {
			return "Call  " . __FUNCTION__ . " method  parameter  Error !";
		}
	}

	/**
	 * 数据自增
	 *
	 * @param string $key
	 *        	KEY名称
	 */
	public function incr($key) {
		return $this->redis->incr ( $key );
	}

	/**
	 * 数据自减
	 *
	 * @param string $key
	 *        	KEY名称
	 */
	public function decr($key) {
		return $this->redis->decr ( $key );
	}

	/**
	 * 判断key是否存在
	 *
	 * @param string $key
	 *        	KEY名称
	 */
	public function isExists($key) {
		return $this->redis->exists ( $key );
	}

	/**
	 * 重命名- 当且仅当newkey不存在时，将key改为newkey ，当newkey存在时候会报错哦RENAME
	 * 和 rename不一样，它是直接更新（存在的值也会直接更新）
	 *
	 * @param string $Key
	 *        	KEY名称
	 * @param string $newKey
	 *        	新key名称
	 */
	public function updateName($key, $newKey) {
		return $this->redis->RENAMENX ( $key, $newKey );
	}

	public function renameKey($key, $newKey) {
		return $this->redis->rename ( $key, $newKey );
	}

	/**
	 * 获取KEY存储的值类型
	 * none(key不存在) int(0) string(字符串) int(1) list(列表) int(3) set(集合) int(2) zset(有序集) int(4) hash(哈希表) int(5)
	 *
	 * @param string $key
	 *        	KEY名称
	 */
	public function dataType($key) {
		return $this->redis->type ( $key );
	}

	/**
	 * 清空数据
	 */
	public function flushAll() {
		return $this->redis->flushAll ();
	}

	public function multi($option = Redis::PIPELINE) {
		return $this->redis->multi ( $option );
	}

	/**
	 * 析构函数
	 */
	public function __destruct() {
		// 释放内嵌对象
		if (! is_null ( $this->redis )) {
			try {
				$this->redis->close ();
			} catch ( RedisException $e ) {
			}
		}
	}
}
?>
