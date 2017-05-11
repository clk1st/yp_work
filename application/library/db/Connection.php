<?php
/**
 * 数据库操作pdo
 */
class Db_Connection {

	private static $instance = array ();

	private $connection = null;

	private $readConnection = null;

	protected function __construct($conn = '') {
		if ($conn) {
			$options = array (PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4' );
			try {
				$this->connection = new PDO ( $conn ['dsn'], $conn ['username'], $conn ['password'], $options );
				$this->connection->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
				if ($conn ['read']) {
					$this->readConnection = new PDO ( $conn ['read'] ['dsn'], $conn ['read'] ['username'], $conn ['read'] ['password'], $options );
					$this->readConnection->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
				} else {
					$this->readConnection = $this->connection;
				}
			} catch ( PDOException $e ) {
				echo 'Connection failed: ' . $e->getMessage ();
			}
		}
	}

	public static function factory($database) {
		if (! isset ( self::$instance [$database] )) {
			$config = self::getDbParam ( $database );
			if (! $config) {
				Error_Exception::throwException ( Enum_ErrorCode::ERROR_DB_CONFIG_NOTFOUND, 'miss database config!' );
			}
			self::$instance [$database] = new self ( $config );
		}
		return self::$instance [$database];
	}

	/**
	 * 获取数据库连接参数
	 *
	 * @param array $param
	 *        	$param['dsn'] = 'mysql:host=localhost;dbname=testdb';
	 *        	$param['username'] = 'username';
	 *        	$param['password'] = 'password';
	 *        	$param['readdsn'] = 'mysql:host=localhost;dbname=testdb';
	 *        	$param['read']['username'] = 'username';
	 *        	$param['read']['password'] = 'password';
	 * @return Ambigous <boolean, unknown>
	 */
	private static function getDbParam($database) {
		$result = false;
		$sysConfig = Yaf_Registry::get ( 'sysConfig' );
		if ($sysConfig->dbs->{$database}) {
			$configs = $sysConfig->dbs->{$database};
			$result ['dsn'] = $configs->dsn;
			$result ['username'] = $configs->username;
			$result ['password'] = $configs->password;
			if ($configs->read) {
				$result ['read'] ['dsn'] = $configs->read->dsn;
				$result ['read'] ['username'] = $configs->read->username;
				$result ['read'] ['password'] = $configs->read->password;
			}
		}
		return $result;
	}

	/**
	 * 批量新增操作
	 *
	 * @param string $table        	
	 * @param array $data        	
	 * @return Ambigous <string, number, number>|boolean
	 */
	public function insertBatch($table, $data) {
		if ($table && is_array ( $data ) && count ( $data ) > 0) {
			$values = array ();
			$dataList = array ();
			foreach ( $data as $dataOne ) {
				ksort ( $dataOne );
				$cols = array ();
				$marks = array ();
				foreach ( $dataOne as $field => $value ) {
					$cols [] = '`' . $field . '`';
					$marks [] = '?';
				}
				$values [] = '(' . implode ( ', ', $marks ) . ')';
				$dataList = array_merge ( $dataList, array_values ( $dataOne ) );
			}
			$query = 'INSERT INTO ' . $table . ' (' . implode ( ', ', $cols ) . ')' . ' VALUES ' . implode ( ",", $values );
			return $this->executeUpdate ( $query, $dataList );
		}
		return false;
	}

	/**
	 * 新增操作
	 *
	 * @param string $table        	
	 * @param array $data        	
	 * @return Ambigous <string, number, number>|boolean
	 */
	public function insert($table, $data) {
		if ($table && is_array ( $data ) && count ( $data ) > 0) {
			$cols = array ();
			$marks = array ();
			foreach ( $data as $field => $value ) {
				$cols [] = '`' . $field . '`';
				$marks [] = '?';
			}
			$query = 'INSERT INTO ' . $table . ' (' . implode ( ', ', $cols ) . ')' . ' VALUES (' . implode ( ', ', $marks ) . ')';
			return $this->executeUpdate ( $query, array_values ( $data ) );
		}
		return false;
	}

	public function update($table, $data, $where) {
		if ($table && is_array ( $data ) && count ( $data ) > 0 && is_array ( $where ) && count ( $where ) > 0) {
			$updateCols = array ();
			foreach ( $data as $field => $value ) {
				$updateCols [] = '`' . $field . '`' . '= ? ';
			}
			$whereCols = array ();
			foreach ( $where as $field => $value ) {
				$whereCols [] = " `{$field}` = ? ";
			}
			$query = 'UPDATE ' . $table . ' SET ' . implode ( ', ', $updateCols ) . ' where ' . implode ( "and", $whereCols );
			return $this->executeUpdate ( $query, array_merge ( array_values ( $data ), array_values ( $where ) ) );
		}
		return false;
	}

	/**
	 * 查询全部
	 *
	 * @param string $sql        	
	 * @param array $params        	
	 * @param string $type
	 *        	w是写，r是读
	 * @return false | array
	 */
	public function fetchAll($sql, $params, $type = 'r') {
		$statement = $this->execute ( $sql, $params, $type );
		return $statement->fetchAll ( \PDO::FETCH_ASSOC );
	}

	public function fetchObject($sql, $params, $type = 'r') {
		$statement = $this->execute ( $sql, $params, $type );
		return $statement->fetch ( \PDO::FETCH_OBJ );
	}

	public function fetchAssoc($sql, $params, $type = 'r') {
		$statement = $this->execute ( $sql, $params, $type );
		return $statement->fetch ( \PDO::FETCH_ASSOC );
	}

	/**
	 * 删除操作
	 *
	 * @param string $table        	
	 * @param string $id        	
	 * @return Ambigous <string, number, number>
	 */
	public function delete($table, $id) {
		if ($table && $id) {
			$sql = 'DELETE FROM ' . $table . ' WHERE id=?';
			return $this->executeUpdate ( $sql, array ($id ) );
		}
		return false;
	}

	/**
	 * 预编译语句
	 *
	 * @param string $sql        	
	 * @param $type w代表写，r代表读        	
	 * @return PDOStatement
	 */
	public function prepareSql($sql, $type = 'w') {
		$connection = null;
		switch ($type) {
			case 'r' :
				$connection = $this->readConnection;
				break;
			default :
				$connection = $this->connection;
		}
		return $connection->prepare ( $sql );
	}

	/**
	 * 执行sql语句
	 *
	 * @param string $sql        	
	 * @param array $params        	
	 * @param string $type
	 *        	默认r读取只读库，w读取主库
	 * @return PDOStatement
	 */
	public function execute($sql, array $params, $type = 'r') {
		$type = ($type == 'w') ? $type : 'r';
		$statement = $this->prepareSql ( $sql, $type );
		$index = 1;
		foreach ( $params as $key => $value ) {
			$statement->bindValue ( $index, $value );
			$index ++;
		}
		$statement->execute ();
		return $statement;
	}

	/**
	 * 执行更新/新增sql语句
	 *
	 * @param string $sql        	
	 * @param array $params        	
	 * @return string|number
	 */
	public function executeUpdate($sql, array $params = array()) {
		if (empty ( $params )) {
			return $this->connection->exec ( $sql );
		}
		$statement = $this->prepareSql ( $sql, 'w' );
		$index = 1;
		foreach ( $params as $key => $value ) {
			$statement->bindValue ( $index, $value );
			$index ++;
		}
		$statement->execute ();
		return $statement->rowCount ();
	}

	/**
	 * 获取 insertid
	 */
	public function lastInsertId() {
		return $this->connection->lastInsertId ();
	}

	/**
	 * 获取最新的错误消息
	 */
	public function getErrorInfo() {
		return $this->connection->errorInfo ();
	}

	/**
	 * 获取写实例
	 *
	 * @return PDO
	 */
	public function getConnection() {
		return $this->connection;
	}

	/**
	 * 获取读实例
	 *
	 * @return PDO
	 */
	public function getReadConnection() {
		return $this->readConnection;
	}

	/**
	 * 开始事物
	 */
	public function beginTransaction() {
		$this->connection->beginTransaction ();
	}

	/**
	 * 提交事务
	 */
	public function commit() {
		$this->connection->commit ();
	}

	/**
	 * 回滚事物
	 */
	public function rollback() {
		$this->connection->rollback ();
	}

	public function __destruct() {
		$this->connection = null;
		$this->readConnection = null;
	}
}
