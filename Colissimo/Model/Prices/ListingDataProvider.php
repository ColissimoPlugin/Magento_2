<?php

namespace LaPoste\Colissimo\Model\Prices;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;

/**
 * Class DataProvider for the listing
 */
class ListingDataProvider extends DataProvider
{
    private $categoryRepository;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        CategoryRepositoryInterface $categoryRepository,
        array $meta = [],
        array $data = []
    ) {
        $this->categoryRepository = $categoryRepository;
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
    }

    public function getData()
    {
        $listingData = parent::getData();
        foreach ($listingData['items'] as &$onePrice) {
            if (0 == $onePrice['price_max']) {
                $onePrice['price_max'] = '-';
            }
            if (0 == $onePrice['weight_max']) {
                $onePrice['weight_max'] = '-';
            }

            if (!empty($onePrice['category_ids'])) {
                $categoryIds = array_filter(explode(',', $onePrice['category_ids']));
                $categoryNames = [];
                foreach ($categoryIds as $categoryId) {
                    $categoryNames[] = $this->categoryRepository->get($categoryId)->getName();
                }
                $onePrice['category_ids'] = implode(', ', $categoryNames);
            }

            if (empty($onePrice['category_ids'])) {
                $onePrice['category_ids'] = __('All categories');
            }
        }

        return $listingData;
    }

    public function addFilter(Filter $filter)
    {
        if ('category_ids' == $filter->getField()) {
            $filter->setValue('%,' . $filter->getValue() . ',%');
            $filter->setConditionType('like');
        }
        parent::addFilter($filter);
    }
}
