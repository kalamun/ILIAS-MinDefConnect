<?php

/**
 * Class ilMinDefConnectUIHookGUI
 * @author            Kalamun <rp@kalamun.net>
 * @version $Id$
 * @ingroup ServicesUIComponent
 * @ilCtrl_isCalledBy ilMinDefConnectUIHookGUI: ilUIPluginRouterGUI, ilAdministrationGUI, ilMinDefConnectGUI
 */
class ilMinDefConnectUIHookGUI extends ilUIHookPluginGUI
{
  public function __construct() {}

  function getHTML($a_comp = false, $a_part = false, $a_par = array()): array
  {
    if (isset($a_par['tpl_id']) && $a_par['tpl_id'] == 'components/ILIAS/Init/tpl.login.html') {
      global $DIC;
      $hide_login_form = $DIC['ilias']->getSetting('hide_ilias_login_form');

      if ($hide_login_form == true && !isset($_GET['login-form'])) {
        $html = $a_par['html'];
        $html = str_replace('{LOGIN_FORM}', '', $html);
        $html = str_replace('{REG_PWD_CLIENT_LINKS}', '', $html);
        return ['mode' => ilUIHookPluginGUI::REPLACE, 'html' => $html];
      }
    }

    return ['mode' => ilUIHookPluginGUI::KEEP, 'html' => ''];
  }

  function modifyGUI(string $a_comp, string $a_part, array $a_par = []): void {}
}
