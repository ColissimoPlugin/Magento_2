<?php

namespace LaPoste\Colissimo\Controller\Adminhtml\Orders;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\Order;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use LaPoste\Colissimo\Model\Carrier\Colissimo;
use LaPoste\Colissimo\Logger\Colissimo as LoggerColissimo;
use LaPoste\Colissimo\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use LaPoste\Colissimo\Helper\CountryOffer;

abstract class ChangeShipment extends Action
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var LoggerColissimo
     */
    protected $logger;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var CountryOffer
     */
    protected $countryOffer;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    private $ordersListing = 'sales/order/';

    /**
     * @param Context               $context
     * @param Filter                $filter
     * @param CollectionFactory     $collectionFactory
     * @param LoggerColissimo       $logger
     * @param Data                  $helperData
     * @param StoreManagerInterface $storeManager
     * @param CountryOffer          $countryOffer
     * @param QuoteFactory          $quoteFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        LoggerColissimo $logger,
        Data $helperData,
        StoreManagerInterface $storeManager,
        CountryOffer $countryOffer,
        QuoteFactory $quoteFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
        $this->helperData = $helperData;
        $this->storeManager = $storeManager;
        $this->countryOffer = $countryOffer;
        $this->quoteFactory = $quoteFactory;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return Redirect
     * @throws LocalizedException|Exception
     */
    public function executeShipmentModification($shippingMethodCode)
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (!in_array($shippingMethodCode, Colissimo::METHODS_CODES)) {
            return $resultRedirect->setPath($this->ordersListing);
        }

        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->count();

        if (empty($collectionSize)) {
            return $resultRedirect->setPath($this->ordersListing);
        }

        $changed = 0;
        $newMethodCode = Colissimo::CODE . '_' . $shippingMethodCode;
        $newMethodName = __(Colissimo::METHODS_CODES_TRANSLATIONS[$shippingMethodCode]);

        $availableCountriesPerStore = [];

        /** @var Order $order */
        foreach ($collection->getItems() as $order) {
            $storeId = $order->getStoreId();
            if (!isset($availableCountriesPerStore[$storeId])) {
                $thisStoreOriginCountryId = $this->helperData->getConfigValue('shipping/origin/country_id', $storeId);
                $availableCountriesPerStore[$storeId] = $this->countryOffer->getCountriesForMethod($shippingMethodCode, $thisStoreOriginCountryId);
            }

            // If the shipping country isn't allowed for this shipping method, add warning and skip it
            if (!in_array($order->getShippingAddress()->getCountryId(), $availableCountriesPerStore[$storeId])) {
                $this->messageManager->addErrorMessage(
                    sprintf(
                        __('The order #%1$d cannot be shipped with %2$s'),
                        $order->getId(),
                        $newMethodName
                    )
                );

                continue;
            }

            $currentShippingMethod = $order->getShippingMethod();

            if ($currentShippingMethod === Colissimo::CODE . '_' . Colissimo::CODE_SHIPPING_METHOD_RELAY) {
                $this->messageManager->addWarningMessage(
                    sprintf(
                        __('The order #%d was made to be shipped to a relay point, make sure to change its shipping address!'),
                        $order->getId()
                    )
                );
            } elseif ($currentShippingMethod === $newMethodCode) {
                continue;
            }

            // Change the shipping method on the order (shown on the orders listing and used when generating labels)
            $newMethodDescription = 'Colissimo - ' . $newMethodName;
            $order->setShippingMethod($newMethodCode)->setShippingDescription($newMethodDescription)->save();

            // Change the shipping method on the order's quote (shown on the shipment page)
            $quote = $this->quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());
            $quote->getShippingAddress()->setShippingMethod($newMethodCode)->setShippingDescription($newMethodDescription)->save();

            $changed ++;
        }

        if (!empty($changed)) {
            $this->messageManager->addSuccessMessage(
                sprintf(
                    __('Shipping method changed to %1$s for %2$d orders.'),
                    $newMethodName,
                    $collectionSize
                )
            );
        }

        return $resultRedirect->setPath($this->ordersListing);
    }
}
