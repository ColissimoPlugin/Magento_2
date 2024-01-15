<?php

namespace LaPoste\Colissimo\Model;

use LaPoste\Colissimo\Api\Data\PricesInterfaceFactory;
use LaPoste\Colissimo\Api\Data\PricesSearchResultsInterfaceFactory;
use LaPoste\Colissimo\Api\PricesRepositoryInterface;
use LaPoste\Colissimo\Model\ResourceModel\Prices as ResourcePrices;
use LaPoste\Colissimo\Model\ResourceModel\Prices\CollectionFactory as PricesCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;

class PricesRepository implements PricesRepositoryInterface
{

    protected $resource;

    protected $pricesFactory;

    protected $pricesCollectionFactory;

    protected $searchResultsFactory;

    protected $dataObjectHelper;

    protected $dataObjectProcessor;

    protected $dataPricesFactory;

    protected $extensionAttributesJoinProcessor;

    private $storeManager;

    private $collectionProcessor;

    protected $extensibleDataObjectConverter;

    /**
     * @param ResourcePrices                      $resource
     * @param PricesFactory                       $pricesFactory
     * @param PricesInterfaceFactory              $dataPricesFactory
     * @param PricesCollectionFactory             $pricesCollectionFactory
     * @param PricesSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper                    $dataObjectHelper
     * @param DataObjectProcessor                 $dataObjectProcessor
     * @param StoreManagerInterface               $storeManager
     * @param CollectionProcessorInterface        $collectionProcessor
     * @param JoinProcessorInterface              $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter       $extensibleDataObjectConverter
     */
    public function __construct(
        ResourcePrices $resource,
        PricesFactory $pricesFactory,
        PricesInterfaceFactory $dataPricesFactory,
        PricesCollectionFactory $pricesCollectionFactory,
        PricesSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->pricesFactory = $pricesFactory;
        $this->pricesCollectionFactory = $pricesCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataPricesFactory = $dataPricesFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \LaPoste\Colissimo\Api\Data\PricesInterface $prices
    ) {
        /* if (empty($prices->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $prices->setStoreId($storeId);
        } */

        $pricesData = $this->extensibleDataObjectConverter->toNestedArray(
            $prices,
            [],
            \LaPoste\Colissimo\Api\Data\PricesInterface::class
        );

        $pricesModel = $this->pricesFactory->create()->setData($pricesData);

        try {
            $this->resource->save($pricesModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the price: %1',
                    $exception->getMessage()
                )
            );
        }

        return $pricesModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($pricesId)
    {
        $prices = $this->pricesFactory->create();
        $this->resource->load($prices, $pricesId);
        if (!$prices->getId()) {
            throw new NoSuchEntityException(__('Prices with id "%1" does not exist.', $pricesId));
        }

        return $prices->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria,
        $attribute = null,
        $value = null
    ) {
        $collection = $this->pricesCollectionFactory->create();
        if (!empty($attribute) && !empty($value)) {
            $collection->addFilter($attribute, $value);
        }

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \LaPoste\Colissimo\Api\Data\PricesInterface::class
        );

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \LaPoste\Colissimo\Api\Data\PricesInterface $prices
    ) {
        try {
            $pricesModel = $this->pricesFactory->create();
            $this->resource->load($pricesModel, $prices->getEntityId());
            $this->resource->delete($pricesModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                                                  'Could not delete the Price: %1',
                                                  $exception->getMessage()
                                              ));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($pricesId)
    {
        return $this->delete($this->get($pricesId));
    }
}

