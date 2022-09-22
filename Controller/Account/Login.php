<?php

namespace PTS\Auth0\Controller\Account;

use Magento\Backend\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Store\Model\StoreManagerInterface;
use PTS\Auth0\Helper\Auth0;
use Magento\Framework\App\Request\Http as Request;

class Login implements HttpGetActionInterface
{
    private Auth0 $auth0;
    private Session $session;
    private StoreManagerInterface $storeManager;
    private RedirectFactory $redirectFactory;
    private Request $request;

    public function __construct(
        Auth0 $auth0,
        Session $session,
        StoreManagerInterface $storeManager,
        RedirectFactory $redirectFactory,
        Request $request
    ) {
        $this->auth0 = $auth0;
        $this->session = $session;
        $this->storeManager = $storeManager;
        $this->redirectFactory = $redirectFactory;
        $this->request = $request;
    }

    public function execute()
    {
        $client = $this->request->getParam('client');
        $redirectUri = $this->request->getParam('redirectUri');

        if ($client === 'customer') {
            return $this->auth0->authenticateCustomerAccount($redirectUri);
        } elseif ($client === 'user') {
            return $this->auth0->authenticateUserAccount();
        }

        return $this->redirectFactory->create()
            ->setUrl($this->storeManager->getStore()->getBaseUrl());
    }
}
