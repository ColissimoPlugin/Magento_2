<?php

namespace LaPoste\Colissimo\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class HazmatCategories extends AbstractSource
{
    public const HAZMAT_CATEGORIES = [
        'lpc-cata' => [
            'label'      => 'Category A - CLP hazardous product',
            'max_weight' => 5000,
            'max_weight_text' => '< 5kg/5L',
            'code'       => 'A',
            'id'         => 1,
        ],
        'lpc-catb' => [
            'label'      => 'Category B - ADR/GPE 2 hazardous product',
            'max_weight' => 500,
            'max_weight_text' => '< 0,5kg/0,5L',
            'code'       => 'B',
            'id'         => 2,
        ],
        'lpc-catc' => [
            'label'      => 'Category C - ADR/GPE 3 hazardous product',
            'max_weight' => 1000,
            'max_weight_text' => '< 1kg/1L',
            'code'       => 'C',
            'id'         => 3,
        ],
        'lpc-catd' => [
            'label'      => 'Category D - Cosmetic hazardous product',
            'max_weight' => 1000,
            'max_weight_text' => '< 1kg/1L',
            'code'       => 'D',
            'id'         => 4,
        ],
        'lpc-cate' => [
            'label'      => 'Category E - Other derogated sensitive product',
            'max_weight' => 0,
            'max_weight_text' => 'According to contract',
            'code'       => 'E',
            'id'         => 5,
        ],
    ];

    public function getAllOptions(): array
    {
        if ($this->_options === null) {
            $this->_options = [
                [
                    'value' => 0,
                    'label' => __('None'),
                ],
            ];

            foreach (self::HAZMAT_CATEGORIES as $category) {
                $this->_options[] = [
                    'value' => $category['id'],
                    'label' => __($category['label']),
                ];
            }
        }

        return $this->_options;
    }

    public static function getCategorySlugFromId(int $categoryId): ?string
    {
        foreach (self::HAZMAT_CATEGORIES as $slug => $category) {
            if ($category['id'] === $categoryId) {
                return $slug;
            }
        }

        return null;
    }
}
