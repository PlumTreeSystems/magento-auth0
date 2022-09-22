<?php

namespace PTS\Auth0\Controller\Account;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\Controller\Result\RedirectFactory;

class Logout implements HttpGetActionInterface
{
    private RedirectFactory $resultRedirectFactory;
    private Request $request;

    public function __construct(
        RedirectFactory $resultRedirectFactory,
        Request $request
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->request = $request;
    }

    public function execute()
    {
        $redirectUri = $this->request->getParam('redirectUri');

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($redirectUri);

        return $resultRedirect;
    }
}
