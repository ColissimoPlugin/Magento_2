<?php

namespace LaPoste\Colissimo\Model\ResourceModel;

class Prices extends \Magento\Eav\Model\Entity\AbstractEntity
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setType('laposte_prices_entity');
    }
}

