<?php

/**
 * Config screen
 */
class ilMinDefConnectConfigGUI extends ilPluginConfigGUI {

    const PLUGIN_CLASS_NAME = ilMinDefConnectPlugin::class;
    const CMD_CONFIGURE = "configure";
    const CMD_UPDATE_CONFIGURE = "updateConfigure";
    const LANG_MODULE = "config";

    protected $dic;
    protected $plugin;
    protected $lng;
    protected $request;
    protected $user;
    protected $ctrl;
    protected $object;

    protected $replace_list;
  
    public function __construct()
    {
      global $DIC;
      $this->dic = $DIC;
      $this->plugin = ilMinDefConnectPlugin::getInstance();
      $this->lng = $this->dic->language();
      // $this->lng->loadLanguageModule("assessment");
      $this->request = $this->dic->http()->request();
      $this->user = $this->dic->user();
      $this->ctrl = $this->dic->ctrl();
      $this->object = $this->dic->object();

      $this->replace_list = [
        [ // ILIAS 7
            "path" => "./Services/LDAP/classes/class.ilLDAPCronSynchronization.php",
            "match" => 'setNewUserAuthMode($this->current_server->getAuthenticationMappingKey())',
            "replace" => 'setNewUserAuthMode("oidc")',
        ],
        [ // ILIAS 8
            "path" => "./Services/LDAP/classes/class.ilLDAPCronSynchronization.php",
            "match" => 'setNewUserAuthMode($current_server->getAuthenticationMappingKey())',
            "replace" => 'setNewUserAuthMode("oidc")',
        ],
      ];
    }
    
    public function performCommand(/*string*/ $cmd)/*:void*/
    {
        $this->plugin = $this->getPluginObject();

        switch ($cmd)
		{
			case self::CMD_CONFIGURE:
            case self::CMD_UPDATE_CONFIGURE:
                $this->{$cmd}();
                break;

            default:
                break;
		}
    }

    protected function configure()/*: void*/
    {
        global $tpl, $ilCtrl, $lng;

        /* */
        $is_active = true;
        $is_writable = true;
        $is_doable = true;

        foreach ($this->replace_list as $rule) {
            $file_content = file_get_contents($rule['path']);
            if (!is_writable($rule['path'])) $is_writable = false;
            if (strpos($file_content, '/* edited by MinDefConnect') === false) $is_active = false;
            if (strpos($file_content, $rule['match']) === false) $is_doable = false;
        }

		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this));
        $form->setTitle($this->plugin->txt("settings"));
        
        $plugin_enabled_heading = new ilFormSectionHeaderGUI();
        $plugin_enabled_heading->setTitle($this->plugin->txt($is_writable ? ($is_doable ? "compatible_version" : "incompatible_version") : "not_writable", true));
        $form->addItem($plugin_enabled_heading);
        
        $plugin_enabled_input = new ilCheckboxInputGUI($this->plugin->txt("plugin_enabled", true), 'plugin_enabled');
        $plugin_enabled_input->setChecked($plugin_enabled);
        $plugin_enabled_input->setValue('1');
        $form->addItem($plugin_enabled_input);
        
        $form->addCommandButton("updateConfigure", $lng->txt("save"));

		$tpl->setContent($form->getHTML());
    }

    protected function updateConfigure()/*: void*/
    {
        if (!empty($_POST['plugin_enabled'])) {
            foreach ($this->replace_list as $rule) {
                if (file_exists($rule['path'])) {
                    $file_content = file_get_contents($rule['path']);
                    
                    /* backup */
                    if (strpos($file_content, '/* edited by MinDefConnect') === false) {
                        copy($rule['path'], dirname($rule['path']) . '/_' . basename($rule['path']));
                    }
    
                    if (strpos($file_content, '/* edited by MinDefConnect') === false) {
                        $file_content = "/* edited by MinDefConnect */\n\n" . $file_content;
                    }
    
                    $file_content = str_replace($rule['match'], $rule['replace'], $file_content);
                    file_put_contents($rule['path'], $file_content);
                }
            }
            
        } else {
            foreach ($this->replace_list as $rule) {
                if (file_exists($rule['path'])) {
                    $file_content = file_get_contents($rule['path']);
                    
                    /* backup */
                    if (strpos($file_content, '/* edited by MinDefConnect') === false) {
                        move(dirname($rule['path']) . '/_' . basename($rule['path']), $rule['path']);
                    }
                }
            }

        }

        self::configure();

        ilUtil::sendSuccess($this->plugin->txt("configuration_saved"), true);

    }
}
