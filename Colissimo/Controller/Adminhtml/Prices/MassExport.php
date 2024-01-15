<?php

namespace LaPoste\Colissimo\Controller\Adminhtml\Prices;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use LaPoste\Colissimo\Model\ResourceModel\Prices\CollectionFactory;
use LaPoste\Colissimo\Api\Data\PricesInterface;

class MassExport extends Action
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        FileFactory $fileFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @throws LocalizedException|\Exception
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $csvContent = implode(
                          ',', [
                                 PricesInterface::METHOD,
                                 PricesInterface::AREA,
                                 PricesInterface::WEIGHT_MIN,
                                 PricesInterface::WEIGHT_MAX,
                                 PricesInterface::PRICE_MIN,
                                 PricesInterface::PRICE_MAX,
                                 PricesInterface::PRICE,
                             ]
                      ) . PHP_EOL;

        foreach ($collection as $item) {
            $csvContent .= $item->getMethod() . ',' .
                           $item->getArea() . ',' .
                           $item->getWeightMin() . ',' .
                           $item->getWeightMax() . ',' .
                           $item->getPriceMin() . ',' .
                           $item->getPriceMax() . ',' .
                           $item->getPrice() . PHP_EOL;
        }

        return $this->fileFactory->create(
            'Export Colissimo.csv',
            $csvContent,
            DirectoryList::VAR_DIR,
            'text/csv'
        );
    }
}
