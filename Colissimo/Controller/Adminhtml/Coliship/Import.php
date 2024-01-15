<?php


/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Controller\Adminhtml\Coliship;


use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Helper\Shipment;
use LaPoste\Colissimo\Logger\Colissimo;
use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Event\Manager;
use Magento\Framework\File\Csv;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Import extends Action
{
    const FILE_DELIMITER = ',';
    const FILE_ENCLOSURE = '"';
    const INDEX_ORDERID = 1;
    const INDEX_TRACKING_NUMBER = 0;
    const TRACKING_DATA = [
        'carrier_code' => \LaPoste\Colissimo\Model\Carrier\Colissimo::CODE,
        'title'        => 'ColiShip import',
    ];

    /**
     * @var Data
     */
    protected $helperData;
    /**
     * @var Csv
     */
    protected $csvParser;
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var \Magento\Sales\Model\Convert\Order
     */
    protected $convertOrder;
    /**
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    protected $trackFactory;
    /**
     * @var \Laposte\Colissimo\Helper\Shipment
     */
    protected $shipmentHelper;
    /**
     * @var \LaPoste\Colissimo\Logger\Colissimo
     */
    protected $logger;
    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $eventManager;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Import constructor.
     *
     * @param \Magento\Backend\App\Action\Context              $context
     * @param \LaPoste\Colissimo\Helper\Data                   $helperData
     * @param \Magento\Framework\File\Csv                      $csvParser
     * @param \Magento\Sales\Api\OrderRepositoryInterface      $orderRepository
     * @param \LaPoste\Colissimo\Helper\Shipment               $shipmentHelper
     * @param \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory
     * @param \LaPoste\Colissimo\Logger\Colissimo              $logger
     * @param \Magento\Framework\Event\Manager                 $eventManager
     * @param \Magento\Framework\Api\SearchCriteriaBuilder     $searchCriteriaBuilder
     */
    public function __construct(
        Action\Context $context,
        Data $helperData,
        Csv $csvParser,
        OrderRepositoryInterface $orderRepository,
        Shipment $shipmentHelper,
        TrackFactory $trackFactory,
        Colissimo $logger,
        Manager $eventManager,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($context);
        $this->helperData = $helperData;
        $this->csvParser = $csvParser;
        $this->orderRepository = $orderRepository;
        $this->shipmentHelper = $shipmentHelper;
        $this->trackFactory = $trackFactory;
        $this->logger = $logger;
        $this->eventManager = $eventManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;

        $this->csvParser->setDelimiter(self::FILE_DELIMITER);
        $this->csvParser->setEnclosure(self::FILE_ENCLOSURE);
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
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->getRequest();
        $importFile = $request->getFiles('import_file');
        $validOrderIds = [];
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath(
            $this->helperData->getAdminRoute('coliship', 'index')
        );

        if (empty($importFile['type']) || 'text/csv' !== $importFile['type']) {
            $this->onError(__('File missing or wrong format (CSV expected)'));

            return $resultRedirect;
        }

        try {
            $content = $this->csvParser->getData($importFile['tmp_name']);
        } catch (\Exception $e) {
            $this->onError($e->getMessage());

            return $resultRedirect;
        }

        $trackingNumbers = [];
        foreach ($content as $line) {
            if (count($line) <= \max(self::INDEX_TRACKING_NUMBER, self::INDEX_ORDERID)) {
                $this->logger->warning(
                    __('The following line does not have the correct fields'),
                    $line
                );
                continue;
            }

            if ('NumeroColis' === $line[self::INDEX_TRACKING_NUMBER]) {
                continue;
            }

            $orderIncrementId = $line[self::INDEX_ORDERID];
            $trackingNumber = $line[self::INDEX_TRACKING_NUMBER];

            $this->searchCriteriaBuilder->addFilter('increment_id', $orderIncrementId);

            $order = $this->orderRepository->getList(
                $this->searchCriteriaBuilder->create()
            )->getItems();

            if (count($order) != 1) {
                $this->onError(sprintf(__("Cannot find order %s"), $orderIncrementId));
                continue;
            }

            $order = array_shift($order);

            if (!$order->canShip()) {
                $this->logger->warning(
                    __('Cannot create shipment for order'),
                    [$orderIncrementId]
                );
                continue;
            }

            try {
                $shipment = $this->shipmentHelper->createShipment($order);
                $shipment = $this->addTrackingToShipment($shipment, $trackingNumber);

                // Save created shipment and order
                $shipment->save();
                $shipment->getOrder()->save();

                $validOrderIds[] = $orderIncrementId;
                $trackingNumbers[$orderIncrementId] = $trackingNumber;
            } catch (\Exception $e) {
                $this->onError($e->getMessage());
            }
        }

        if (!empty($validOrderIds)) {
            $this->eventManager->dispatch(
                'colissimo_coliship_import_after',
                [
                    'orderIds'     => $validOrderIds,
                    'parcelNumber' => $trackingNumbers,
                ]
            );
        }

        $this->messageManager->addSuccessMessage(__('Import is done'));

        return $resultRedirect;
    }


    /**
     * @param $message
     *
     * @return void
     */
    public function onError($message)
    {
        $this->messageManager->addErrorMessage(__('An error occurred during import: ', $message));
        $this->logger->error($message);
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param                                     $trackingNumber
     *
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function addTrackingToShipment(
        $shipment,
        $trackingNumber
    ) {
        $data = array_merge(
            self::TRACKING_DATA,
            ['number' => $trackingNumber]
        );

        $track = $this->trackFactory->create()->addData($data);
        $shipment->addTrack($track);

        return $shipment;
    }
}
