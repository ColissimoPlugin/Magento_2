<?php

namespace LaPoste\Colissimo\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchInterface;
use LaPoste\Colissimo\Api\ColissimoStatus;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;

class OrderStatusPatch implements DataPatchInterface
{
    /**
     * @var StatusFactory
     */
    private $statusFactory;
    /**
     * @var StatusResourceFactory
     */
    private $statusResourceFactory;

    /**
     * @param StatusFactory         $statusFactory
     * @param StatusResourceFactory $statusResourceFactory
     */
    public function __construct(
        StatusFactory $statusFactory,
        StatusResourceFactory $statusResourceFactory
    ) {
        $this->statusFactory = $statusFactory;
        $this->statusResourceFactory = $statusResourceFactory;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $newOrderStatuses = [
            [
                'status'   => ColissimoStatus::ORDER_STATUS_TRANSIT,
                'label'    => 'Colissimo In-Transit',
                'mgStatus' => Order::STATE_COMPLETE,
            ],
            [
                'status'   => ColissimoStatus::ORDER_STATUS_DELIVERED,
                'label'    => 'Colissimo Delivered',
                'mgStatus' => Order::STATE_COMPLETE,
            ],
            [
                'status'   => ColissimoStatus::ORDER_STATUS_ANOMALY,
                'label'    => 'Colissimo Anomaly',
                'mgStatus' => Order::STATE_COMPLETE,
            ],
            [
                'status'   => ColissimoStatus::ORDER_STATUS_READYTOSHIP,
                'label'    => 'Colissimo Ready to ship',
                'mgStatus' => Order::STATE_COMPLETE,
            ],
        ];

        $statusResource = $this->statusResourceFactory->create();

        foreach ($newOrderStatuses as $oneStatus) {
            $status = $this->statusFactory->create();
            $status->setData(
                [
                    'status' => $oneStatus['status'],
                    'label'  => $oneStatus['label'],
                ]
            );

            try {
                $statusResource->save($status);
                $status->assignState($oneStatus['mgStatus'], false, true);
            } catch (AlreadyExistsException $exception) {
                // do nothing more
            } catch (\Exception $e) {
                // do nothing more
            }
        }
    }
}

