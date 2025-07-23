<?php


namespace LaPoste\Colissimo\Ui\Component\Create\Form\Prices;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

class CategoryIds implements OptionSourceInterface
{
    protected $context;
    protected $objectManager;
    protected $categoryCollection;

    public function __construct(
        CollectionFactory $categoryCollection,
        Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->context = $context;
        $this->objectManager = $objectManager;
        $this->categoryCollection = $categoryCollection;
    }

    public function toOptionArray()
    {
        $options = [];
        $categories = $this->categoryCollection->create()->addAttributeToSelect('*');
        foreach ($categories as $category) {
            $options[] = [
                'label' => $category->getName(),
                'value' => $category->getId(),
            ];
        }

        return $options;
    }
}
