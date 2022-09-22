<?php

namespace PTS\Auth0\Block;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class UserAuth extends Template
{
    private UrlInterface $url;

    public function __construct(
        Context $context,
        UrlInterface $url
    ) {
        parent::__construct($context);
        $this->url = $url;
    }

    public function getUserAuthUrl()
    {
        return $this->url->getRouteUrl('adminhtml');
    }
}
