<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Model\Config\Source;

use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;
use Magento\Sales\Model\Order\Config;

class OrderStatusWithoutNew extends OrderStatus
{
    protected $orderConfig;

    private $newStateStatus;

    public function __construct(
        CollectionFactory $statusCollectionFactory,
        Config $orderConfig
    ) {
        parent::__construct($statusCollectionFactory);
        $this->orderConfig = $orderConfig;
    }


    private function filterNewStateStatus(array $array)
    {
        if (null === $this->newStateStatus) {
            $this->newStateStatus = $this->orderConfig
                ->getStateStatuses(\Magento\Sales\Model\Order::STATE_NEW);
        }

        foreach ($array as $key => $oneStatus) {
            if (array_key_exists($oneStatus['value'], $this->newStateStatus)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->filterNewStateStatus(parent::toOptionArray());
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return $this->filterNewStateStatus(parent::toArray());
    }
}
