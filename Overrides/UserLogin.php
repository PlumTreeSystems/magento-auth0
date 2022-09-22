<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PTS\Auth0\Overrides;

use Magento\Framework\Url;

/**
 * @api
 * @since 100.0.2
 */
class UserLogin extends \Magento\Backend\Controller\Adminhtml\Auth\Login
{
    private Url $frontendUrl;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        Url $frontendUrl
    ) {
        $this->frontendUrl = $frontendUrl;
        parent::__construct($context, $resultPageFactory);
    }

    /**
     * Administrator login action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        return $this->resultRedirectFactory->create()->setUrl(
            $this->frontendUrl->getUrl(
                'auth/account/authenticate',
                ['_query' => ['client' => 'user']]
            )
        );
    }
}
