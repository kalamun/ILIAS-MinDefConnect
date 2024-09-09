<?php
//use ILIAS/DI/Container;
require_once(__DIR__.'/class.ilMinDefConnectCron.php');

class ilMinDefConnectPlugin extends ilCronHookPlugin {

	const PLUGIN_ID = "xmindefconnect";
	const PLUGIN_NAME = "MinDefConnect";
	

	/** @var Container $dic */
    	private $dic;

    
	public function __construct() {
		global $DIC; 
		$this->db = $DIC->database();
		$this->dic = $DIC;
		parent::__construct($this->db, $DIC["component.repository"], self::PLUGIN_ID);
	}
	
	public static function getInstance(): ?ilMinDefConnectPlugin
	{
		return new ilMinDefConnectPlugin;
	}
	
	
	/**
	 * @inheritdoc
	 */
	public function getPluginName():string {
		return self::PLUGIN_NAME;
	}
	
	
	/**
	 * @inheritdoc
	 */
	public function getCronJobInstances():array {
		return [
			new ilMinDefConnectCron()
		];
	}
	
	
	/**
	 * @inheritdoc
	 */
	public function getCronJobInstance($a_job_id):ilCronJob
	{
		return new ilMinDefConnectCron();
	}
}
