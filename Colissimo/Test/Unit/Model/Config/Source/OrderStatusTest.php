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

class OrderStatusTest extends \PHPUnit\Framework\TestCase
{
    protected $statusCollectionFactory;
    protected $orderStatus;


    public function setUp()
    {
        $this->statusCollectionFactory = $this->createMock(
            \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory::class
        );

        $this->orderStatus = $this->getMockBuilder(
            \LaPoste\Colissimo\Model\Config\Source\OrderStatus::class
        )
                                  ->setMethods()
                                  ->setConstructorArgs(
                                      [
                                          'statusCollectionFactory' => $this->statusCollectionFactory,
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
                ]
            ));

        $this->statusCollectionFactory
            ->expects($this->any())
            ->method('create')
            ->will($this->returnValue($statusCollection));

        $optionArray = $this->orderStatus->toOptionArray();

        // Check that the null / empty value is always the first element
        $item = reset($optionArray);
        $this->assertEquals(null, $optionArray[0]['value']);
        $this->assertEquals(__('no change'), $optionArray[0]['label']);

        // Check that all other items follow
        $item = next($optionArray);
        $this->assertEquals('pending', $optionArray[1]['value']);
        $this->assertEquals('Pending', $optionArray[1]['label']);
    }
}
