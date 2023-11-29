<?php

/**
 * Class ilMinDefConnectUIHookGUI
 * @author            Kalamun <rp@kalamun.net>
 * @version $Id$
 * @ingroup ServicesUIComponent
 * @ilCtrl_isCalledBy ilMinDefConnectUIHookGUI: ilUIPluginRouterGUI, ilAdministrationGUI, ilMinDefConnectGUI
 */

class ilMinDefConnectUIHookGUI extends ilUIHookPluginGUI {

  public function __construct()
  {
  }
  
	function getHTML($a_comp = false, $a_part = false, $a_par = array()) {
    return ["mode" => ilUIHookPluginGUI::KEEP, "html" => ""];
  }

  function modifyGUI($a_comp, $a_part, $a_par = array())
	{
	}
  
}