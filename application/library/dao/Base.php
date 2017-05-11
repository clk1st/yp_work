<?php
class Dao_Base {
    protected $db;
    public function __construct() {
        $this->db = Db_Connection::factory('instancedb');
    }
}

?>
