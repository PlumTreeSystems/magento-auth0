<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PTS\Auth0\Overrides;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use PTS\Auth0\Helper\Auth0;

/**
 * Sign out a customer.
 */
class CustomerLogout extends \Magento\Customer\Controller\Account\Logout
{
    private Auth0 $auth0;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param Auth0 $auth0
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        Auth0 $auth0
    ) {
        $this->auth0 = $auth0;
        parent::__construct($context, $customerSession);
    }

    /**
     * Customer logout action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setUrl(
            $this->auth0->sdk->logout(
                $this->_url->getUrl('auth/account/logout')
                . '?redirectUri='
                . $this->_url->getUrl('auth/customer/logout')
            )
        );
    }
}
