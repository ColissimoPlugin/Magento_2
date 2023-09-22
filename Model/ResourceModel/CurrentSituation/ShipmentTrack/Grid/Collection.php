<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Model\ResourceModel\CurrentSituation\ShipmentTrack\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable = 'sales_shipment_track',
        $resourceModel = \Magento\Sales\Model\ResourceModel\Order\Shipment\Track::class
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);

        $this->join(
            'sales_shipment',
            'sales_shipment.entity_id = main_table.parent_id',
            [
                'entity_id AS shipment_entity_id',
                'increment_id AS shipment_increment_id',
                'shipment_status',
            ]
        );

        $this->join(
            'sales_order',
            'sales_order.entity_id = main_table.order_id',
            [
                'entity_id AS order_entity_id',
                'increment_id AS order_increment_id',
            ]
        );

        $table = $this->getTable('laposte_colissimo_bordereau');
        $this->getSelect()
             ->joinLeft(
                 $table,
                 $table . '.parcels_numbers LIKE CONCAT(\'%\', main_table.track_number, \'%\')',
                 [
                     'entity_id AS bordereau_id',
                     'bordereau_number',
                 ]
             );

        $this->addFieldToFilter(
            'carrier_code',
            \LaPoste\Colissimo\Model\Carrier\Colissimo::CODE
        );

        $this->addFieldToFilter(
            'lpc_is_delivered',
            0
        );
    }

    protected function _getMappedField($field)
    {
        switch ($field) {
            case 'order_increment_id':
                return 'sales_order.increment_id';

            case 'shipment_increment_id':
                return 'sales_shipment.increment_id';

            default:
                return parent::_getMappedField($field);
        }
    }
}
