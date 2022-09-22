<?php

namespace PTS\Auth0\Helper;

use Auth0\SDK\Contract\StoreInterface;
use Magento\Customer\Model\Session;
use Psr\Log\LoggerInterface;

class SessionStorageAdapter implements StoreInterface
{
    private Session $session;
    private LoggerInterface $logger;

    public function __construct(Session $session, LoggerInterface $logger)
    {
        $this->session = $session;
        $this->logger = $logger;
    }

    public function set(string $key, $value): void
    {
        $this->session->setData($key, $value);
        $this->logger->info('Session set: ' . $key . ' ' . json_encode($value));
    }

    public function get(string $key, $default = null)
    {
        $this->session->getData($key, false);
        $this->logger->info('Session get: ' . $key);
    }

    public function delete(string $key): void
    {
        $this->session->getData($key, true);
    }

    public function purge(): void
    {
        $this->session->clearStorage();
    }

    public function defer(bool $deferring): void
    {
        $this->session->clearStorage();
    }
}
