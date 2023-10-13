<?php

namespace LaPoste\Colissimo\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchInterface;
use LaPoste\Colissimo\Api\ColissimoStatus;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;

class PartialDeliveryOrderStatusPatch implements DataPatchInterface
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
     * @param StatusFactory $statusFactory
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
        $statusResource = $this->statusResourceFactory->create();

        $status = $this->statusFactory->create();
        $status->setData(
            [
                'status' => ColissimoStatus::ORDER_STATUS_PARTIAL_DELIVERY,
                'label'  => 'Colissimo Partial Expedition',
            ]
        );

        try {
            $statusResource->save($status);
            $status->assignState(Order::STATE_PROCESSING, false, true);
        } catch (AlreadyExistsException $exception) {
            // do nothing more
        } catch (\Exception $e) {
            // do nothing more
        }
    }
}

