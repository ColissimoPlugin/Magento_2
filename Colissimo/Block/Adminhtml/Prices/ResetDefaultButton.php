<?php

namespace LaPoste\Colissimo\Block\Adminhtml\Prices;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class ResetDefaultButton implements ButtonProviderInterface
{
    protected UrlInterface $urlBuilder;
    protected Escaper $escaper;

    public function __construct(
        UrlInterface $urlBuilder,
        Escaper $escaper
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper;
    }

    public function getButtonData(): array
    {
        $confirmMessage = __(
            'All the price ranges will be deleted and replaced by the Colissimo default ones. The price ranges you added will be lost. Do you want to continue?'
        );

        return [
            'label'      => __('Replace by default prices'),
            'class'      => 'secondary',
            'on_click'   => "deleteConfirm('" . $this->escaper->escapeJs((string) $confirmMessage) . "', '" . $this->urlBuilder->getUrl('*/*/resetDefault') . "')",
            'sort_order' => 20,
        ];
    }
}
