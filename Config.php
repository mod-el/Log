<?php namespace Model\Log;

use Model\Core\Module_Config;

class Config extends Module_Config
{
	public $configurable = true;
	public $hasCleanUp = true;

	protected function assetsList()
	{
		$this->addAsset('config', 'config.php', function () {
			return '<?php
$config = [
	\'logOn\' => [
		\'error\' => null,
		\'Db_delete\' => null,
		\'ORM_save\' => null,
		\'ORM_delete\' => null,
	],
	\'tempTtl\' => 1800,
	\'defaultTtl\' => 1209600,
];
';
		});
	}

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
