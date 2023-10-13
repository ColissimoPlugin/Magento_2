<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Model\Shipping;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Address;
use Magento\Shipping\Model\Shipment\Request;
use Magento\Store\Model\ScopeInterface;
use Magento\User\Model\User;

class ReturnLabels extends \Magento\Shipping\Model\Shipping
{
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_authSession;

    /**
     * @var \Magento\Shipping\Model\Shipment\Request
     */
    protected $_request;

    protected $_currentCustomer;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface   $scopeConfig
     * @param \Magento\Shipping\Model\Config                       $shippingConfig
     * @param \Magento\Store\Model\StoreManagerInterface           $storeManager
     * @param \Magento\Shipping\Model\CarrierFactory               $carrierFactory
     * @param \Magento\Shipping\Model\Rate\ResultFactory           $rateResultFactory
     * @param \Magento\Shipping\Model\Shipment\RequestFactory      $shipmentRequestFactory
     * @param \Magento\Directory\Model\RegionFactory               $regionFactory
     * @param \Magento\Framework\Math\Division                     $mathDivision
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\Backend\Model\Auth\Session                  $authSession
     * @param \Magento\Shipping\Model\Shipment\Request             $request
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Shipping\Model\Config $shippingConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Shipping\Model\Shipment\RequestFactory $shipmentRequestFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Framework\Math\Division $mathDivision,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Backend\Model\Auth\Session $authSession,
        Request $request,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
    ) {
        $this->_authSession = $authSession;
        $this->_request = $request;
        $this->currentCustomer = $currentCustomer;
        parent::__construct(
            $scopeConfig,
            $shippingConfig,
            $storeManager,
            $carrierFactory,
            $rateResultFactory,
            $shipmentRequestFactory,
            $regionFactory,
            $mathDivision,
            $stockRegistry
        );
    }


    /**
     * Prepare and do return of shipment
     *
     * @param Shipment $orderShipment
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function returnOfShipment(Shipment $orderShipment)
    {
        $admin = $this->_authSession->getUser();
        if (null === $admin) {
            // this is used in frontend when building mailbox pick-up.
            $admin = $this->currentCustomer->getCustomer();
        }
        $order = $orderShipment->getOrder();

        $shippingMethod = $order->getShippingMethod(true);
        $shipmentStoreId = $orderShipment->getStoreId();
        $shipmentCarrier = $this->_carrierFactory->create($order->getShippingMethod(true)->getCarrierCode());
        $baseCurrencyCode = $this->_storeManager->getStore($shipmentStoreId)->getBaseCurrencyCode();
        if (!$shipmentCarrier) {
            throw new LocalizedException(__('Invalid carrier: %1', $shippingMethod->getCarrierCode()));
        }
        $shipperRegionCode = $this->_scopeConfig->getValue(
            Shipment::XML_PATH_STORE_REGION_ID,
            ScopeInterface::SCOPE_STORE,
            $shipmentStoreId
        );
        if (is_numeric($shipperRegionCode)) {
            $shipperRegionCode = $this->_regionFactory->create()->load($shipperRegionCode)->getCode();
        }

        $originStreet1 = $this->_scopeConfig->getValue(
            Shipment::XML_PATH_STORE_ADDRESS1,
            ScopeInterface::SCOPE_STORE,
            $shipmentStoreId
        );
        $storeInfo = new DataObject(
            (array) $this->_scopeConfig->getValue(
                'general/store_information',
                ScopeInterface::SCOPE_STORE,
                $shipmentStoreId
            )
        );


        if (!$admin->getFirstname()
            || !$admin->getLastname()
            || !$storeInfo->getName()
            || !$storeInfo->getPhone()
            || !$originStreet1
            || !$this->_scopeConfig->getValue(
                Shipment::XML_PATH_STORE_CITY,
                ScopeInterface::SCOPE_STORE,
                $shipmentStoreId
            )
            || !$this->_scopeConfig->getValue(
                Shipment::XML_PATH_STORE_ZIP,
                ScopeInterface::SCOPE_STORE,
                $shipmentStoreId
            )
            || !$this->_scopeConfig->getValue(
                Shipment::XML_PATH_STORE_COUNTRY_ID,
                ScopeInterface::SCOPE_STORE,
                $shipmentStoreId
            )
        ) {
            throw new LocalizedException(
                __(
                    'We don\'t have enough information to create shipping labels. Please make sure your store information and settings are complete.'
                )
            );
        }

        /** @var $request \Magento\Shipping\Model\Shipment\Request */
        $request = $this->_shipmentRequestFactory->create();
        $request->setOrderShipment($orderShipment);
        $address = $order->getShippingAddress();

        $this->setShipperDetails($request, $address);
        $this->setRecipientDetails($request, $admin, $storeInfo, $shipmentStoreId, $shipperRegionCode, $originStreet1);

        $request->setShippingMethod($shippingMethod->getMethod());
        $request->setPackageWeight($order->getWeight());
        $request->setPackages($orderShipment->getPackages());
        $request->setBaseCurrencyCode($baseCurrencyCode);
        $request->setStoreId($shipmentStoreId);

        $request->setIsReturnLabel(true);

        return $shipmentCarrier->returnOfShipment($request);
    }

    /**
     * Set recipient details into request
     * @param \Magento\Shipping\Model\Shipment\Request $request
     * @param \Magento\Sales\Model\Order\Address       $address
     * @return void
     */
    protected function setRecipientDetails(
        Request $request,
        $storeAdmin,
        DataObject $store,
        $shipmentStoreId,
        $regionCode,
        $originStreet
    ) {
        $originStreet2 = $this->_scopeConfig->getValue(
            Shipment::XML_PATH_STORE_ADDRESS2,
            ScopeInterface::SCOPE_STORE,
            $shipmentStoreId
        );

        if ($storeAdmin instanceof User) {
            $request->setRecipientContactPersonName($storeAdmin->getName());
        }
        $request->setRecipientContactPersonFirstName($storeAdmin->getFirstname());
        $request->setRecipientContactPersonLastName($storeAdmin->getLastname());
        $request->setRecipientContactCompanyName($store->getName());
        $request->setRecipientContactPhoneNumber($store->getPhone());
        $request->setRecipientEmail($storeAdmin->getEmail());
        $request->setRecipientAddressStreet(trim($originStreet . ' ' . $originStreet2));
        $request->setRecipientAddressStreet1($originStreet);
        $request->setRecipientAddressStreet2($originStreet2);
        $request->setRecipientAddressCity(
            $this->_scopeConfig->getValue(
                Shipment::XML_PATH_STORE_CITY,
                ScopeInterface::SCOPE_STORE,
                $shipmentStoreId
            )
        );
        $request->setRecipientAddressStateOrProvinceCode($regionCode);
        $request->setRecipientAddressPostalCode(
            $this->_scopeConfig->getValue(
                Shipment::XML_PATH_STORE_ZIP,
                ScopeInterface::SCOPE_STORE,
                $shipmentStoreId
            )
        );
        $request->setRecipientAddressCountryCode(
            $this->_scopeConfig->getValue(
                Shipment::XML_PATH_STORE_COUNTRY_ID,
                ScopeInterface::SCOPE_STORE,
                $shipmentStoreId
            )
        );
    }

    /**
     * Set shipper details into request
     * @param \Magento\Shipping\Model\Shipment\Request $request
     * @param \Magento\User\Model\User                 $storeAdmin
     * @param \Magento\Framework\DataObject            $store
     * @param                                          $shipmentStoreId
     * @param                                          $regionCode
     * @param                                          $originStreet
     * @return void
     */
    protected function setShipperDetails(Request $request, Address $address)
    {
        $request->setShipperContactPersonName(trim($address->getFirstname() . ' ' . $address->getLastname()));
        $request->setShipperContactPersonFirstName($address->getFirstname());
        $request->setShipperContactPersonLastName($address->getLastname());
        $request->setShipperContactCompanyName($address->getCompany());
        $request->setShipperContactPhoneNumber($address->getTelephone());
        $request->setShipperEmail($address->getEmail());
        $request->setShipperAddressStreet(trim($address->getStreetLine(1) . ' ' . $address->getStreetLine(2)));
        $request->setShipperAddressStreet1($address->getStreetLine(1));
        $request->setShipperAddressStreet2($address->getStreetLine(2));
        $request->setShipperAddressCity($address->getCity());
        $request->setShipperAddressStateOrProvinceCode($address->getRegionCode() ?: $address->getRegion());
        $request->setShipperAddressRegionCode($address->getRegionCode());
        $request->setShipperAddressPostalCode($address->getPostcode());
        $request->setShipperAddressCountryCode($address->getCountryId());
    }
}
