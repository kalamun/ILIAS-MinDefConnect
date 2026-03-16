# ILIAS MinDef xApi
This plug-in will enable ILIAS users to use access through MinDefConnect.
Supports ILIAS 10.

Copyright (c) 2026 Roberto Pasini <bonjour@kalamun.net>
GPLv3, see LICENSE

Author: Roberto Pasini <bonjour@kalamun.net>

## Install

```
mkdir -p public/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook
cd public/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook
git clone https://github.com/kalamun/ILIAS-MinDefConnect.git MinDefConnect
git checkout ilias_v10
```

## Activation

After having copied the plugin files to the plugins directory, log-in to ILIAS and go to `Administration` > `Extending ILIAS` > `Plugins`.
There, in the corresponding line, you have to click on `Actions` button, then select `Install`.
Do it again, but this time selecting `Activate`.
Then you can go to `Configure`.

## Usage

Concretely, this plugin is going to apply some modifications to the ILIAS code.
First of all it will perform some compatibility checks.
It could be the case that you don't have the writing permissions: in that case you can't apply the modifications and you have to contact the system administration to give to the Apache user (usually `www-data`) the right permissions.
It could also be the case that your ILIAS version is not compatible with the plugin: in that case a warning will be displayed.

To apply the patch, check the "Enable" checkbox, then click `Save`.
To remove the patch, uncheck the "Enable" checkbox, then click `Save`.

You can also choose to remove the default login form from the login page by checking the relative box.


## Requirements
This plugin is compatible with ILIAS v10.5, untested on other v10.x versions.
