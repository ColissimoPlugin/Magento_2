<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Controller\Relays;

use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Logger;
use LaPoste\Colissimo\Model\RelaysWebservice\GenerateRelaysPayload;
use LaPoste\Colissimo\Model\RelaysWebservice\RelaysApi;


class LoadRelays extends Action
{
    protected $helperData;
    protected $_resultPageFactory;
    protected $_resultJsonFactory;
    protected $_generateRelaysPayload;
    protected $_logger;
    protected $relaysApi;
    protected $cartModel;
    protected $storeManager;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        RelaysApi $relaysApi,
        GenerateRelaysPayload $generateRelaysPayload,
        Logger\Colissimo $logger,
        Cart $cartModel,
        Data $helperData,
        StoreManagerInterface $storeManager
    ) {
        $this->_resultPageFactory = $resultPageFactory;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_generateRelaysPayload = $generateRelaysPayload;
        $this->_logger = $logger;
        $this->relaysApi = $relaysApi;
        $this->helperData = $helperData;
        $this->cartModel = $cartModel;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    public function execute()
    {
        $address = [
            "address"     => $this->getRequest()->getParam('address'),
            "zipCode"     => $this->getRequest()->getParam('zipCode'),
            "city"        => $this->getRequest()->getParam('city'),
            "countryCode" => $this->getRequest()->getParam('countryId'),
        ];
        $loadMore = $this->getRequest()->getParam('loadMore') === '1';

        $errorCodesWSClientSide = [
            "104",
            "105",
            "117",
            "125",
            "129",
            "143",
            "144",
            "145",
            "146",
        ];

        $resultJson = $this->_resultJsonFactory->create();

        try {
            $this->_generateRelaysPayload->withLogin()->withPassword()->withAddress($address)->withShippingDate()->withOptionInter()->checkConsistency();
            $relaysPayload = $this->_generateRelaysPayload->assemble();

            $resultWs = $this->relaysApi->getRelays($relaysPayload);
        } catch (\SoapFault $fault) {
            $this->_logger->error($fault);

            return $resultJson->setData(['error' => "Error", 'success' => 0]);
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            $this->_logger->error($exception);

            return $resultJson->setData(['error' => $exception->getMessage(), 'success' => 0]);
        }

        $return = $resultWs->return;

        if ($return->errorCode == 0) {
            if (empty($return->listePointRetraitAcheminement)) {
                $this->_logger->warning(__('Web service returns 0 relay'));

                return $resultJson->setData(['error' => __('No relay available'), 'success' => 0]);
            }

            $listRelaysWS = $return->listePointRetraitAcheminement;

            // Choose displayed relay types
            $relayTypesList = $this->helperData->getAdvancedConfigValue('lpc_pr_front/chooseRelayType');

            // Force Post office type if cart weight > 20kg
            $items = $this->cartModel->getQuote()->getAllItems();
            $cartWeight = 0;
            foreach ($items as $item) {
                $cartWeight += ($item->getWeight() * $item->getQty());
            }
            $storeId = $this->storeManager->getStore()->getId();
            $cartWeight = $this->helperData->convertWeightToKilogram($cartWeight, null, $storeId);
            if ($cartWeight > 20) {
                $relayTypesList = 'BDP,BPR';
            }

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
                $this->_logger->warning(__('Web service returns 0 relay'));

                return $resultJson->setData(['error' => __('No relay available'), 'success' => 0]);
            }

            $resultPage = $this->_resultPageFactory->create();
            $block = $resultPage->getLayout()->createBlock('LaPoste\Colissimo\Block\ListRelays')->setTemplate("LaPoste_Colissimo::list_relays.phtml");
            $block->setListRelays($listRelaysWS);
            $block->setOverWarning($cartWeight);
            $listRelaysHtml = $block->toHtml();

            return $resultJson->setData(['html' => $listRelaysHtml, 'success' => 1, 'loadMore' => $loadMore]);
        } elseif ($return->errorCode == 301 || $return->errorCode == 300 || $return->errorCode == 203) {
            $this->_logger->warning($return->errorCode . " : " . $return->errorMessage);

            return $resultJson->setData(['error' => __('No relay available'), 'success' => 0]);
        } else {
            //Error to display To client
            if (in_array($return->errorCode, $errorCodesWSClientSide)) {
                $this->_logger->error($return->errorCode . " : " . $return->errorMessage);

                return $resultJson->setData(['error' => $return->errorMessage, 'success' => 0]);
            } //Error to hide to the client
            else {
                $this->_logger->error($return->errorCode . " : " . $return->errorMessage);

                return $resultJson->setData(['error' => "Error", 'success' => 0]);
            }
        }
    }
}
