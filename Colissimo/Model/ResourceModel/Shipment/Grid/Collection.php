<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Model\ResourceModel\Shipment\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\ResourceConnection;

class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    protected $resourceConnection;

    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        ResourceConnection $resourceConnection,
        $mainTable = 'sales_order',
        $resourceModel = \Magento\Sales\Model\ResourceModel\Order\Shipment::class
    ) {
        $this->resourceConnection = $resourceConnection;

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);

        $this->join(
            'sales_shipment',
            'sales_shipment.order_id = main_table.entity_id',
            [
                'shipment_status',
                'entity_id AS shipment_entity_id',
                'increment_id AS shipment_increment_id',
            ]
        );

        $this->join(
            ['shipping_address' => 'sales_order_address'],
            'shipping_address.entity_id = main_table.shipping_address_id',
            ['postcode', 'street', 'city', 'country_id']
        );

        $this->addFieldToFilter(
            'shipping_method',
            ['like' => \LaPoste\Colissimo\Model\Carrier\Colissimo::CODE . '_%']
        );
    }


    public function _getItemId(\Magento\Framework\DataObject $item)
    {
        return $item->getShipmentEntityId();
    }

    protected function _getMappedField($field)
    {
        $salesShipmentTrackTableName = $this->resourceConnection->getTableName('sales_shipment_track');

        switch ($field) {
            case 'entity_id':
                return 'main_table.entity_id';

            case 'increment_id':
                return 'main_table.increment_id';

            case 'shipment_increment_id':
                return 'sales_shipment.increment_id';

            case 'shipment_status':
                return 'sales_shipment.shipment_status';

            case 'store_id':
                return 'main_table.store_id';

            case 'created_at':
                return 'main_table.created_at';

            case 'postcode':
            case 'street':
            case 'city':
            case 'country_id':
                return 'shipping_address.' . $field;

            case 'track_number':
                return new \Zend_Db_Expr(
                    '(SELECT GROUP_CONCAT(track_number) FROM `' . $salesShipmentTrackTableName . '` WHERE ' . $salesShipmentTrackTableName . '.parent_id = sales_shipment.entity_id)'
                );

            default:
                return parent::_getMappedField($field);
        }
    }
}
