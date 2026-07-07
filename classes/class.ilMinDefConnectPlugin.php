<?php

/**
 * Class ilMinDefConnectPlugin
 * @author  Kalamun <rp@kalamun.net>
 * @version $Id$
 */
class ilMinDefConnectPlugin extends ilUserInterfaceHookPlugin
{
    const CTYPE = 'components/ILIAS';
    const CNAME = 'UIComponent';
    const SLOT_ID = 'uihk';
    const PLUGIN_NAME = 'MinDefConnect';

    protected static $instance = null;

    public function __construct(
        \ilDBInterface $db,
        \ilComponentRepositoryWrite $component_repository,
        string $id
    ) {
        parent::__construct($db, $component_repository, $id);
    }

    public static function getInstance(): ilMinDefConnectPlugin
    {
        global $DIC;

        if (self::$instance instanceof self) {
            return self::$instance;
        }

        $component_repository = $DIC['component.repository'];
        $component_factory = $DIC['component.factory'];

        $plugin_info = $component_repository->getComponentByTypeAndName(
            self::CTYPE,
            self::CNAME
        )->getPluginSlotById(self::SLOT_ID)->getPluginByName(self::PLUGIN_NAME);

        self::$instance = $component_factory->getPlugin($plugin_info->getId());

        return self::$instance;
    }

    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    protected function afterDeactivation(): void
    {
        self::disableEverything();
        parent::afterDeactivation();
    }

    protected function beforeUninstall(): bool
    {
        self::disableEverything();
        return parent::beforeUninstall();
    }

    protected function disableEverything()
    {
        global $DIC;
        $DIC['ilias']->setSetting('hide_ilias_login_form', false);

        $ilMinDefConnectConfigGUI = new ilMinDefConnectConfigGUI();
        $ilMinDefConnectConfigGUI->unpatch();
    }
}
