<?php

namespace PTS\Auth0\Helper;

use Auth0\SDK\Auth0 as SDKAuth0;
use Auth0\SDK\Utility\HttpResponse;
use Magento\Backend\Model\UrlInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Message\ManagerInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\App\DeploymentConfig;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\User\Model\ResourceModel\User;
use Magento\User\Model\UserFactory;
use PTS\Microstores\Helper\CookieManager;
use PTS\ShopfrontApi\Helper\CustomerManager;

class Auth0
{
    public SDKAuth0 $sdk;

    private ManagerInterface $messageManager;
    private Session $customerSession;
    private LoggerInterface $logger;
    private RedirectFactory $resultRedirectFactory;
    private StoreManagerInterface $storeManager;
    private UserFactory $userFactory;
    private User $userResource;
    private UrlInterface $url;
    private AdminAuthentication $adminAuthentication;
    private CollectionFactory $collection;
    private DeploymentConfig $config;
    private CookieManager $cookieManager;

    public function __construct(
        SessionStorageAdapter $sessionStorage,
        ManagerInterface $messageManager,
        Session $customerSession,
        LoggerInterface $logger,
        RedirectFactory $resultRedirectFactory,
        StoreManagerInterface $storeManager,
        UserFactory $userFactory,
        User $userResource,
        UrlInterface $url,
        AdminAuthentication $adminAuthentication,
        DeploymentConfig $config,
        CustomerManager $customerManager,
        CollectionFactory $collection,
        CookieManager $cookieManager
    ) {
        $this->sdk = new SDKAuth0([
            'domain' => $config->get('auth0/domain'),
            'clientId' => $config->get('auth0/clientId'),
            'clientSecret' => $config->get('auth0/clientSecret'),
            'cookieSecret' => $config->get('crypt/key'),
            'sessionStorage' => $sessionStorage
        ]);

        $this->messageManager = $messageManager;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->storeManager = $storeManager;
        $this->userFactory = $userFactory;
        $this->userResource = $userResource;
        $this->url = $url;
        $this->adminAuthentication = $adminAuthentication;
        $this->customerManager = $customerManager;
        $this->collection = $collection;
        $this->config = $config;
        $this->cookieManager = $cookieManager;
    }

    public function authenticateCustomerAccount(?string $redirectUri)
    {
        if ($redirectUri) {
            $this->logger->info("Auth0: Redirecting to $redirectUri after authentication.");
        }
        $user = $this->processAuth0();
        $resultRedirect = $this->resultRedirectFactory->create();
        $store = $this->storeManager->getStore();

        if (!$user) {
            $this->messageManager->addErrorMessage(__('Authentication failed. Please try again.'));

            if (!$redirectUri) {
                $redirectUri = 'customer/account/login';
            }

            return $resultRedirect->setUrl(
                $this->sdk->logout(
                    $store->getUrl('auth/account/logout')
                    . '?redirectUri='
                    . $store->getUrl($redirectUri)
                )
            );
        }

        if (isset($user['app_metadata']['enrolleeId'])
            && ($enrolleeId = $user['app_metadata']['enrolleeId'])
        ) {
            $customer = $this->collection->create()
                ->addAttributeToSelect('enrollee_id')
                ->addAttributeToSelect('domain')
                ->addAttributeToFilter('enrollee_id', $enrolleeId)
                ->getFirstItem();

            if ($customer && $customer->getId()) {
                $this->customerSession->setCustomerAsLoggedIn($customer);
                $this->cookieManager->setMicrostore($customer->getDomain());
                return $resultRedirect->setUrl(
                    'https://'
                    . $customer->getDomain()
                    . '.'
                    . $this->config->get('domain')
                    . '/'
                    . ($redirectUri ?? '')
                );
            } else {
                $this->messageManager->addErrorMessage(__('Authentication failed. Please try again.'));
                $this->logger->error("Auth0 authentication failed. Error: Retailer (id:$enrolleeId) does not exist.");

                if (!$redirectUri) {
                    $redirectUri = 'customer/account/login';
                }

                return $resultRedirect->setUrl(
                    $this->sdk->logout(
                        $store->getUrl('auth/account/logout')
                        . '?redirectUri='
                        . $store->getUrl($redirectUri)
                    )
                );
            }
        } else {
            $this->messageManager->addErrorMessage(__('Authentication failed. Please try again.'));
            $this->logger->error(
                'Auth0 authentication failed. Error: app_metadata.enrolleeId value is null for user ' . $user['email']
            );

            if (!$redirectUri) {
                $redirectUri = 'customer/account/login';
            }

            return $resultRedirect->setUrl(
                $this->sdk->logout(
                    $store->getUrl('auth/account/logout')
                    . '?redirectUri='
                    . $store->getUrl($redirectUri)
                )
            );
        }
    }

    public function authenticateUserAccount()
    {
        $user = $this->processAuth0();
        $resultRedirect = $this->resultRedirectFactory->create();
        $store = $this->storeManager->getStore();

        if (!$user) {
            $this->messageManager->addErrorMessage(__('Authentication failed.'));
            return $resultRedirect->setUrl($this->sdk->logout($store->getUrl('auth/user/logout')));
        }

        $adminUser = $this->userFactory->create();
        $this->userResource->load($adminUser, $user['email'], 'email');

        if ($adminUser->getId()) {
            $process = $this->adminAuthentication->processLogin($adminUser);
            if ($process) {
                return $resultRedirect->setUrl($this->url->getUrl('admin/dashboard/index'));
            } else {
                return $resultRedirect->setUrl($this->sdk->logout($store->getUrl('auth/user/logout')));
            }
        } else {
            $this->messageManager->addErrorMessage(__('Authentication failed.'));
            $this->logger->error('Auth0 authentication failed. Error: User with email ' . $user['email'] . ' does not exist.');
            return $resultRedirect->setUrl($this->sdk->logout($store->getUrl('auth/user/logout')));
        }
    }

    private function processAuth0()
    {
        if ($exchangeParameters = $this->sdk->getExchangeParameters()) {
            try {
                $this->logger->info('Auth0: exchange parameters: ' . json_encode($exchangeParameters));
                $this->sdk->exchange($this->storeManager->getStore()->getBaseUrl() . 'auth/account/login/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage('There was an error trying to log in.');
                $this->logger->error('Auth0 authentication failed. Error: ' . $e->getMessage());
                return null;
            }
        }

        if ($this->sdk->getIdToken() === null) {
            $this->messageManager->addErrorMessage(__('Authentication failed.'));
            return null;
        }
        $token = $this->sdk->decode($this->sdk->getIdToken());

        $resp = $this->sdk->management()->users()->get($token->getSubject());
        if (!HttpResponse::wasSuccessful($resp)) {
            $this->messageManager->addErrorMessage(__('Authentication failed.'));
            $err = HttpResponse::getContent($resp);
            $code = HttpResponse::getStatusCode($resp);
            $this->logger->error('Auth0 authentication failed. Error: ' . $err . ' (' . $code . ')');
            return null;
        }

        return HttpResponse::decodeContent($resp);
    }
}
