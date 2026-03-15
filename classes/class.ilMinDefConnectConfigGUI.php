<?php

/**
 * Class ilMinDefConnectConfigGUI
 * @author            Roberto Pasini <bonjour@kalamun.net>
 * @ilCtrl_IsCalledBy ilMinDefConnectConfigGUI: ilObjComponentSettingsGUI
 */
class ilMinDefConnectConfigGUI extends ilPluginConfigGUI
{
  private \ILIAS\UI\Factory $ui_factory;
  private ilLanguage $lng;
  private \ILIAS\UI\Renderer $renderer;
  private ilHelpGUI $help;
  private ilCtrlInterface $ctrl;
  private \Psr\Http\Message\ServerRequestInterface $request;
  private ilGlobalTemplateInterface $tpl;
  private $ilias;

  protected $compatible_version;
  protected $is_active;
  protected $replace_list;
  protected $is_writable;

  public function __construct()
  {
    global $DIC;

    $this->ui_factory = $DIC->ui()->factory();
    $this->renderer = $DIC->ui()->renderer();
    $this->tpl = $DIC->ui()->mainTemplate();
    $this->lng = $DIC->language();
    $this->help = $DIC->help();
    $this->request = $DIC->http()->request();
    $this->ctrl = $DIC->ctrl();
    $this->ilias = $DIC['ilias'];

    $this->replace_list = [
      ['../components/ILIAS/OpenIdConnect/classes/', 'class.ilAuthProviderOpenIdConnect.php'],
      ['../vendor/composer/vendor/jumbojett/openid-connect-php/src/', 'OpenIDConnectClient.php'],
    ];

    $this->detect_version();
  }

  private function get_local_path($file_path)
  {
    return $file_path[0];
  }

  private function get_local_filename($file_path)
  {
    return $file_path[1];
  }

  private function get_local_fullpath($file_path)
  {
    return $file_path[0] . $file_path[1];
  }

  public function detect_version()
  {
    $this->is_writable = true;
    $this->is_active = false;
    $this->compatible_version = false;

    foreach ($this->replace_list as $file_path) {
      if (!is_writable($this->get_local_fullpath($file_path))) {
        $this->is_writable = false;
      }

      $file_name = $this->get_local_filename($file_path);
      $content = file_get_contents($this->get_local_fullpath($file_path));
      if (strpos($content, '* edited by MinDefConnect v') !== false) {
        $this->is_active = true;
        preg_match('#^((\d+\.)+\d+)#', substr($content, strpos($content, '* edited by MinDefConnect v') + 24, 8), $matched_version);
        $this->compatible_version = $matched_version[0];
      } else {
        foreach (glob(__DIR__ . '/../vendor/bkup_files/*', GLOB_ONLYDIR) as $path) {
          $bkup_file_content = file_get_contents($path . '/' . $file_name);
          if ($bkup_file_content == $content) {
            $this->compatible_version = basename($path);
          }
        }
      }
    }
  }

  public function performCommand(string $cmd): void
  {
    $this->help->setScreenIdComponent($this->getPluginObject()->getId());
    $this->help->setScreenId('adm');

    switch ($cmd) {
      case 'configure':
      case 'save':
        $this->$cmd();
        break;
    }
  }

  public function configure()
  {
    $form = $this->buildForm();
    $this->tpl->setContent($this->renderer->render($form));
  }

  public function patch()
  {
    // copy patches to destinations
    foreach ($this->replace_list as $file_path) {
      if ($this->compatible_version) {
        $file_name = $this->get_local_filename($file_path);
        $copy_from = __DIR__ . '/../vendor/patches/' . $this->compatible_version . '/' . $file_name;
        $copy_to = $this->get_local_fullpath($file_path);

        if (file_exists($copy_from) && file_exists($copy_to)) {
          copy($copy_from, $copy_to);
        }
      }
    }
  }

  public function unpatch()
  {
    // copy bkup files to destinations
    foreach ($this->replace_list as $file_path) {
      if ($this->compatible_version) {
        $file_name = $this->get_local_filename($file_path);
        $copy_from = __DIR__ . '/../vendor/bkup_files/' . $this->compatible_version . '/' . $file_name;
        $copy_to = $this->get_local_fullpath($file_path);

        if (file_exists($copy_from) && file_exists($copy_to)) {
          copy($copy_from, $copy_to);
        }
      }
    }
  }

  public function save()
  {
    $form = $this->buildForm()->withRequest($this->request);

    if ($data = $form->getData()) {
      $this->ilias->setSetting('hide_ilias_login_form', !empty($data['main']['login']['hide_ilias_login_form']));

      if (!empty($data['main']['patch']['is_active']) && !$this->is_active) {
        $this->patch();
      }

      if (empty($data['main']['patch']['is_active']) && $this->is_active) {
        $this->unpatch();
      }

      $this->tpl->setOnScreenMessage('success', $this->lng->txt('saved_successfully'), true);
      $this->ctrl->redirect($this, 'configure');
    } else {
      $this->tpl->setContent($this->renderer->render($form));
    }
  }

  public function buildForm(): \ILIAS\UI\Component\Input\Container\Form\Form
  {
    $field = $this->ui_factory->input()->field();
    $plugin = $this->plugin_object;

    $values = [
      'login' => [
        'hide_ilias_login_form' => $this->ilias->getSetting('hide_ilias_login_form') == true,
      ],
      'patch' => $this->compatible_version ? [
        'is_active' => $this->is_active,
      ] : [],
    ];

    $login_section = $field->section([
      'hide_ilias_login_form' => $field->checkbox($plugin->txt('hide_ilias_login_form'), $plugin->txt('hide_ilias_login_form_info')),
    ], $plugin->txt('login_page'));

    $patch_section = $field->section(!empty($this->compatible_version) ? [
      'is_active' => $field->checkbox($plugin->txt('mindefconnect_status_is_' . ($this->is_active ? 'enabled' : 'disabled')), $plugin->txt('mindefconnect_status_is_' . ($this->is_active ? 'enabled' : 'disabled') . '_info')),
    ] : [], $plugin->txt('mindefconnect_status') . ' (' . !empty($this->compatible_version) ? ($plugin->txt('mindefconnect_version') . ' v' . $this->compatible_version) : $plugin->txt('mindefconnect_not_compatible') . ')');

    $main_section = $field
      ->section([
        'login' => $login_section,
        'patch' => $patch_section,
      ], $plugin->txt('configuration'))
      ->withValue($values);

    return $this->ui_factory->input()->container()->form()->standard(
      $this->ctrl->getFormAction($this, 'save'),
      [
        'main' => $main_section,
      ]
    );
  }
}
