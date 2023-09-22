<?php
/*******************************************************
 * Copyright (C) 2021 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Controller\Adminhtml\Ajax;


use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use LaPoste\Colissimo\Logger\Colissimo;
use LaPoste\Colissimo\Model\Carrier\CustomsDocumentsApi;
use Magento\Sales\Api\ShipmentRepositoryInterface;


class sendCustomsDocuments extends Action
{
    protected $logger;
    private $customsDocumentsApi;
    private $shipmentRepository;

    /**
     * SendCustomsDocuments constructor.
     *
     * @param Context             $context
     * @param Colissimo           $logger
     * @param CustomsDocumentsApi $customsDocumentsApi
     */
    public function __construct(
        Context $context,
        Colissimo $logger,
        CustomsDocumentsApi $customsDocumentsApi,
        ShipmentRepositoryInterface $shipmentRepository
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->customsDocumentsApi = $customsDocumentsApi;
        $this->shipmentRepository = $shipmentRepository;
    }

    /**
     * Ajax request
     *
     * @return void
     */
    public function execute()
    {
        $request = $this->getRequest();
        if (!$request->isAjax()) {
            return;
        }

        $shipmentId = $request->getParam('shipment_id');
        $documentsPerLabel = $request->getFiles('lpc__customs_document');
        $data = [
            'sentDocuments' => [],
            'errors'        => [],
        ];
        foreach ($documentsPerLabel as $parcelNumber => $documentTypes) {
            $shipment = $this->shipmentRepository->get($shipmentId);
            $data['sentDocuments'][$parcelNumber] = $shipment->getDataUsingMethod('lpc_label_docs');
            if (empty($data['sentDocuments'][$parcelNumber])) {
                $data['sentDocuments'][$parcelNumber] = [];
            } else {
                $data['sentDocuments'][$parcelNumber] = json_decode($data['sentDocuments'][$parcelNumber], true);
            }

            foreach ($documentTypes as $documentType => $documents) {
                foreach ($documents as $oneDocument) {
                    try {
                        $documentId = $this->customsDocumentsApi->storeDocument(
                            $documentType,
                            $parcelNumber,
                            $oneDocument['tmp_name'],
                            $oneDocument['name']
                        );

                        // Old version of API maybe, keep this test unless sure the extension isn't in the doc ID anymore
                        $dotPosition = strrpos($documentId, '.');
                        if (!empty($dotPosition)) {
                            $documentId = substr($documentId, 0, $dotPosition);
                        }

                        $data['sentDocuments'][$parcelNumber][$documentId] = [
                            'documentName' => $oneDocument['name'],
                            'documentType' => $documentType,
                        ];
                    } catch (\Exception $e) {
                        $data['errors'][] = $e->getMessage();

                        $this->logger->error(sprintf(__('Error sending the customs document %1$s for the parcel number %2$s'), $oneDocument['name'], $parcelNumber), [$e]);
                    }
                }
            }

            $shipment->setDataUsingMethod(
                'lpc_label_docs',
                empty($data['sentDocuments'][$parcelNumber]) ? null : json_encode($data['sentDocuments'][$parcelNumber])
            );
            $shipment->save();
        }

        $this->getResponse()
             ->representJson(
                 json_encode(
                     [
                         'type' => empty($data['errors']) ? 'success' : 'error',
                         'data' => $data,
                     ]
                 )
             );
    }
}
