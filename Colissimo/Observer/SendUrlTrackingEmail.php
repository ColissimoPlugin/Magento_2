<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Observer;

use LaPoste\Colissimo\Api\UnifiedTrackingApi;
use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Logger\Colissimo;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Url;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class SendUrlTrackingEmail implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Url
     */
    protected $url;
    /**
     * @var \LaPoste\Colissimo\Helper\Data
     */
    protected $helperData;
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;
    /**
     * @var \LaPoste\Colissimo\Api\UnifiedTrackingApi
     */
    protected $unifiedTrackingApi;
    /**
     * @var \LaPoste\Colissimo\Logger\Colissimo
     */
    protected $logger;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;
    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    private $inlineTranslation;
    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

    /**
     * SendUrlTrackingEmail constructor.
     *
     * @param \Magento\Framework\Url                                     $url
     * @param \LaPoste\Colissimo\Helper\Data                             $helperData
     * @param \Magento\Framework\Mail\Template\TransportBuilder          $transportBuilder
     * @param \LaPoste\Colissimo\Api\UnifiedTrackingApi                  $unifiedTrackingApi
     * @param \LaPoste\Colissimo\Logger\Colissimo                        $logger
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Framework\Translate\Inline\StateInterface         $inlineTranslation
     * @param \Magento\Directory\Model\CountryFactory                    $countryFactory
     */
    public function __construct(
        Url $url,
        Data $helperData,
        TransportBuilder $transportBuilder,
        UnifiedTrackingApi $unifiedTrackingApi,
        Colissimo $logger,
        CollectionFactory $orderCollectionFactory,
        StateInterface $inlineTranslation,
        CountryFactory $countryFactory
    ) {
        $this->url = $url;
        $this->helperData = $helperData;
        $this->transportBuilder = $transportBuilder;
        $this->unifiedTrackingApi = $unifiedTrackingApi;
        $this->logger = $logger;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->inlineTranslation = $inlineTranslation;
        $this->countryFactory = $countryFactory;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $sendTrackingEmailConfig = $this->helperData->getAdvancedConfigValue('lpc_labels/sendTrackingEmail');
        if (!$sendTrackingEmailConfig) {
            return;
        }

        $eventName = $observer->getEvent()->getName();
        if (in_array($eventName, ['colissimo_coliship_import_after', 'lpc_generate_outward_label_after']) && $sendTrackingEmailConfig != 1) {
            return;
        }
        if ('lpc_generate_bordereau_after' === $eventName && $sendTrackingEmailConfig != 2) {
            return;
        }

        $orderIds = $observer->getData('orderIds');
        $parcelNumbers = $observer->getData('parcelNumber');
        if (null === $orderIds ||
            !\is_array($orderIds) ||
            empty($orderIds)
        ) {
            $this->logger->error(__("Can't send tracking email: order not found"));

            return;
        }

        $orderCollection = $this->orderCollectionFactory
            ->create()
            ->addFieldToFilter('increment_id', $orderIds);

        /** @var \Magento\Sales\Model\Order $order */
        foreach ($orderCollection->getItems() as $order) {
            if (!$order instanceof \Magento\Sales\Model\Order) {
                $this->logger->error(__("Can't send tracking email: order not found"));
                continue;
            }
            $storeId = $order->getStoreId();
            $orderId = $order->getId();
            $recipientEmail = $order->getCustomerEmail();
            $recipientName = $order->getCustomerName();
            $senderName = $this->helperData->getConfigValue('trans_email/ident_sales/name', $storeId);
            $senderEmail = $this->helperData->getConfigValue('trans_email/ident_sales/email', $storeId);


            $parcelNumber = $parcelNumbers[$order->getIncrementId()];
            $shippingAddress = $order->getShippingAddress();

            $shippingAddressFormatted = $shippingAddress['firstname'] . ' ' . $shippingAddress['lastname'] . '<br />';
            $shippingAddressFormatted .= nl2br($shippingAddress['street']) . '<br />';
            $shippingAddressFormatted .= $shippingAddress['postcode'] . ' ' . $shippingAddress['city'];
            if (!empty($shippingAddress['region'])) {
                $shippingAddressFormatted .= '<br />' . $shippingAddress['region'];
            }
            $countryId = $shippingAddress['country_id'];
            $countryName = $this->countryFactory->create()->loadByCode($countryId)->getName();
            $shippingAddressFormatted .= '<br />' . $countryName;

            try {
                $this->inlineTranslation->suspend();
                $this->transportBuilder
                    ->setTemplateIdentifier('lpc_order_tracking')
                    ->setTemplateOptions([
                                             'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                                             'store' => $storeId,
                                         ])->setTemplateVars([
                                                                 'customerName'    => $recipientName,
                                                                 'orderNumber'     => $orderId,
                                                                 'createdAt'       => $order->getCreatedAt(),
                                                                 'trackingLink'    => $this->getTrackingActionUrl($orderId),
                                                                 'shippingAddress' => $shippingAddressFormatted,
                                                                 'parcelNumber'    => $parcelNumber,
                                                             ])->setFromByScope([
                                                                                    'email' => $senderEmail,
                                                                                    'name'  => $senderName,
                                                                                ])->addTo($recipientEmail, $recipientName)
                    ->getTransport()
                    ->sendMessage();
                $this->inlineTranslation->resume();
            } catch (\Exception $e) {
                $this->logger->error('Error while sending tracking email : ' . $e->getMessage());
            }
        }
    }

    /**
     * @param $orderId
     *
     * @return string
     */
    public function getTrackingActionUrl($orderId)
    {
        return $this->url->getUrl(
            'lpc/tracking',
            ['trackhash' => $this->unifiedTrackingApi->encrypt($orderId)]
        );
    }
}
