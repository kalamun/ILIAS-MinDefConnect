<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 * edited by MinDefConnect v10.5
 */

declare(strict_types=1);

use Jumbojett\OpenIDConnectClient;

/**
 * Class ilAuthProviderOpenIdConnect
 *
 * PATCH: Added optional UserInfo endpoint support.
 * PATCH: Added second_client_id support for admin users.
 *
 * When ilOpenIdConnectSettings::getUseUserinfoEndpoint() returns true the
 * provider calls $oidc->requestUserInfo() immediately after authenticate()
 * and merges the returned claims on top of the verified ID-token claims.
 * Per OIDC Core §5.3.2, UserInfo claims take precedence on conflict.
 *
 * When ilOpenIdConnectSettings::getSecondClientId() is non-empty and the
 * authenticated user holds the ILIAS administrator role, a second OIDC
 * round-trip is performed using second_client_id instead of client_id.
 */
class ilAuthProviderOpenIdConnect extends ilAuthProvider
{
    private const OIDC_AUTH_IDTOKEN = 'oidc_auth_idtoken';
    // --- PATCH: Second Client ID ---
    private const OIDC_USE_SECOND_CLIENT = 'oidc_use_second_client';
    // --- END PATCH ---

    private const ERR_AUTH_FAILED = 'auth_oidc_failed';

    private const ERR_AUTH_WRONG_LOGIN = 'err_wrong_login';

    private readonly ilOpenIdConnectSettings $settings;
    /** @var array $body */
    private readonly ilLogger $logger;
    private readonly ilLanguage $lng;

    public function __construct(ilAuthCredentials $credentials)
    {
        global $DIC;
        parent::__construct($credentials);

        $this->logger = $DIC->logger()->auth();
        $this->settings = ilOpenIdConnectSettings::getInstance();
        $this->lng = $DIC->language();
        $this->lng->loadLanguageModule('auth');
    }

    public function handleLogout(): void
    {
        if ($this->settings->getLogoutScope() === ilOpenIdConnectSettings::LOGOUT_SCOPE_LOCAL) {
            return;
        }

        $id_token = ilSession::get(self::OIDC_AUTH_IDTOKEN);
        $this->logger->debug('Logging out with token: ' . $id_token);

        if (isset($id_token) && $id_token !== '') {
            ilSession::set(self::OIDC_AUTH_IDTOKEN, '');
            $oidc = $this->initClient();
            try {
                $oidc->signOut(
                    $id_token,
                    ILIAS_HTTP_PATH . '/' . ilStartUpGUI::logoutUrl()
                );
            } catch (\Jumbojett\OpenIDConnectClientException $e) {
                $this->logger->warning('Logging out of OIDC provider failed with: ' . $e->getMessage());
            }
        }
    }

    public function doAuthentication(ilAuthStatus $status): bool
    {
        if (!$this->settings->getActive()) {
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
            $status->setTranslatedReason($this->lng->txt(self::ERR_AUTH_FAILED));
            $this->logger->info('Authentication aborted, OIDC authentication is disabled');
            return false;
        }

        try {
            // --- PATCH: Second Client ID — read and consume the session flag ---
            $use_second_client = (bool) ilSession::get(self::OIDC_USE_SECOND_CLIENT);
            if ($use_second_client) {
                ilSession::set(self::OIDC_USE_SECOND_CLIENT, false);
            }
            // --- END PATCH ---

            $oidc = $this->initClient($use_second_client);
            $oidc->setRedirectURL(ILIAS_HTTP_PATH . '/openidconnect.php');

            $proxy = ilProxySettings::_getInstance();
            if ($proxy->isActive()) {
                $host = $proxy->getHost();
                $port = $proxy->getPort();
                if ($port) {
                    $host .= ':' . $port;
                }
                $oidc->setHttpProxy($host);
            }

            $this->logger->debug('Redirect url is: ' . $oidc->getRedirectURL());

            $oidc->addScope($this->settings->getAllScopes());
            if ($this->settings->getLoginPromptType() === ilOpenIdConnectSettings::LOGIN_ENFORCE) {
                $oidc->addAuthParam(['prompt' => 'login']);
            }

            // Triggers the Authorization Code flow; redirects if not yet
            // authenticated, returns normally once tokens are available.
            $oidc->authenticate();

            // Start with claims from the verified ID token.
            $claims = $oidc->getVerifiedClaims();

            // -----------------------------------------------------------------
            // PATCH: optionally fetch and merge UserInfo endpoint claims.
            //
            // The jumbojett library's requestUserInfo() sends a Bearer-token
            // request to the discovery-document's userinfo_endpoint using the
            // access token that was obtained during authenticate().  No extra
            // configuration is needed beyond having a valid access token.
            //
            // Why merge instead of replace:
            //   • The ID token is cryptographically verified; we trust it.
            //   • UserInfo may carry additional claims (email, profile, …).
            //   • When the same claim appears in both, OIDC §5.3.2 says the
            //     UserInfo value should be used — hence we overwrite.
            // -----------------------------------------------------------------
            if ($this->settings->getUseUserinfoEndpoint()) {
                $claims = $this->mergeUserInfoClaims($oidc, $claims);
            }

            // --- PATCH: Second Client ID — re-authenticate admins with second_client_id ---
            if (!$use_second_client && $this->settings->getSecondClientId() !== '') {
                $uid_field   = $this->settings->getUidField();
                $ext_account = $claims->{$uid_field} ?? '';
                if (is_string($ext_account) && $ext_account !== '' && $this->isExternalAccountAdmin($ext_account)) {
                    $this->logger->debug('Admin user detected; restarting OIDC flow with second_client_id.');
                    ilSession::set(self::OIDC_USE_SECOND_CLIENT, true);
                    $this->clearOidcSessionState();
                    header('Location: ' . ILIAS_HTTP_PATH . '/openidconnect.php');
                    exit();
                }
            }
            // --- END PATCH ---

            $status = $this->handleUpdate($status, $claims);

            if ($this->settings->getLogoutScope() === ilOpenIdConnectSettings::LOGOUT_SCOPE_GLOBAL) {
                ilSession::set(self::OIDC_AUTH_IDTOKEN, $oidc->getIdToken());
            }
            return true;
        } catch (Exception $e) {
            $this->logger->warning($e->getMessage());
            $this->logger->warning((string) $e->getCode());
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
            $status->setTranslatedReason($this->lng->txt(self::ERR_AUTH_FAILED));
            return false;
        }
    }

