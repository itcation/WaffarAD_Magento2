<?php


namespace WaffarAD\Magento2\Observer;


use WaffarAD\Magento2\Service\CurlService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class SendOrder implements ObserverInterface
{

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var CurlService
     */
    private $curlService;
    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;
    /**
     * @var UrlInterface
     */
    private $url;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;
    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    const XML_MODULE_ENABLED = 'waffarad/general/enabled';

    /**
     * SendOrder constructor.
     * @param RemoteAddress $remoteAddress
     * @param StoreManagerInterface $storeManager
     * @param CurlService $curlService
     * @param CookieManagerInterface $cookieManager
     * @param UrlInterface $url
     * @param LoggerInterface $logger
     * @param SessionManagerInterface $sessionManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     */
    public function __construct(
        RemoteAddress $remoteAddress,
        StoreManagerInterface $storeManager,
        CurlService $curlService,
        CookieManagerInterface $cookieManager,
        UrlInterface $url,
        LoggerInterface $logger,
        SessionManagerInterface $sessionManager,
        CookieMetadataFactory $cookieMetadataFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->storeManager = $storeManager;
        $this->curlService = $curlService;
        $this->cookieManager = $cookieManager;
        $this->url = $url;
        $this->logger = $logger;
        $this->sessionManager = $sessionManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->scopeConfig = $scopeConfig;
    }
    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if(!$this->scopeConfig->getValue(self::XML_MODULE_ENABLED)) {
            return $this;
        }
        /**
         * @var $order OrderInterface
         */
        $afId = $this->cookieManager->getCookie('af_id');
        $subId = $this->cookieManager->getCookie('subid');
        if ($afId && $subId) {
            try {
                $order = $observer->getOrder();
                $baseUrl = $this->storeManager->getStore()->getBaseUrl();
                $current_url = $this->url->getUrl('checkout/onepage/success');

                $affiliateData = array(
                    "order_id" => $order->getIncrementId(),
                    "ip" => $this->remoteAddress->getRemoteAddress(),
                    "order_currency" => $order->getOrderCurrencyCode(),
                    "order_total" => $order->getGrandTotal(),
                    "af_id" => $afId,
                    "subid" => $subId,
                    "base_url" => base64_encode($baseUrl),
                    "current_page_url" => base64_encode($current_url),
                    "script_name" => "magento",
                );

                foreach ($order->getAllVisibleItems() as $item) {
                    $affiliateData['product_ids'][] = $item->getSku();
                }

                $this->curlService->sendAddOrder($affiliateData);

                $metadata = $this->cookieMetadataFactory->createCookieMetadata()
                    ->setPath($this->sessionManager->getCookiePath());

                $this->cookieManager->deleteCookie('af_id', $metadata);
                $this->cookieManager->deleteCookie('subid', $metadata);
            } catch (NoSuchEntityException | InputException | FailureToSendException $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);
            }
        }
    }
}
