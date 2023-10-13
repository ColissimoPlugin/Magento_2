<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Controller\Adminhtml\Shipment;


use LaPoste\Colissimo\Logger\Colissimo;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Event\Manager;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order\ShipmentRepository;

class EmailReturnLabel extends \Magento\Backend\App\Action
{
    /**
     * @var \LaPoste\Colissimo\Logger\Colissimo
     */
    protected $logger;
    /**
     * @var \Magento\Sales\Model\Order\ShipmentRepository
     */
    protected $shipmentRepository;
    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $eventManager;

    /**
     * EmailReturnLabel constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param \LaPoste\Colissimo\Logger\Colissimo $logger
     * @param \Magento\Sales\Model\Order\ShipmentRepository $shipmentRepository
     */
    public function __construct(
        Context $context,
        Colissimo $logger,
        Manager $eventManager,
        ShipmentRepository $shipmentRepository
    ) {
        parent::__construct($context);

        $this->logger = $logger;
        $this->eventManager = $eventManager;
        $this->shipmentRepository = $shipmentRepository;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $shipmentId = $this->getRequest()->getParam('shipment_id');

        if (null === $shipmentId) {
            return $this->onError('Shipment not found');
        }

        try {
            /** @var \Magento\Sales\Model\Order\Shipment $shipment */
            $shipment = $this->shipmentRepository->get($shipmentId);
        } catch (InputException $e) {
            return $this->onError($e->getMessage());
        } catch (NoSuchEntityException $e) {
            return $this->onError($e->getMessage());
        }

        $label = $shipment->getDataUsingMethod('lpc_return_label');
        if (!$label) {
            return $this->onError('Please generate return label');
        }
        $orderId = $shipment->getOrder()->getId();

        $this->eventManager->dispatch(
            'lpc_generate_inward_label_after',
            [
                'orderIds' => [$orderId],
                'label'    => $label,
                'force'    => true,
            ]
        );

        $this->messageManager->addSuccessMessage(__('Return label was sent'));

        return $this->_redirect('laposte_colissimo/shipment/index');
    }

    /**
     * @param string $message
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function onError($message = '')
    {
        $message = __("Can't send return label:") . ' ' . __($message);

        $this->logger->error($message);
        $this->messageManager->addErrorMessage($message);

        return $this->_redirect('laposte_colissimo/shipment/index');
    }
}
