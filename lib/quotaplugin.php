<?php

/**
 * This plugin is used by chooser/appinfo/remote.php
 * @author fjob
 *
 */

// NOT USED - see remote.php

class OC_Connector_Sabre_QuotaPlugin_files_accounting extends OC_Connector_Sabre_QuotaPlugin {

	public function __construct($view) {
		$this->view = $view;
	}

	public function getFreeSpace($parentUri) {
		$freeSpace = $this->view->free_space($parentUri);
		$user = \OCP\USER::getUser();
		$quotas = \OCA\Files_Accounting\Storage_Lib::getQuotas($user);
		\OCP\Util::writeLog('files_sharding', 'Quotas for '.$user.': '.
				\OCP\Util::computerFileSize($quotas['quota']).'<' .
				\OCP\Util::computerFileSize($quotas['freequota']), \OC_Log::WARN);
		if(!empty($quotas['quota']) && !empty($quotas['freequota']) &&
				\OCP\Util::computerFileSize($quotas['quota']) <
					\OCP\Util::computerFileSize($quotas['freequota']) ||
				!empty($quotas['default_quota']) && !empty($quotas['freequota']) &&
				\OCP\Util::computerFileSize($quotas['default_quota']) <
					\OCP\Util::computerFileSize($quotas['freequota'])){
			\OCP\Util::writeLog('files_sharding', 'Using freequota: '. $quotas['freequota'], \OC_Log::WARN);
			$used = \OCP\Util::computerFileSize($quotas['quota']) - $freeSpace; 
			return \OCP\Util::computerFileSize($quotas['freequota']) - $used;
		}
		return $freeSpace;
	}
}
