<?php

namespace LaPoste\Colissimo\Model;

use LaPoste\Colissimo\Api\Data\PricesInterface;
use LaPoste\Colissimo\Api\Data\PricesInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;

class Prices extends \Magento\Framework\Model\AbstractModel
{

    const ENTITY = 'laposte_prices_entity';
    protected $pricesDataFactory;

    protected $dataObjectHelper;

    protected $_eventPrefix = 'laposte_prices_entity';

    /**
     * @param \Magento\Framework\Model\Context                         $context
     * @param \Magento\Framework\Registry                              $registry
     * @param PricesInterfaceFactory                                   $pricesDataFactory
     * @param DataObjectHelper                                         $dataObjectHelper
     * @param \LaPoste\Colissimo\Model\ResourceModel\Prices            $resource
     * @param \LaPoste\Colissimo\Model\ResourceModel\Prices\Collection $resourceCollection
     * @param array                                                    $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        PricesInterfaceFactory $pricesDataFactory,
        DataObjectHelper $dataObjectHelper,
        \LaPoste\Colissimo\Model\ResourceModel\Prices $resource,
        \LaPoste\Colissimo\Model\ResourceModel\Prices\Collection $resourceCollection,
        array $data = []
    ) {
        $this->pricesDataFactory = $pricesDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve prices model with prices data
     * @return PricesInterface
     */
    public function getDataModel()
    {
        $pricesData = $this->getData();

        $pricesDataObject = $this->pricesDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $pricesDataObject,
            $pricesData,
            PricesInterface::class
        );

        return $pricesDataObject;
    }

    public function setData($key, $value = null)
    {
        if (in_array($key, ['weight_min', 'price_min'])) {
            if (empty($value)) {
                $value = 0;
            }
        }

        return parent::setData($key, $value);
    }
}

