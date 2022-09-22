<?php

namespace PTS\Auth0\Helper;

use Magento\Backend\Model\Session\AdminConfig;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Security\Model\AdminSessionsManager;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\DeploymentConfig;
use Magento\User\Model\User;

class AdminAuthentication
{
    private AuthSession $authSession;
    private CookieManagerInterface $cookieManager;
    private AdminConfig $sessionConfig;
    private CookieMetadataFactory $cookieMetadata;
    private AdminSessionsManager $adminSessionManager;
    private DeploymentConfig $config;
    private ManagerInterface $messageManager;

    public function __construct(
        AuthSession $authSession,
        CookieManagerInterface $cookieManager,
        AdminConfig $sessionConfig,
        CookieMetadataFactory $cookieMetadata,
        AdminSessionsManager $adminSessionManager,
        DeploymentConfig $config,
        ManagerInterface $messageManager
    ) {
        $this->authSession = $authSession;
        $this->cookieManager = $cookieManager;
        $this->sessionConfig = $sessionConfig;
        $this->cookieMetadata = $cookieMetadata;
        $this->adminSessionManager = $adminSessionManager;
        $this->config = $config;
        $this->messageManager = $messageManager;
    }

    public function processLogin(User $user)
    {
        $this->authSession->setUser($user);
        $this->authSession->processLogin();
        
        $errorMessage = '';
        if ($user !== null) {
            if ((int)$user->getIsActive() !== 1) {
                $errorMessage = 'The account sign-in was incorrect or your account is disabled temporarily. '
                    . 'Please wait and try again later.';
            }
            if (!$user->hasAssigned2Role($user->getId())) {
                $errorMessage = 'More permissions are needed to access this.';
            }

            if (!empty($errorMessage)) {
                $this->authSession->destroy();
                $this->messageManager->addErrorMessage(__($errorMessage));
                return false;
            }
        }

        if ($this->authSession->isLoggedIn()) {
            if ($cookieValue = $this->authSession->getSessionId()) {
                $this->authSession->setUpdatedAt(time());
                $cookieMetadata = $this->cookieMetadata->createPublicCookieMetadata()
                    ->setDuration($this->config->get('auth0/tokenExpiration'))
                    ->setPath($this->getAdminCookiePath())
                    ->setDomain($this->sessionConfig->getCookieDomain())
                    ->setSecure($this->sessionConfig->getCookieSecure())
                    ->setHttpOnly($this->sessionConfig->getCookieHttpOnly())
                    ->setSameSite($this->sessionConfig->getCookieSameSite());
                $this->cookieManager->setPublicCookie($this->sessionConfig->getName(), $cookieValue, $cookieMetadata);
                $this->adminSessionManager->processLogin();
            }
        }

        return true;
    }

    private function getAdminCookiePath()
    {
        return '/' . $this->config->get('backend/frontName');
    }
}
