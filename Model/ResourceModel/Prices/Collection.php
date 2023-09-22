<?php

namespace LaPoste\Colissimo\Model\ResourceModel\Prices;

class Collection extends \Magento\Eav\Model\Entity\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \LaPoste\Colissimo\Model\Prices::class,
            \LaPoste\Colissimo\Model\ResourceModel\Prices::class
        );
    }
}

