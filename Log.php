<?php namespace Model\Log;

use Model\Core\Module;

class Log extends Module
{
	/** @var mixed */
	private $queryLog = false;
	/** @var array */
	private $logWith = [
		'ttl' => 0,
		'reasons' => [],
	];

	/**
	 * @param mixed $options
	 */
	function init(array $options)
	{
		$config = $this->retrieveConfig();
		$this->logWith['ttl'] = $config['tempTtl'] ?? 1800;

		$this->model->on('Db_query', function ($data) {
			$this->queryLog = $data;
		});

		$this->model->on('Db_queryExecuted', function ($data) {
			if ($this->queryLog === false or !$this->model->getEventsFlag())
				return false;

			$data = array_merge($this->queryLog, $data);
			$this->queryLog = false;

			if (!in_array($data['type'], array('INSERT', 'UPDATE', 'DELETE')))
				return false;

			$users = $this->getUsersIndicator();
			$users_string = $users === null ? null : json_encode($users);

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
					' . ($users_string !== null ? $this->model->_Db->quote($users_string) : 'NULL') . ',
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

		foreach (($config['logOn'] ?? []) as $event => $ttl) {
			$this->model->on($event, function ($data) use ($event, $ttl) {
				$this->logEvents($event, $ttl);
			});
		}
	}

	/**
	 * Logs the current execution
	 *
	 * @param string|null $reason
	 * @param int $ttl (default 14 days)
	 */
	public function logEvents(string $reason = null, int $ttl = null)
	{
		$config = $this->retrieveConfig();

		if ($this->logWith === null) {
			$this->logWith = [
				'ttl' => $config['tempTtl'],
				'reasons' => [],
			];
		}

		if ($ttl === null)
			$ttl = $config['defaultTtl'];

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
	 * @param int|null $page
	 * @return \Generator
	 */
	public function getLogs(array $where = [], ?int $page = null): \Generator
	{
		$limit = null;
		if ($page)
			$limit = (($page - 1) * 50) . ',50';

		return $this->model->_Db->select_all('zk_log', $where, [
			'order_by' => 'date DESC',
			'limit' => $limit,
		]);
	}

	/**
	 * @param array $where
	 * @param int|null $ttl
	 * @return bool
	 */
	public function preserveLogs(array $where = [], int $ttl = null): bool
	{
		$config = $this->retrieveConfig();
		if ($ttl === null)
			$ttl = $config['defaultTtl'];

		$expireAt = date_create();
		$expireAt->modify('+' . $ttl . ' seconds');

		return $this->model->_Db->update('zk_log', $where, [
			'expire_at' => $expireAt->format('Y-m-d H:i:s'),
		], ['order_by' => 'date DESC']);
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

					$prepared_session = $db->quote(json_encode($_SESSION));

					$events = $this->model->getEventsHistory();
					$prepared_events = $db->quote(json_encode($events));

					if (strlen($prepared_session) > MYSQL_MAX_ALLOWED_PACKET - 400)
						$prepared_session = '\'TOO LARGE\'';
					if (strlen($prepared_events) > MYSQL_MAX_ALLOWED_PACKET - 400)
						throw new \Exception('Packet too large');

					$get = $this->model->getInput(null, 'get');
					if (isset($get['url']))
						unset($get['url']);

					$user = $this->getUsersIndicator();
					$user = $user ? $db->quote(json_encode($user)) : 'NULL';

					$user_hash = isset($_COOKIE['ZKID']) ? $db->quote($_COOKIE['ZKID']) : 'NULL';

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
						`user_hash`,
						`url`,
						`get`,
						`loading_id`,
						`expire_at`,
						`reason`
					) VALUES(
						' . $db->quote(date('Y-m-d H:i:s')) . ',
						' . $user . ',
						' . $user_hash . ',
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

	/**
	 * Returns the current logged user (or users, if more than one)
	 */
	protected function getUsersIndicator()
	{
		$users = $this->model->allModules('User');

		if (count($users) == 0) {
			return null;
		} elseif (count($users) == 1 and isset($users[0])) {
			return reset($users)->logged();
		} else {
			$users_arr = [];
			foreach ($users as $n => $user)
				$users_arr[$n] = $user->logged();
			return $users_arr;
		}
	}
}
