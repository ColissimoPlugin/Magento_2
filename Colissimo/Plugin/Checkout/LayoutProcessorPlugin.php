<?php

namespace LaPoste\Colissimo\Plugin\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;

class LayoutProcessorPlugin
{
    /**
     * @param LayoutProcessor $subject
     * @param array           $jsLayout
     * @return array
     */
    public function afterProcess(
        LayoutProcessor $subject,
        array $jsLayout
    ) {
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shippingAdditional']['children']['lpc_shipping_note'] = [
            'component'  => 'Magento_Ui/js/form/element/abstract',
            'config'     => [
                'customScope' => 'shippingAddress',
                'template'    => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/input',
                'formElement' => 'text',
                'options'     => [],
                'id'          => 'lpc_shipping_note',
                'tooltip'     => [
                    'description' => __('Shipping information for Colissimo'),
                ],
            ],
            'dataScope'  => 'shippingAddress.lpc_shipping_note',
            'label'      => __('Shipping notes'),
            'provider'   => 'checkoutProvider',
            'visible'    => true,
            'validation' => [],
            'sortOrder'  => 900,
            'id'         => 'lpc_shipping_note',
        ];


        return $jsLayout;
    }
}
