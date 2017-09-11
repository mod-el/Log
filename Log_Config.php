<?php
namespace Model;

class Log_Config extends Module_Config {
	public function install(array $data = []){
		$q1 = $this->model->_Db->query('CREATE TABLE IF NOT EXISTS `zk_log` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `session` blob NOT NULL,
		  `events` blob NOT NULL,
		  `date` datetime NOT NULL,
		  `user` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
		  `url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
		  `loading_id` char(16) COLLATE utf8_unicode_ci NOT NULL,
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

		if(!$q1 or !$q2)
			return false;

		return true;
	}
}
