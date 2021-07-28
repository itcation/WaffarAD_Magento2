<?php


namespace WaffarAD\Magento2\Service;


use Magento\Framework\HTTP\Client\Curl;

class CurlService
{
    const API_URL = 'https://conversion.waffarx.com/magento/';
    const CONTENT_TYPE = 'application/json';
    /**
     * @var Curl
     */
    private $curl;

    /**
     * CurlService constructor.
     * @param Curl $curl
     */
    public function __construct(
        Curl $curl
    ) {
        $this->curl = $curl;
    }

    /**
     * @param array $params
     * @param string $method
     */
    protected function sendData(array $params, string $method): void
    {
        $this->getCurlClient()->addHeader('Content-Type', self::CONTENT_TYPE);
        $this->getCurlClient()->post($this->getRequestUri($method), json_encode($params));
    }

    /**
     * @param array $params
     */
    public function sendAddOrder(array $params): void
    {
        $this->sendData($params, 'addOrder');
    }

    /**
     * @param string $method
     * @return string
     */
    protected function getRequestUri(string $method): string
    {
        return self::API_URL . $method;
    }

    /**
     * @return string
     */
    protected function getApiUrl(): string
    {
        return self::API_URL;
    }

    /**
     * @return Curl
     */
    protected function getCurlClient(): Curl
    {
        return $this->curl;
    }
}
