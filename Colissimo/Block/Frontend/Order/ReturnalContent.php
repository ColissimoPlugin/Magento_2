<?php

namespace LaPoste\Colissimo\Block\Frontend\Order;

use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Model\AccountApi;
use LaPoste\Colissimo\Model\PickUpPointApi;
use Magento\Framework\View\Element\Html\Link\Current;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;

class ReturnalContent extends Current
{
    const PATH_TO_CONTROLLER = 'lpc/shipment/printreturnlabel';
    const WIDGET_SERVER_URL = 'https://ws.colissimo.fr';

    private OrderRepositoryInterface $orderRepository;
    private AccountApi $accountApi;
    private PickUpPointApi $pickUpPointApi;
    private Data $helperData;

    public function __construct(
        Context $context,
        DefaultPathInterface $defaultPath,
        OrderRepositoryInterface $orderRepository,
        AccountApi $accountApi,
        PickUpPointApi $pickUpPointApi,
        Data $helperData,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);

        $this->orderRepository = $orderRepository;
        $this->accountApi = $accountApi;
        $this->pickUpPointApi = $pickUpPointApi;
        $this->helperData = $helperData;
    }

    public function getDropOffWidgetOptions(): array
    {
        $order = $this->getOrder();
        if (empty($order)) {
            return [];
        }

        $authenticateResponse = $this->pickUpPointApi->authenticate();
        if (false === $authenticateResponse || empty($authenticateResponse->token)) {
            return [];
        }

        $shippingAddress = $order->getShippingAddress();
        $street = $shippingAddress->getStreet();

        $options = [
            'URLColissimo'     => self::WIDGET_SERVER_URL,
            'token'            => $authenticateResponse->token,
            'ceCountry'        => empty($shippingAddress->getCountryId()) ? 'FR' : $shippingAddress->getCountryId(),
            'ceAddress'        => empty($street[0]) ? '' : $street[0],
            'ceZipCode'        => (string) $shippingAddress->getPostcode(),
            'ceTown'           => (string) $shippingAddress->getCity(),
            'returnDate'       => date('d/m/Y'),
            'typeDropOffPoint' => 'ALL',
            'filterReturnType' => '0',
            'origin'           => 'CMS',
        ];

        if ($this->helperData->getAdvancedConfigValue('lpc_pr_front/prCustomizeWidget')) {
            $options['couleur1'] = $this->helperData->getAdvancedConfigValue('lpc_pr_front/prAddressTextColor');
            $options['couleur2'] = $this->helperData->getAdvancedConfigValue('lpc_pr_front/prListTextColor');

            $font = $this->helperData->getFont('lpc_pr_front/prDisplayFont');
            if (!empty($font)) {
                $options['font'] = $font;
            }
        }

        return $options;
    }

    public function getReturnLabelDownloadUrl(): string
    {
        $products = [];

        return $this->getUrl(self::PATH_TO_CONTROLLER, ['products' => $products]);
    }

    public function mailBoxPickUpLink(): string
    {
        if (!$this->_scopeConfig->getValue('lpc_advanced/lpc_bal/allowMailBoxPickUp')) {
            return '';
        }
        // Don't propose BAL return if secured return enabled
        $accountInformation = $this->accountApi->getAccountInformation();
        if (!empty($accountInformation['optionRetourToken']) && $this->_scopeConfig->getValue('lpc_advanced/lpc_return_labels/securedReturn')) {
            return '';
        }

        $order = $this->getOrder();
        if (empty($order)) {
            return '';
        }

        if ('FR' !== $order->getShippingAddress()->getCountryId()) {
            return '';
        }

        return $this->getUrl('lpc/balReturn/index');
    }

    public function getShippedProducts(): array
    {
        $shippedProducts = [];

        $order = $this->getOrder();
        if (empty($order) || !$order->hasShipments()) {
            return $shippedProducts;
        }

        $shipments = $order->getShipmentsCollection();
        foreach ($shipments as $shipment) {
            if (empty($shipment->getShippingLabel())) {
                continue;
            }

            foreach ($shipment->getItems() as $oneItem) {
                $productId = $oneItem->getProductId();
                if (empty($shippedProducts[$productId])) {
                    $shippedProducts[$productId] = [
                        'name'     => $oneItem->getName(),
                        'quantity' => $oneItem->getQty(),
                    ];
                } else {
                    $shippedProducts[$productId]['quantity'] += $oneItem->getQty();
                }
            }
        }

        return $shippedProducts;
    }

    public function getOrder()
    {
        $params = $this->getRequest()->getParams();
        if (empty($params['order_id'])) {
            return null;
        }

        return $this->orderRepository->get($params['order_id']);
    }

    public function getReturnButtonLabel(): string
    {
        $accountInformation = $this->accountApi->getAccountInformation();
        if (!empty($accountInformation['optionRetourToken']) && $this->_scopeConfig->getValue('lpc_advanced/lpc_return_labels/securedReturn')) {
            return __('Return from a post office');
        } else {
            return __('Generate inward shipping label');
        }
    }

    public function isSecuredReturn(): bool
    {
        $accountInformation = $this->accountApi->getAccountInformation();

        return !empty($accountInformation['optionRetourToken']) && $this->_scopeConfig->getValue('lpc_advanced/lpc_return_labels/securedReturn');
    }

    public function getShippingPostcode(): string
    {
        $order = $this->getOrder();
        if (empty($order)) {
            return '';
        }

        return $order->getShippingAddress()->getPostcode();
    }
}
