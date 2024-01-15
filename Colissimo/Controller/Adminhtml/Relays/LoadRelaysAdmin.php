<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Controller\Adminhtml\Relays;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Model\RelaysWebservice\GenerateRelaysPayload;
use LaPoste\Colissimo\Model\RelaysWebservice\RelaysApi;
use LaPoste\Colissimo\Logger\Colissimo;

class LoadRelaysAdmin extends Action
{
    protected Data $helperData;
    protected PageFactory $resultPageFactory;
    protected JsonFactory $resultJsonFactory;
    protected GenerateRelaysPayload $generateRelaysPayload;
    protected Colissimo $logger;
    protected RelaysApi $relaysApi;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        RelaysApi $relaysApi,
        GenerateRelaysPayload $generateRelaysPayload,
        Colissimo $logger,
        Data $helperData
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->generateRelaysPayload = $generateRelaysPayload;
        $this->logger = $logger;
        $this->relaysApi = $relaysApi;
        $this->helperData = $helperData;
        parent::__construct($context);
    }

    public function execute()
    {
        $request = $this->getRequest();
        $address = [
            'address'     => $request->getParam('address'),
            'zipCode'     => $request->getParam('zipCode'),
            'city'        => $request->getParam('city'),
            'countryCode' => $request->getParam('countryId'),
        ];

        $loadMore = $request->getParam('loadMore') === '1';

        $errorCodesWSClientSide = [
            '104',
            '105',
            '117',
            '125',
            '129',
            '143',
            '144',
            '145',
            '146',
        ];

        $resultJson = $this->resultJsonFactory->create();

        try {
            $this->generateRelaysPayload->withLogin()->withPassword()->withAddress($address)->withShippingDate()->withOptionInter()->checkConsistency();
            $relaysPayload = $this->generateRelaysPayload->assemble();

            $resultWs = $this->relaysApi->getRelays($relaysPayload);
        } catch (\SoapFault $fault) {
            $this->logger->error($fault);

            return $resultJson->setData(['error' => 'Error getting pickup points from Colissimo', 'success' => 0]);
        } catch (LocalizedException $exception) {
            $this->logger->error($exception);

            return $resultJson->setData(['error' => $exception->getMessage(), 'success' => 0]);
        }

        $return = $resultWs->return;

        if ($return->errorCode == 0) {
            if (empty($return->listePointRetraitAcheminement)) {
                $this->logger->warning(__('Web service returns 0 relay'));

                return $resultJson->setData(['error' => __('No relay available'), 'success' => 0]);
            }

            $listRelaysWS = $return->listePointRetraitAcheminement;

            // Choose displayed relay types
            $relayTypesList = $this->helperData->getAdvancedConfigValue('lpc_pr_front/chooseRelayType');
            if (!empty($relayTypesList)) {
                $relayTypes = explode(',', $relayTypesList);
                $listRelaysWS = array_filter($listRelaysWS, function ($relay) use ($relayTypes) {
                    return in_array($relay->typeDePoint, $relayTypes);
                });
            }

            // Limit number of displayed relays
            $maxRelayPoint = $loadMore ? 20 : (int) $this->helperData->getAdvancedConfigValue('lpc_pr_front/maxRelayPoint');
            if (empty($maxRelayPoint)) {
                $maxRelayPoint = 20;
            }
            $maxRelayPoint = min(20, max(0, $maxRelayPoint));
            $listRelaysWS = array_slice($listRelaysWS, 0, $maxRelayPoint);

            if (empty($listRelaysWS)) {
                $this->logger->warning(__('Web service returns 0 relay'));

                return $resultJson->setData(['error' => __('No relay available'), 'success' => 0]);
            }

            $resultPage = $this->resultPageFactory->create();
            $block = $resultPage->getLayout()
                                ->createBlock('LaPoste\Colissimo\Block\Adminhtml\Order\ListRelaysAdmin')
                                ->setTemplate('LaPoste_Colissimo::order/list_relays_admin.phtml');
            $block->setListRelays($listRelaysWS);
            $listRelaysHtml = $block->toHtml();

            return $resultJson->setData(['html' => $listRelaysHtml, 'success' => 1, 'loadMore' => $loadMore]);
        } elseif ($return->errorCode == 301 || $return->errorCode == 300 || $return->errorCode == 203) {
            $this->logger->warning($return->errorCode . ' : ' . $return->errorMessage);

            return $resultJson->setData(['error' => __('No relay available'), 'success' => 0]);
        } else {
            $this->logger->error($return->errorCode . ' : ' . $return->errorMessage);

            return $resultJson->setData(
                [
                    'error'   => in_array($return->errorCode, $errorCodesWSClientSide) ? $return->errorMessage : 'Unknown error when trying to get pickup points',
                    'success' => 0,
                ]
            );
        }
    }
}
