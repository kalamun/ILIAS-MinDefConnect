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

After having copied the plugin files to the plugins directory, log-in to ILIAS and go to `Administration` > `Extending ILIAS` > `Plugins`.<br>
There, in the corresponding line, you have to click on `Actions` button, then select `Install`.<br>
Do it again, but this time selecting `Activate`.<br>
Then you can go to `Configure`.

## Usage

Concretely, this plugin is going to apply some modifications to the ILIAS code.<br>
First of all it will perform some compatibility checks.<br>
It could be the case that you don't have the writing permissions: in that case you can't apply the modifications and you have to contact the system administration to give to the Apache user (usually `www-data`) the right permissions.<br>
It could also be the case that your ILIAS version is not compatible with the plugin: in that case a warning will be displayed.

To apply the patch, check the "Enable" checkbox, then click `Save`.<br>
To remove the patch, uncheck the "Enable" checkbox, then click `Save`.

When the patch is enabled, you can navigate to the OIDC settings page (`Administration` > `Users and roles` > `Authentication and Registration` > `OpenID Connect`) and enable the UserInfo endpoint support by checking the relative checkbox.<br>
You can also choose to remove the default login form from the login page by checking the relative box.

You can also check "Use Refresh Token" to have ILIAS store the access/refresh token pair returned by the identity provider and silently renew the access token via the refresh_token grant once it expires, instead of forcing a new OIDC login. This requires the identity provider to actually issue a refresh_token, which typically means adding `offline_access` under the additional scopes on the same settings page. Other plugin/ILIAS code can retrieve a currently-valid access token via `ilAuthProviderOpenIdConnect::getValidAccessToken()`.

All the users with auth method set to OIDC are forced to log-in via OIDC.<br>
To support the internet workflow, a secondary client ID has been added to the settings page. When set, the admin users will be authentified by using that secondary client ID.

## How to setup ILIAS

### Intradef
* Go to `Administration` > `Extending ILIAS` > `Plugins`
    * Click on the MinDefConnect dropdown, then select Configure
    * Check both checkboxes: Hide ILIAS form and Enable MinDefConnect
* Go to `Administration` > `Users and roles` > `Authentication and Registration`.
* Go to `OpenID Connect` tab
    * Fill all the mandatory informations
    * Check both _Fetch additional claims from UserInfo_ and _Use Refresh Token_
    * Enable OpenID Connect by checking the related checkbox
* Go to `Authentication` tab
    * If not already set, select _ILIAS Auth_ as the default method

### Internet
* Go to `Administration` > `Extending ILIAS` > `Plugins`
    * Click on the MinDefConnect dropdown, then select Configure
    * Check both checkboxes: Hide ILIAS form and Enable MinDefConnect
* Go to `Administration` > `Users and roles` > `Authentication and Registration`.
* Go to `OpenID Connect` tab
    * Fill all the mandatory informations
    * Check both _Fetch additional claims from UserInfo_ and _Use Refresh Token_
    * Set the secondary Client ID and Client Key that will be used for logging the administrators, enabling the 2FA provided by MinDefConnect
    * Enable OpenID Connect by checking the related checkbox
* Go to `Authentication` tab
    * If not already set, select _ILIAS Auth_ as the default method
* Go to `Administration` > `Users and roles` > `User Management`.
    * Edit the administrators and set their authentication method as `OpenID Connect`

## How to recover admin access if OIDC is not working

For security reasons, the only way to recover admin access when you are stuck outside for some reasons, is to disable OpenID Connect from the database.<br>
To do so, run this query: `UPDATE settings SET value = '0' WHERE module = 'oidc' AND keyword = 'active';` on the ILIAS db.

## Requirements
This plugin is compatible with ILIAS v10.5 and ILIAS v10.8, untested on other v10.x versions.
