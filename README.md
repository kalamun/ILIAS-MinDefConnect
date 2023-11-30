# ILIAS MinDef Connect
This plug-in will enable ILIAS to enable the MinDef Connect compatibility for LDAP + OpenId SSO systems.

Copyright (c) 2023 Roberto Pasini <bonjour@kalamun.net>
GPLv3, see LICENSE

Author: Roberto Pasini <bonjour@kalamun.net>

## Install

```
mkdir -p Customizing/global/plugins/Services/UIComponent/UserInterfaceHook
cd Customizing/global/plugins/Services/UIComponent/UserInterfaceHook
git clone https://github.com/kalamun/ILIAS-MinDefConnect.git MinDefConnect
```

## Activation

After having copied the plugin files to the plugins directory, log-in to ILIAS and go to `Administration` > `Extending ILIAS` > `Plugins`.
There, in the corresponding line, you have to click on `Actions` button, then select `Install`.
Do it again, but this time selecting `Activate`.
Then you can go to `Configure`.
![image](https://github.com/kalamun/ILIAS-MinDefConnect/assets/385026/0bc38db4-6a9e-4bd2-b228-97ba4e680f4d)

## Usage

Concretely, this plugin is going to apply some modifications to the ILIAS code.
First of all it will perform some compatibility checks.
It could be the case that you don't have the writing permissions: in that case you can't apply the modifications and you have to contact the system administration to give to the Apache user (usually `www-data`) the right permissions.
It could also be the case that your ILIAS version is not compatible with the plugin: in that case a warning will be displayed, but you can still try to apply the patches.
Before applying any patch, a backup of the involved files will be done.

To apply the patch, check the `Active` checkbox and click `Save`.
![image](https://github.com/kalamun/ILIAS-MinDefConnect/assets/385026/ef018419-d810-42b2-ae30-ae38f9ad511b)

To remove the patch, uncheck it then click `Save`.
The plugin will revert the backup files.



## Requirements
This plugin is compatible with ILIAS v7.x
