<?php namespace Model\Log;

use Model\Core\Module_Config;

class Config extends Module_Config
{
	public $configurable = true;
	public $hasCleanUp = true;

	public function install(array $data = []): bool
	{
		$q1 = $this->model->_Db->query('CREATE TABLE IF NOT EXISTS `zk_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session` blob NOT NULL,
  `events` blob NOT NULL,
  `date` datetime NOT NULL,
  `user` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `get` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `post` LONGBLOB,
  `loading_id` char(16) COLLATE utf8_unicode_ci NOT NULL,
  `expire_at` datetime DEFAULT NULL,
  `reason` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;');

		$q2 = $this->model->_Db->query('CREATE TABLE IF NOT EXISTS `zk_query_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `get` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `table` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `query` text COLLATE utf8_unicode_ci NOT NULL,
  `rows` int(11) DEFAULT NULL,
  `user` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` datetime NOT NULL,
  `loading_id` char(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `row_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;');

		if (!$q1 or !$q2)
			return false;

		return true;
	}

	/**
	 * @return bool
	 */
	public function postUpdate_0_2_0()
	{
		$this->model->_Db->query('ALTER TABLE `zk_log`
  CHANGE COLUMN `events` `events` LONGBLOB NOT NULL,
  ADD COLUMN `get` VARCHAR(255) NULL AFTER `url`,
  ADD COLUMN `post` BLOB NULL AFTER `get`,
  ADD COLUMN `expire_at` DATETIME NULL AFTER `loading_id`,
  ADD COLUMN `reason` VARCHAR(250) NULL AFTER `expire_at`;');
		return true;
	}

	/**
	 *
	 */
	public function cleanUp()
	{
		$this->model->_Db->delete('zk_log', [
			'or' => [
				['expire_at', null],
				['expire_at', '<=', date('Y-m-d H:i:s')],
			],
		]);

		$threshold = date_create();
		$threshold->modify('-7 days');
		$this->model->_Db->delete('zk_query_log', [
			'data' => ['<=', $threshold->format('Y-m-d H:i:s')],
		]);
	}

	/**
	 * @param array $request
	 * @return null|string
	 */
	public function getTemplate(array $request)
	{
		if ($request[2] == 'config') {
			if (isset($request[4]) and is_numeric($request[4])) {
				return 'log-single';
			} else {
				return 'log';
			}
		} else {
			return null;
		}
	}
}
