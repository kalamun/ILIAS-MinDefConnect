<?php
/**
 * Class ilMinDefConnectPlugin
 * @author  Kalamun <rp@kalamun.net>
 * @version $Id$
 */

class ilMinDefConnectPlugin extends ilUserInterfaceHookPlugin
{
    const PLUGIN_NAME = "MinDefConnect";
    protected static $instance = null;

    public static function getInstance() : self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function getPluginName() : string
    {
        return self::PLUGIN_NAME;
    }

}
