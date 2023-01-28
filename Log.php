<?php namespace Model\Log;

use Model\Core\Module;
use Model\Logger\Logger;

class Log extends Module
{
	public function terminate()
	{
		Logger::persist();
	}

	/**
	 * @param string $event
	 * @param array $data
	 */
	public function showEventData(string $event, array $data): void
	{
		if (count($data) === 0)
			return;
		if (count($data) === 1) {
			echo reset($data);
			return;
		}

		echo '[ show ]';
	}
}
