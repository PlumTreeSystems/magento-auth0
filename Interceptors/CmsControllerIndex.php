<?php

namespace PTS\Auth0\Interceptors;

use Magento\Cms\Controller\Index\Index;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Store\Model\StoreManagerInterface;

class CmsControllerIndex
{
    private StoreManagerInterface $storeManager;
    private RedirectFactory $redirect;
    private Request $request;

    public function __construct(
        StoreManagerInterface $storeManager,
        RedirectFactory $redirect,
        Request $request
    ) {
        $this->storeManager = $storeManager;
        $this->redirect = $redirect;
        $this->request = $request;
    }

    public function afterExecute(Index $subject, $result)
    {
        $request = $this->request->getParams();
        if (isset($request['retailerLogin']) && $request['retailerLogin']) {
            $redirect = $this->redirect->create();
            return $redirect->setUrl(
                $this->storeManager->getStore()->getUrl(
                    'auth/account/authenticate',
                    ['_query' => ['client' => 'customer']]
                )
            );
        }
        return $result;
    }
}
