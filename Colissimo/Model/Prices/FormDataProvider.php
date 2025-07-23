<?php

namespace LaPoste\Colissimo\Model\Prices;

use LaPoste\Colissimo\Model\ResourceModel\Prices\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;

class FormDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{

    protected $collection;

    protected $dataPersistor;

    protected $loadedData;

    /**
     * Constructor
     *
     * @param string                 $name
     * @param string                 $primaryFieldName
     * @param string                 $requestFieldName
     * @param CollectionFactory      $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array                  $meta
     * @param array                  $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->collection->addAttributeToSelect('*');
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        foreach ($items as $model) {
            $this->loadedData[$model->getId()] = $model->getData();

            if (!empty($this->loadedData[$model->getId()]['category_ids'])) {
                $this->loadedData[$model->getId()]['category_ids'] = array_filter(explode(',', $this->loadedData[$model->getId()]['category_ids']));
            }
            if (empty($this->loadedData[$model->getId()]['weight_max'])) {
                $this->loadedData[$model->getId()]['weight_max'] = '-';
            }
            if (empty($this->loadedData[$model->getId()]['price_max'])) {
                $this->loadedData[$model->getId()]['price_max'] = '-';
            }
        }
        $data = $this->dataPersistor->get('laposte_colissimo_prices');

        if (!empty($data)) {
            $model = $this->collection->getNewEmptyItem();
            $model->setData($data);
            $this->loadedData[$model->getId()] = $model->getData();
            $this->dataPersistor->clear('laposte_colissimo_prices');
        }

        return $this->loadedData;
    }
}

