<?php namespace Model\Log;

use Model\Core\Module_Config;

class Config extends Module_Config
{
	public bool $configurable = true;
	public bool $hasCleanUp = true;

	/**
	 * @param string $type
	 * @return null|string
	 */
	public function getTemplate(string $type): ?string
	{
		if ($type === 'config') {
			if (is_numeric($this->model->getRequest(4))) {
				return 'log-single';
			} else {
				return 'log';
			}
		} else {
			return null;
		}
	}

	public function getConfigData(): ?array
	{
		return [];
	}
}
