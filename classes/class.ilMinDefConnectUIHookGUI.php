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

        $hidden_login_form = '<div class="hidden_login_form" style="display:none;">{LOGIN_FORM}</div><div class="toggle_login_form" style="text-align: center; cursor: pointer; margin-top: 1em;">ILIAS Login</div>'
          . "<script>document.querySelector('.toggle_login_form').addEventListener('click', () => {document.querySelector('.toggle_login_form').style.display = 'none'; document.querySelector('.hidden_login_form').style.display = 'block';});</script>";
        $html = $a_par['html'];
        $html = str_replace('{LOGIN_FORM}', $hidden_login_form, $html);
        $html = str_replace('{REG_PWD_CLIENT_LINKS}', '', $html);
        return ['mode' => ilUIHookPluginGUI::REPLACE, 'html' => $html];
      }
    }

    return ['mode' => ilUIHookPluginGUI::KEEP, 'html' => ''];
  }

  function modifyGUI(string $a_comp, string $a_part, array $a_par = []): void {}
}
