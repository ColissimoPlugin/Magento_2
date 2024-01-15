<?php

namespace LaPoste\Colissimo\Controller\Adminhtml\Relays;

use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Logger\Colissimo;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class SetRelayInformationAdmin extends Action
{
    protected Colissimo $colissimoLogger;
    private Data $helperData;
    protected JsonFactory $resultJsonFactory;

    /**
     * SetRelayInformationSession constructor.
     * @param Data        $helperData
     * @param Context     $context
     * @param Colissimo   $logger
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Data $helperData,
        Context $context,
        Colissimo $logger,
        JsonFactory $resultJsonFactory
    ) {
        $this->helperData = $helperData;
        $this->colissimoLogger = $logger;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $request = $this->getRequest();

        $relayInformation = [
            'id'        => $request->getParam('relayId', ''),
            'name'      => $request->getParam('relayName', ''),
            'address'   => $request->getParam('relayAddress', ''),
            'post_code' => $request->getParam('relayPostCode', ''),
            'city'      => $request->getParam('relayCity', ''),
            'type'      => $request->getParam('relayType', ''),
            'country'   => $request->getParam('relayCountry', ''),
        ];

        if (!in_array('', $relayInformation)) {
            $this->helperData->setMarker('admin_order_creation_relay', $relayInformation);
        } else {
            $this->colissimoLogger->error(
                __METHOD__,
                [
                    'Error LPC: Can\'t set relay information in the session for the order because at least one relay information is empty in request.',
                    $relayInformation,
                ]
            );
        }

        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData(['success' => 1]);
    }
}
