<?php

namespace PTS\Auth0\Controller\Account;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Store\Model\StoreManagerInterface;
use PTS\Auth0\Helper\Auth0;

class Authenticate implements HttpGetActionInterface
{
    private RedirectFactory $resultRedirectFactory;
    private Auth0 $auth0;
    private Request $request;
    private StoreManagerInterface $storeManager;

    public function __construct(
        RedirectFactory $resultRedirectFactory,
        Auth0 $auth0,
        Request $request,
        StoreManagerInterface $storeManager
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->auth0 = $auth0;
        $this->request = $request;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        $this->auth0->sdk->clear();

        $redirectUri = $this->request->getParam('redirectUri');

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($this->auth0->sdk->login(
            $this->storeManager->getStore()->getBaseUrl()
            . 'auth/account/login/?client='
            . $this->request->getParam('client')
            . ($redirectUri ? '&redirectUri=' . $redirectUri : '')
        ));

        return $resultRedirect;
    }
}
