<?php namespace Model\Log;

use Model\Core\Core;
use Model\Core\Module;

class Log extends Module
{
	/** @var mixed */
	private $queryLog = false;
	/** @var array */
	private $logWith = [
		'ttl' => 1800,
		'reasons' => [],
	];

	/**
	 * @param mixed $options
	 */
	function init(array $options)
	{
		$this->model->on('Db_query', function ($data) {
			$this->queryLog = $data;
		});

		$this->model->on('Db_queryExecuted', function ($data) {
			if ($this->queryLog === false)
				return false;

			$data = array_merge($this->queryLog, $data);
			$this->queryLog = false;

			if (!in_array($data['type'], array('INSERT', 'UPDATE', 'DELETE')))
				return false;

			$users = $this->model->allModules('User');

			if (count($users) == 0) {
				$users_string = false;
			} elseif (count($users) == 1) {
				$users_string = reset($users)->logged();
			} else {
				$users_string = array();
				foreach ($users as $n => $user)
					$users_string[$n] = $user->logged();
				$users_string = json_encode($users_string);
			}

			$row_id = null;
			if ($data['type'] == 'INSERT' and isset($data['id'])) {
				$row_id = $data['id'];
			} elseif ($data['type'] != 'INSERT' and preg_match('/WHERE `?id`? = \'?[0-9]+\'?/i', $data['qry'])) {
				$row_id = preg_replace('/^.+ WHERE `?id`? = \'?([0-9]+)\'?.*$/i', '$1', $data['qry']);
			}

			$qrystring = $_GET;
			if (isset($qrystring['url'])) unset($qrystring['url']);
			if (isset($qrystring['zkrand'])) unset($qrystring['zkrand']);
			$qrystring = http_build_query($qrystring);

			try {
				$this->model->switchEvents(false);

				$this->model->_Db->query('INSERT INTO `zk_query_log`(`path`,`get`,`type`,`table`,`query`,`rows`,`user`,`data`,`loading_id`,`row_id`) VALUES(
					' . $this->model->_Db->quote(implode('/', $this->model->getRequest())) . ',
					' . $this->model->_Db->quote($qrystring) . ',
					' . $this->model->_Db->quote($data['type']) . ',
					' . ($data['table'] !== false ? $this->model->_Db->quote($data['table']) : 'NULL') . ',
					' . $this->model->_Db->quote($data['qry']) . ',
					' . ($data['rows'] !== false ? $this->model->_Db->quote($data['rows']) : 'NULL') . ',
					' . ($users_string !== false ? $this->model->_Db->quote($users_string) : 'NULL') . ',
					' . $this->model->_Db->quote(date('Y-m-d H:i:s')) . ',
					' . $this->model->_Db->quote(ZK_LOADING_ID) . ',
					' . ($row_id ? $this->model->_Db->quote($row_id) : 'NULL') . '
				)');

				$this->model->switchEvents(true);
			} catch (\Exception $e) {
				$this->model->switchEvents(true);
				throw $e;
			}
		});

		$this->model->on('error', function ($data) {
			$this->logEvents('error');
		});
	}

	/**
	 * Logs the current execution
	 *
	 * @param string|null $reason
	 * @param int $ttl (default 14 days)
	 */
	public function logEvents(string $reason = null, int $ttl = 1209600)
	{
		if ($this->logWith === null) {
			$this->logWith = [
				'ttl' => 1800,
				'reasons' => [],
			];
		}

		if ($ttl > $this->logWith['ttl'])
			$this->logWith['ttl'] = $ttl;
		if ($reason) {
			if (!in_array($reason, $this->logWith['reasons']))
				$this->logWith['reasons'][] = $reason;
		}
	}

	/**
	 * Disable logging for the current execution
	 */
	public function disableAutoLog()
	{
		$this->logWith = null;
	}

	/**
	 * @param array $where
	 * @return \Generator
	 */
	public function getLogs(array $where = []): \Generator
	{
		return $this->model->_Db->select_all('zk_log', $where, ['order_by' => 'date DESC']);
	}

	/**
	 * On execution termination, it logs events if it has been told to
	 */
	public function terminate()
	{
		if ($this->logWith) {
			$this->model->switchEvents(false);

			$db = $this->model->_Db;
			if ($db) {
				try {
					if (!defined('MYSQL_MAX_ALLOWED_PACKET')) {
						$max_allowed_packet_query = $db->query('SHOW VARIABLES LIKE \'max_allowed_packet\'')->fetch();
						if ($max_allowed_packet_query)
							define('MYSQL_MAX_ALLOWED_PACKET', $max_allowed_packet_query['Value']);
						else
							define('MYSQL_MAX_ALLOWED_PACKET', 1000000);
					}

					$prepared_session = $db->quote(json_encode($_SESSION[SESSION_ID]));

					$events = $this->model->getEventsHistory();
					$prepared_events = $db->quote(json_encode($events));

					if (strlen($prepared_session) > MYSQL_MAX_ALLOWED_PACKET - 400)
						$prepared_session = '\'TOO LARGE\'';
					if (strlen($prepared_events) > MYSQL_MAX_ALLOWED_PACKET - 400)
						throw new \Exception('Packet too large');

					$get = $this->model->getInput(null, 'get');
					if (isset($get['url']))
						unset($get['url']);

					$user = isset($_COOKIE['ZKID']) ? $db->quote($_COOKIE['ZKID']) : 'NULL';

					$post = $this->model->getInput(null, 'post');

					$prepared_post = $db->quote(json_encode($post));

					if (strlen($prepared_post) > MYSQL_MAX_ALLOWED_PACKET - 400)
						$prepared_post = '\'TOO LARGE\'';

					$url = '/' . $this->model->prefix([], ['path' => false]) . implode('/', $this->model->getRequest());

					$expireAt = date_create();
					$expireAt->modify('+' . $this->logWith['ttl'] . ' seconds');

					$id = $db->query('INSERT INTO zk_log(
						`date`,
						`user`,
						`url`,
						`get`,
						`loading_id`,
						`expire_at`,
						`reason`
					) VALUES(
						' . $db->quote(date('Y-m-d H:i:s')) . ',
						' . $user . ',
						' . $db->quote($url) . ',
						' . $db->quote(http_build_query($get)) . ',
						' . $this->model->_Db->quote(ZK_LOADING_ID) . ',
						' . $db->quote($expireAt->format('Y-m-d H:i:s')) . ',
						' . $db->quote(implode(',', $this->logWith['reasons'])) . '
					)', null, 'INSERT');

					$db->query('UPDATE zk_log SET `session` = ' . $prepared_session . ' WHERE `id` = ' . $id);
					$db->query('UPDATE zk_log SET `events` = ' . $prepared_events . ' WHERE `id` = ' . $id);
					$db->query('UPDATE zk_log SET `post` = ' . $prepared_post . ' WHERE `id` = ' . $id);
				} catch (\Exception $e) {
					if (DEBUG_MODE) {
						echo ' < b>ERRORE DURANTE IL LOG:</b > ' . getErr($e);
					}
				}
			}

			$this->model->switchEvents(true);
		}
	}

	/**
	 * @param string $module
	 * @param string $event
	 * @param array $data
	 */
	public function showEventData(string $module, string $event, array $data)
	{
		if (count($data) === 0)
			return;
		if (count($data) === 1) {
			echo reset($data);
			return;
		}

		switch ($module . '_' . $event) {
			case 'Core_loadModule':
				echo $data['module'];
				if ($data['idx'] !== 0)
					echo ' (' . $data['idx'] . ')';
				break;
			default:
				echo '[ show ]';
				break;
		}
	}
}
