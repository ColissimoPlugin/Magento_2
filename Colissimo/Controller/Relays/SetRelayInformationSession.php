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


use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use \LaPoste\Colissimo\Logger;


class SetRelayInformationSession extends Action
{
    protected $_checkoutSession;
    protected $colissimoLogger;

    /**
     * SetRelayInformationSession constructor.
     * @param Context          $context
     * @param Session          $checkoutSession
     * @param Logger\Colissimo $logger
     */
    public function __construct(Context $context, Session $checkoutSession, Logger\Colissimo $logger)
    {
        $this->_checkoutSession = $checkoutSession;
        $this->colissimoLogger = $logger;

        return parent::__construct($context);
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

        if (!empty($this->_checkoutSession->getLpcRelayInformation())) {
            $this->_checkoutSession->setLpcRelayInformation([]);
        }

        if (!in_array('', $relayInformation)) {
            $this->_checkoutSession->setLpcRelayInformation($relayInformation);
            if (empty(session_id()) || session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $session = '_SESSION';
            $$session['lpc_pickup_information'] = $relayInformation;
        } else {
            $this->colissimoLogger->error(
                __METHOD__,
                [
                    'Error LPC: Can\'t set relay information in the session for the order because at least one relay information is empty in request.',
                    $relayInformation,
                ]
            );
        }
    }
}
