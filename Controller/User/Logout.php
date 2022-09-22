<?php

namespace PTS\Auth0\Controller\User;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Backend\Model\Auth;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

class Logout implements HttpGetActionInterface
{
    private RedirectFactory $resultRedirectFactory;

    private Auth $auth;

    private Session $session;

    private ManagerInterface $messageManager;

    private StoreManagerInterface $storeManager;

    public function __construct(
        RedirectFactory $resultRedirectFactory,
        Auth $auth,
        Session $session,
        ManagerInterface $messageManager,
        StoreManagerInterface $storeManager
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->auth = $auth;
        $this->session = $session;
        $this->messageManager = $messageManager;
        $this->storeManager = $storeManager;
    }

    /**
     * Administrator logout action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $this->auth->logout();
        $this->session->unsetAll();

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setUrl($this->storeManager->getStore()->getBaseUrl());
    }
}
