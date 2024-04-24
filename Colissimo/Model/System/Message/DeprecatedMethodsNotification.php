<?php

namespace LaPoste\Colissimo\Model\System\Message;

use LaPoste\Colissimo\Model\Carrier\Colissimo;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;

class DeprecatedMethodsNotification implements MessageInterface
{
    const MESSAGE_IDENTITY = 'lpc_deprecated_methods';

    private Colissimo $colissimoCarrier;
    private UrlInterface $urlBuilder;

    public function __construct(
        Colissimo $colissimoCarrier,
        UrlInterface $urlBuilder
    ) {
        $this->colissimoCarrier = $colissimoCarrier;
        $this->urlBuilder = $urlBuilder;
    }

    public function isDisplayed(): bool
    {
        $activeMethods = array_keys($this->colissimoCarrier->getAllowedMethods());

        return !empty(array_intersect($activeMethods, [Colissimo::CODE_SHIPPING_METHOD_EXPERT, Colissimo::CODE_SHIPPING_METHOD_EXPERT_DDP]));
    }

    public function getText(): string
    {
        $methodsPage = $this->urlBuilder->getUrl('adminhtml/system_config/edit/section/carriers');
        $pricesPage = $this->urlBuilder->getUrl('laposte_colissimo/prices/index');

        $message = sprintf(
            __('The "Colissimo International" method is deprecated, it is strongly recommended to <a href="%s">disable it</a> and replace it with the "Colissimo with signature" method.'),
            $methodsPage
        );
        $message .= '<br />';
        $message .= sprintf(
            __('You can export then import <a href="%s">your price ranges</a> during this modification, the rates remain the same.'),
            $pricesPage
        );

        return $message;
    }

    public function getIdentity(): string
    {
        return self::MESSAGE_IDENTITY;
    }

    public function getSeverity(): int
    {
        return self::SEVERITY_MAJOR;
    }
}
