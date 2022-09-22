<?php

namespace PTS\Auth0\Overrides;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use PTS\Auth0\Helper\Auth0;
use Magento\Store\Model\StoreManagerInterface;

class UserLogout implements HttpGetActionInterface
{
    private Auth0 $auth0;
    
    private RedirectFactory $redirectFactory;

    private StoreManagerInterface $storeManager;

    public function __construct(
        Auth0 $auth0,
        RedirectFactory $redirectFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->auth0 = $auth0;
        $this->redirectFactory = $redirectFactory;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        $resultRedirect = $this->redirectFactory->create();
        $resultRedirect->setUrl(
            $this->auth0->sdk->logout(
                $this->storeManager->getStore()->getUrl('auth/account/logout')
                . '?redirectUri='
                . $this->storeManager->getStore()->getUrl('auth/user/logout')
            )
        );
        return $resultRedirect;
    }
}