    // -----------------------------------------------------------------------
    // NEW: UserInfo merge helper - MinDefConnect / kalamun
    // -----------------------------------------------------------------------

    /**
     * Call the IdP's UserInfo endpoint and merge the returned claims on top
     * of $id_token_claims.  Returns the merged object.
     *
     * Failures are non-fatal: we log a warning and fall back to the
     * ID-token claims so that authentication still succeeds.
     *
     * @param  OpenIDConnectClient $oidc          Authenticated client instance.
     * @param  stdClass            $id_token_claims  Verified claims from getVerifiedClaims().
     * @return stdClass                           Merged claims object.
     */
    private function mergeUserInfoClaims(OpenIDConnectClient $oidc, stdClass $id_token_claims): stdClass
    {
        try {
            $this->logger->debug('Requesting additional claims from UserInfo endpoint.');
            $userinfo = $oidc->requestUserInfo();

            if (!is_object($userinfo)) {
                $this->logger->warning('UserInfo endpoint returned a non-object response; skipping merge.');
                return $id_token_claims;
            }

            $this->logger->dump($userinfo, ilLogLevel::DEBUG);

            // Merge: UserInfo claims win on conflict (OIDC Core §5.3.2).
            $merged = clone $id_token_claims;
            foreach ($userinfo as $key => $value) {
                $merged->$key = $value;
            }

            $this->logger->debug('UserInfo claims merged successfully.');
            return $merged;
        } catch (\Jumbojett\OpenIDConnectClientException $e) {
            // The IdP's discovery document may not advertise a userinfo_endpoint,
            // or the endpoint may be temporarily unavailable.
            $this->logger->warning(
                'UserInfo endpoint request failed (falling back to ID-token claims): '
                . $e->getMessage()
            );
            return $id_token_claims;
        } catch (Exception $e) {
            $this->logger->warning(
                'Unexpected error fetching UserInfo claims (falling back to ID-token claims): '
                . $e->getMessage()
            );
            return $id_token_claims;
        }
    }


    /**
     * @param stdClass $user_info
     */
    private function handleUpdate(ilAuthStatus $status, $user_info): ilAuthStatus
    {
        if (!is_object($user_info)) {
            $this->logger->error('Received invalid user credentials: ');
            $this->logger->dump($user_info, ilLogLevel::ERROR);
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
            $status->setReason(self::ERR_AUTH_WRONG_LOGIN);
            return $status;
        }

        $uid_field    = $this->settings->getUidField();
        $ext_account  = $user_info->{$uid_field} ?? '';

        if (!is_string($ext_account) || $ext_account === '') {
            $this->logger->error('Could not determine valid external account, value is empty or not a string.');
            $this->logger->dump($user_info, ilLogLevel::ERROR);
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
            $status->setReason(self::ERR_AUTH_WRONG_LOGIN);
            return $status;
        }

        $this->logger->debug('Authenticated external account: ' . $ext_account);

        $int_account = ilObjUser::_checkExternalAuthAccount(
            ilOpenIdConnectUserSync::AUTH_MODE,
            $ext_account
        );

        try {
            $sync = new ilOpenIdConnectUserSync($this->settings, $user_info);
            $sync->setExternalAccount($ext_account);
            $sync->setInternalAccount((string) $int_account);
            $sync->updateUser();

            $user_id = $sync->getUserId();
            ilSession::set('used_external_auth_mode', ilAuthUtils::AUTH_OPENID_CONNECT);
            $status->setAuthenticatedUserId($user_id);
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATED);
        } catch (ilOpenIdConnectSyncForbiddenException) {
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
            $status->setReason(self::ERR_AUTH_WRONG_LOGIN);
        }

        return $status;
    }

    private function initClient(bool $use_second_client = false): OpenIDConnectClient
    {
        // --- PATCH: Second Client ID ---
        $client_id = ($use_second_client && $this->settings->getSecondClientId() !== '')
            ? $this->settings->getSecondClientId()
            : $this->settings->getClientId();
        // --- END PATCH ---

        $oidc = new OpenIDConnectClient(
            $this->settings->getProvider(),
            $client_id,
            $this->settings->getSecret()
        );

        $oidc->setCodeChallengeMethod('S256');

        return $oidc;
    }

    // --- PATCH: Second Client ID helpers ---

    private function isExternalAccountAdmin(string $ext_account): bool
    {
        global $DIC;

        $int_account = ilObjUser::_checkExternalAuthAccount(
            ilOpenIdConnectUserSync::AUTH_MODE,
            $ext_account
        );

        if (!$int_account) {
            return false;
        }

        $user_id = ilObjUser::_lookupId($int_account);
        if (!$user_id) {
            return false;
        }

        return $DIC->rbac()->review()->isAssigned((int) $user_id, SYSTEM_ROLE_ID);
    }

    private function clearOidcSessionState(): void
    {
        unset(
            $_SESSION['openid_connect_nonce'],
            $_SESSION['openid_connect_state'],
            $_SESSION['openid_connect_code_verifier']
        );
    }

    // --- END PATCH ---
}
