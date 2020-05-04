<?php namespace Model\Log\Migrations;

use Model\Db\Migration;

class Migration_20200504115000_CreateLogTables extends Migration
{
	public function exec()
	{
		$this->createTable('zk_log');
		$this->addColumn('zk_log', 'session', ['type' => 'blob', 'null' => false]);
		$this->addColumn('zk_log', 'events', ['type' => 'longblob', 'null' => false]);
		$this->addColumn('zk_log', 'date', ['type' => 'datetime', 'null' => false]);
		$this->addColumn('zk_log', 'user', ['type' => 'varchar(200)']);
		$this->addColumn('zk_log', 'user_hash', ['type' => 'varchar(100)']);
		$this->addColumn('zk_log', 'url', ['type' => 'varchar(255)']);
		$this->addColumn('zk_log', 'get', ['type' => 'varchar(255)']);
		$this->addColumn('zk_log', 'post', ['type' => 'longblob']);
		$this->addColumn('zk_log', 'loading_id', ['type' => 'char(16)', 'null' => false]);
		$this->addColumn('zk_log', 'expire_at', ['type' => 'datetime']);
		$this->addColumn('zk_log', 'reason', ['type' => 'varchar(255)']);
		$this->addIndex('zk_log', 'expire', ['expire_at']);
		$this->addIndex('zk_log', 'date', ['date']);

		$this->createTable('zk_query_log');
		$this->addColumn('zk_query_log', 'path', ['type' => 'varchar(255)', 'null' => false]);
		$this->addColumn('zk_query_log', 'get', ['type' => 'varchar(255)', 'null' => false]);
		$this->addColumn('zk_query_log', 'type', ['type' => 'varchar(10)', 'null' => false]);
		$this->addColumn('zk_query_log', 'table', ['type' => 'varchar(50)']);
		$this->addColumn('zk_query_log', 'query', ['type' => 'text', 'null' => false]);
		$this->addColumn('zk_query_log', 'rows', ['type' => 'int']);
		$this->addColumn('zk_query_log', 'user', ['type' => 'varchar(200)']);
		$this->addColumn('zk_query_log', 'data', ['type' => 'datetime']);
		$this->addColumn('zk_query_log', 'loading_id', ['type' => 'char(16)']);
		$this->addColumn('zk_query_log', 'row_id', ['type' => 'int']);
		$this->addIndex('zk_query_log', 'date', ['data']);
	}

	public function check(): bool
	{
		return $this->tableExists('zk_log');
	}
}
