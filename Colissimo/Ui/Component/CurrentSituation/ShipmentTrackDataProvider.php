<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Ui\Component\CurrentSituation;

class ShipmentTrackDataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    protected $colissimoStatus;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Magento\Framework\Api\Search\ReportingInterface $reporting,
        \Magento\Framework\Api\Search\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \LaPoste\Colissimo\Model\ColissimoStatus $colissimoStatus,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        $this->colissimoStatus = $colissimoStatus;
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        switch ($filter->getField()) {
            case 'bordereau_associated':
                switch ($filter->getValue()) {
                    case 'associated':
                        $filter->setField('bordereau_number');
                        $filter->setConditionType('notnull');
                        break;
                    case 'unassociated':
                        $filter->setField('bordereau_number');
                        $filter->setConditionType('null');
                        break;
                    default:
                        // do nothing
                        return;
                }
                break;

            case 'anomaly_status':
                switch ($filter->getValue()) {
                    case 'anomaly':
                        $filter->setField('shipment_status');
                        $filter->setConditionType('in');
                        $filter->setValue($this->colissimoStatus->getAnomalyInternalCodes());
                        break;
                    case 'to_print':
                        $filter->setField('shipment_status');
                        $filter->setConditionType('in');
                        $filter->setValue($this->colissimoStatus->getToPrintInternalCodes());
                        break;
                    default:
                        // do nothing
                        return;
                }
        }

        return parent::addFilter($filter);
    }
}
