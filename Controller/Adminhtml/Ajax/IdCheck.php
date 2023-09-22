<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use LaPoste\Colissimo\Logger;
use LaPoste\Colissimo\Helper;
use LaPoste\Colissimo\Exception;
use LaPoste\Colissimo\Model\Carrier;

class IdCheck extends Action
{
    protected $logger;

    protected $helperData;

    protected $generateLabelPayload;

    protected $labellingApi;


    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        Helper\Data $helperData,
        Logger\Colissimo $logger,
        Carrier\GenerateLabelPayload $generateLabelPayload,
        Carrier\LabellingApi $labellingApi
    ) {
        parent::__construct($context);
        $this->helperData = $helperData;
        $this->logger = $logger;
        $this->generateLabelPayload = $generateLabelPayload;
        $this->labellingApi = $labellingApi;
    }

    /**
     * Ajax request
     *
     * @return void
     */
    public function execute()
    {
        if ($this->getRequest()->isAjax()) {
            try {
                $this->constructLabelPayload();

                $response = $this->labellingApi->checkGenerateLabel($this->generateLabelPayload);

                if ($response->messages[0]->id == 0) {
                    $isIdOk = true;
                } else {
                    $this->logger->error(__('Error during IDs checking') . ":" . $response->messages[0]->messageContent);
                    $isIdOk = false;
                }

                $result = $isIdOk ? __('Credentials are valid.') : __('Bad credentials.');

                $this->getResponse()
                     ->representJson(json_encode(['success' => $result, 'isIdOk' => $isIdOk]));
            } catch (Exception\ApiException $e) {
                if (\Magento\Framework\Webapi\Exception::HTTP_UNAUTHORIZED === $e->getCode()) {
                    $this->getResponse()
                         ->representJson(
                             json_encode(['success' => __('Bad credentials'), 'isIdOk' => false])
                         );

                    return;
                }

                $this->logger->critical($e);

                $this->getResponse()
                     ->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_INTERNAL_ERROR)
                     ->representJson(json_encode(['error' => 'Colissimo Api error: ' . $e->getMessage()]));
            } catch (\Exception $e) {
                $this->logger->critical($e);

                $this->getResponse()
                     ->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_INTERNAL_ERROR)
                     ->representJson(json_encode(['error' => $e->getMessage()]));
            }
        }
    }

    protected function constructLabelPayload()
    {
        //Mandatory parameters are here used to call the function checkGenerateLabel

        $login = $this->getRequest()->getParam('login');
        $password = $this->getRequest()->getParam('password');

        $items = [
            ['weight' => 1.9, 'weight_unit' => 'KILOGRAM'],
            ['weight' => 2.1, 'weight_unit' => 'KILOGRAM'],
            ['weight' => 0.2, 'weight_unit' => 'KILOGRAM'],
        ];

        $sender = [
            'companyName' => '__companyName__',
            'street'      => '__street_number_and_name__',
            'countryCode' => 'FR',
            'city'        => '__city__',
            'zipCode'     => '69007',
        ];

        $addressee = [
            'companyName' => '__companyName__',
            'street'      => '__street_number_and_name__',
            'countryCode' => 'FR',
            'city'        => '__city__',
            'zipCode'     => '69007',
        ];

        try {
            $this->generateLabelPayload->withContractNumber($login)->withPassword($password)->withProductCode("DOM")->withPreparationDelay(3)->withPackage(
                new \Magento\Framework\DataObject(),
                $items
            )->withSender($sender)->withAddressee($addressee)->withOutputFormat('PDF_A4_300dpi');
        } catch (\Exception $e) {
            $this->logger->error(__('Error during IDs checking'), [$e]);
        }
    }
}
