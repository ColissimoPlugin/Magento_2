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
        $relayInformation = [];

        $relayInformation['id'] = $this->getRequest()->getParam('relayId', '');
        $relayInformation['name'] = $this->getRequest()->getParam('relayName', '');
        $relayInformation['address'] = $this->getRequest()->getParam('relayAddress', '');
        $relayInformation['post_code'] = $this->getRequest()->getParam('relayPostCode', '');
        $relayInformation['city'] = $this->getRequest()->getParam('relayCity', '');
        $relayInformation['type'] = $this->getRequest()->getParam('relayType', '');
        $relayInformation['country'] = $this->getRequest()->getParam('relayCountry', '');

        if (!empty($this->_checkoutSession->getLpcRelayInformation())) {
            $this->_checkoutSession->setLpcRelayInformation([]);
        }

        if (array_search("", $relayInformation) === false) {
            $this->_checkoutSession->setLpcRelayInformation($relayInformation);
        } else {
            $this->colissimoLogger->error(__METHOD__,
                                          [__('Error LPC : Can\'t set relay information in the session for the order because at least one relay information is empty in request.')]);
        }
    }
}
