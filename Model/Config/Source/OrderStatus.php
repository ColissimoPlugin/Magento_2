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

class OrderStatus implements \Magento\Framework\Data\OptionSourceInterface
{
    protected $statusCollectionFactory;

    public function __construct(
        CollectionFactory $statusCollectionFactory
    ) {
        $this->statusCollectionFactory = $statusCollectionFactory;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result = $this->statusCollectionFactory->create()->toOptionArray();
        $emptyValue = [
            ['value' => null, 'label' => __('no change')],
        ];

        return array_merge($emptyValue, $result);
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $result = $this->statusCollectionFactory->create()->toArray();
        $result[null] = __('no change');

        return $result;
    }
}
