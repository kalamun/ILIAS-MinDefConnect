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

    private bool $reapply_patch_after_update = false;

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

    protected function afterActivation(): void
    {
        $config_gui = new ilMinDefConnectConfigGUI();
        if (!$config_gui->isPatchActive() && $config_gui->canPatch()) {
            $config_gui->patch();
        }

        self::enableOpenIdConnect();
        parent::afterActivation();
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

    protected function beforeUpdate(): bool
    {
        $config_gui = new ilMinDefConnectConfigGUI();
        if ($config_gui->isPatchActive()) {
            $config_gui->unpatch();
            $this->reapply_patch_after_update = true;
        }

        return parent::beforeUpdate();
    }

    protected function afterUpdate(): void
    {
        if ($this->reapply_patch_after_update) {
            $this->reapply_patch_after_update = false;
            (new ilMinDefConnectConfigGUI())->patch();
        }

        parent::afterUpdate();
    }

    protected function disableEverything()
    {
        global $DIC;
        $DIC['ilias']->setSetting('hide_ilias_login_form', false);

        $ilMinDefConnectConfigGUI = new ilMinDefConnectConfigGUI();
        $ilMinDefConnectConfigGUI->unpatch();

        self::disableOpenIdConnect();
    }

    protected function enableOpenIdConnect(): void
    {
        global $DIC;

        $oidc_settings = ilOpenIdConnectSettings::getInstance();
        $oidc_settings->setActive(true);
        $oidc_settings->save();

        // 'userinfo_endpoint_enabled' and 'refresh_token_enabled' only exist on the
        // patched OpenID Connect settings class (see ilMinDefConnectConfigGUI::patch()),
        // so they are written directly to the underlying storage rather than through
        // setters that may not exist on the currently loaded (unpatched) class.
        $oidc_storage = new ilSetting('oidc');
        $oidc_storage->set('userinfo_endpoint_enabled', '1');
        $oidc_storage->set('refresh_token_enabled', '1');

        $DIC['ilias']->setSetting('auth_mode', (string) ilAuthUtils::AUTH_OPENID_CONNECT);
    }

    protected function disableOpenIdConnect(): void
    {
        global $DIC;

        $oidc_settings = ilOpenIdConnectSettings::getInstance();
        $oidc_settings->setActive(false);
        $oidc_settings->save();

        $DIC['ilias']->setSetting('auth_mode', (string) ilAuthUtils::AUTH_LOCAL);
    }
}
