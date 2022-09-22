<?php

namespace PTS\Auth0\Controller\Customer;

use Magento\Customer\Api\SessionCleanerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

class Logout implements HttpGetActionInterface
{
    private CookieManagerInterface $cookieManager;

    private Session $session;

    private CookieMetadataFactory $cookieMetadataFactory;

    private SessionCleanerInterface $sessionCleaner;

    private RedirectInterface $redirect;

    private RedirectFactory $resultRedirectFactory;

    private StoreManagerInterface $storeManager;

    public function __construct(
        CookieManagerInterface $cookieManager,
        Session $session,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionCleanerInterface $sessionCleaner,
        RedirectInterface $redirect,
        RedirectFactory $resultRedirectFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->cookieManager = $cookieManager;
        $this->session = $session;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionCleaner = $sessionCleaner;
        $this->redirect = $redirect;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        $lastCustomerId = $this->session->getId();
        $this->session->logout()->setBeforeAuthUrl($this->redirect->getRefererUrl())
            ->setLastCustomerId($lastCustomerId);
        $this->sessionCleaner->clearFor((int)$lastCustomerId);
        if ($this->cookieManager->getCookie('mage-cache-sessid')) {
            $metadata = $this->cookieMetadataFactory->createCookieMetadata();
            $metadata->setPath('/');
            $this->cookieManager->deleteCookie('mage-cache-sessid', $metadata);
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setUrl($this->storeManager->getStore()->getBaseUrl());
    }
}
