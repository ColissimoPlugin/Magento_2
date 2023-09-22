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


use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Logger\Colissimo;
use LaPoste\Colissimo\Model\Mail\Template\TransportBuilder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class SendReturnLabelEmail implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \LaPoste\Colissimo\Helper\Data
     */
    protected $helperData;
    /**
     * @var \LaPoste\Colissimo\Logger\Colissimo
     */
    protected $logger;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;
    /**
     * @var \LaPoste\Colissimo\Model\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;
    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * SendReturnLabelEmail constructor.
     * @param \LaPoste\Colissimo\Helper\Data                             $helperData
     * @param \LaPoste\Colissimo\Logger\Colissimo                        $logger
     * @param \LaPoste\Colissimo\Model\Mail\Template\TransportBuilder    $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface         $inlineTranslation
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     */
    public function __construct(
        Data $helperData,
        Colissimo $logger,
        StateInterface $inlineTranslation,
        TransportBuilder $transportBuilder,
        CollectionFactory $orderCollectionFactory
    ) {
        $this->logger = $logger;
        $this->helperData = $helperData;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $force = $observer->getData('force');

        if ($force !== null && $force !== true ||
            !$this->helperData->getAdvancedConfigValue('lpc_return_labels/sendReturnLabelEmail')) {
            return;
        }

        $orderIds = $observer->getData('orderIds');
        $label = $observer->getData('label');
        if (null === $orderIds ||
            !is_array($orderIds)) {
            $this->logger->error(__("Can't send return label email: order not found"));

            return;
        }
        if (null === $label) {
            $this->logger->error(__("Can't send return label email: label not found"));

            return;
        }

        // This seems to be necessary
        try {
            $pdf = \Zend_Pdf::parse($label)->render();
        } catch (\Zend_Pdf_Exception $e) {
            $this->logger->error('Error while sending return label email : ' . $e->getMessage());

            return;
        }

        $orderCollection = $this->orderCollectionFactory
            ->create()
            ->addFieldToFilter('entity_id', $orderIds);

        $this->inlineTranslation->suspend();

        /** @var \Magento\Sales\Model\Order $order */
        foreach ($orderCollection->getItems() as $order) {
            $storeId = $order->getStoreId();
            $orderId = $order->getId();
            $recipientEmail = $order->getCustomerEmail();
            $recipientName = $order->getCustomerName();
            $senderName = $this->helperData->getConfigValue('trans_email/ident_sales/name', $storeId);
            $senderEmail = $this->helperData->getConfigValue('trans_email/ident_sales/email', $storeId);
            try {
                $this->transportBuilder
                    ->setTemplateIdentifier('lpc_order_return_label')
                    ->setTemplateOptions([
                                             'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                                             'store' => $storeId,
                                         ])->setTemplateVars([
                                                                 'customerName' => $recipientEmail,
                                                                 'orderNumber'  => $orderId,
                                                                 'createdAt'    => $order->getCreatedAt(),
                                                             ])->setFrom([
                                                                             'email' => $senderEmail,
                                                                             'name'  => $senderName,
                                                                         ])->addTo($recipientEmail, $recipientName)
                    ->addPdfAttachment(
                        $pdf,
                        __('Return_Label_Order_%1', $orderId) . '.pdf'
                    )->getTransport()
                    ->sendMessage();
            } catch (\Exception $e) {
                $this->logger->error('Error while sending return label email : ' . $e->getMessage());
            }
        }
        $this->inlineTranslation->resume();
    }
}
