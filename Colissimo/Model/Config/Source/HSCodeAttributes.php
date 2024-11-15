<?php

namespace LaPoste\Colissimo\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Eav\Model\Config;
use Magento\Catalog\Model\Product;

class HSCodeAttributes implements ArrayInterface
{
    protected Config $eavConfig;

    public function __construct(Config $eavConfig)
    {
        $this->eavConfig = $eavConfig;
    }

    public function toOptionArray(): array
    {
        $attributes = $this->toArray();
        $options = [];
        foreach ($attributes as $attributeCode => $label) {
            $options[] = [
                'value' => $attributeCode,
                'label' => $label,
            ];
        }

        return $options;
    }

    public function toArray(): array
    {
        $matchingStrings = [
            'hs',
            'harmonized',
            'tariff',
            'customs',
        ];

        $attributes = $this->eavConfig->getEntityAttributes(Product::ENTITY);
        $options = [];
        foreach ($attributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            $attributeLabel = $attribute->getFrontendLabel();
            if (empty($attributeCode) || empty($attributeLabel)) {
                continue;
            }

            $attributeCodeLowercase = strtolower($attributeCode);
            $attributeLabelLowercase = strtolower($attributeLabel);

            $matching = false;
            foreach ($matchingStrings as $matchingString) {
                if (strpos($attributeCodeLowercase, $matchingString) !== false || strpos($attributeLabelLowercase, $matchingString) !== false) {
                    $matching = true;
                    break;
                }
            }

            if (!$matching) {
                continue;
            }

            $options[$attributeCode] = __($attributeLabel);
        }

        if (empty($options['lpc_hs_code'])) {
            $options['lpc_hs_code'] = __('Product HS Code');
        }
        asort($options);

        return $options;
    }
}
