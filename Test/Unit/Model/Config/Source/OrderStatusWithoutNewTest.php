<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Test\Unit\Model\Config\Source;

class OrderStatusWithoutNewTest extends \PHPUnit\Framework\TestCase
{
    protected $statusCollectionFactory;
    protected $orderConfig;

    protected $orderStatusWithoutNew;


    public function setUp()
    {
        $this->statusCollectionFactory = $this->createMock(
            \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory::class
        );

        $this->orderConfig = $this->createMock(
            \Magento\Sales\Model\Order\Config::class
        );

        $this->orderStatusWithoutNew = $this->getMockBuilder(
            \LaPoste\Colissimo\Model\Config\Source\OrderStatusWithoutNew::class
        )
                                            ->setMethods()
                                            ->setConstructorArgs(
                                                [
                                                    'statusCollectionFactory' => $this->statusCollectionFactory,
                                                    'orderConfig'             => $this->orderConfig,
                                                ]
                                            )
                                            ->getMock();
    }

    public function testToOptionArray()
    {
        $statusCollection = $this->createMock(
            \Magento\Sales\Model\ResourceModel\Order\Status\Collection::class
        );

        $statusCollection
            ->expects($this->any())
            ->method('toOptionArray')
            ->will($this->returnValue(
                [
                    ['value' => 'pending', 'label' => 'Pending'],
                    ['value' => 'canceled', 'label' => 'Canceled'],
                ]
            ));

        $this->statusCollectionFactory
            ->expects($this->any())
            ->method('create')
            ->will($this->returnValue($statusCollection));

        $this->orderConfig
            ->expects($this->any())
            ->method('getStateStatuses')
            ->with(\Magento\Sales\Model\Order::STATE_NEW)
            ->will($this->returnValue(['pending' => 'Pending']));

        $optionArray = $this->orderStatusWithoutNew->toOptionArray();

        // Check that the null / empty value is always the first element
        $item = reset($optionArray);
        $this->assertEquals(null, $item['value']);
        $this->assertEquals(__('no change'), $item['label']);

        // Check that pending state is omitted
        $item = next($optionArray);
        $this->assertEquals('canceled', $item['value']);
        $this->assertEquals('Canceled', $item['label']);
    }
}
