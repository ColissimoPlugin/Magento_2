<?php

namespace LaPoste\Colissimo\Block\Adminhtml\Order\View\Tab;

use LaPoste\Colissimo\Helper\CountryOffer;
use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Model\Carrier\CustomsDocumentsApi;
use LaPoste\Colissimo\Model\Carrier\Colissimo;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Registry;

class CustomsTab extends Template implements TabInterface
{
    const RETURN_LABEL_LETTER_MARK = 'R';

    protected $_template = 'LaPoste_Colissimo::order/view/customs.phtml';
    /**
     * @var Registry
     */
    private $_coreRegistry;

    protected $countryOfferHelper;
    protected $helperData;
    protected $customsDocumentsApi;

    /**
     * View constructor.
     *
     * @param Context             $context
     * @param Registry            $registry
     * @param CountryOffer        $countryOfferHelper
     * @param Data                $helperData
     * @param CustomsDocumentsApi $customsDocumentsApi
     * @param array               $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CountryOffer $countryOfferHelper,
        Data $helperData,
        CustomsDocumentsApi $customsDocumentsApi,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->countryOfferHelper = $countryOfferHelper;
        $this->helperData = $helperData;
        $this->customsDocumentsApi = $customsDocumentsApi;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * Retrieve order model instance
     *
     * @return int
     *Get current id order
     */
    public function getOrderId()
    {
        return $this->getOrder()->getEntityId();
    }

    /**
     * Retrieve order increment id
     *
     * @return string
     */
    public function getOrderIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Customs documents');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Customs documents');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Returns the possible document types for the customs
     *
     * @return array
     */
    public function getDocumentTypes()
    {
        $documentsTypes = [
            'C50'                   => __('Custom clearance bordereau') . ' (C50)',
            'CERTIFICATE_OF_ORIGIN' => __('Original certificate'),
            'CN23'                  => __('Customs declaration') . ' (CN23)',
            'EXPORT_LICENCE'        => __('Export license'),
            'COMMERCIAL_INVOICE'    => __('Parcel invoice'),
            'COMPENSATION'          => __('Compensation report'),
            'DAU'                   => __('Unique administrative document') . ' (DAU)',
            'DELIVERY_CERTIFICATE'  => __('Delivery certificate'),
            'LABEL'                 => __('Label'),
            'PICTURE'               => __('Picture'),
            'SIGNATURE'             => __('Proof of delivery'),
        ];
        asort($documentsTypes);
        $documentsTypes['OTHER'] = __('Other document');
        $documentsTypes = array_merge(['' => __('Document type')], $documentsTypes);

        return $documentsTypes;
    }

    /**
     * Returns the documents already sent to the customs
     *
     * @return array
     */
    public function getSentDocuments()
    {
        $sentDocuments = [];
        $tracksCollection = $this->getOrder()->getTracksCollection();
        foreach ($tracksCollection as $track) {
            $oneParcelNumber = $track->getTrackNumber();
            if (self::RETURN_LABEL_LETTER_MARK !== substr($oneParcelNumber, 1, 1)) {
                $shipment = $track->getShipment();
                $documents = $shipment->getDataUsingMethod('lpc_label_docs');
                if (empty($documents)) {
                    $documents = [];
                } else {
                    $documents = json_decode($documents, true);
                }
                $sentDocuments[$oneParcelNumber] = $documents;
            }
        }

        return $sentDocuments;
    }

    /**
     * Returns the parcel number - shipment id relations
     *
     * @return array
     */
    public function getParcelShipmentRelations()
    {
        $relations = [];
        $tracksCollection = $this->getOrder()->getTracksCollection();
        foreach ($tracksCollection as $track) {
            $oneParcelNumber = $track->getTrackNumber();
            if (self::RETURN_LABEL_LETTER_MARK !== substr($oneParcelNumber, 1, 1)) {
                $shipment = $track->getShipment();
                $relations[$oneParcelNumber] = $shipment->getId();
            }
        }

        return $relations;
    }

    /**
     * Tells if the documents are needed for the customs
     *
     * @return boolean
     */
    public function isDocumentsNeeded()
    {
        $trackingNumbersForOrder = $this->getParcelNumbers();
        if (empty($trackingNumbersForOrder)) {
            return false;
        }

        $shippingAddress = $this->getOrder()->getShippingAddress();
        $countryCode = $shippingAddress->getCountryId();
        $date = date('Y-m-d');
        $shippingMethod = $this->getOrder()->getShippingMethod();

        if (in_array($countryCode, ['GF', 'GP', 'MQ'])
            || ('YT' === $countryCode && $date > '2022-03-31')
            || ('RE' === $countryCode && $date > '2022-05-31')
            || in_array($shippingMethod, Colissimo::DDP_METHODS)) {
            $storeCountryCode = $this->helperData->getConfigValue('shipping/origin/country_id');

            return $this->countryOfferHelper->getIsCn23RequiredForDestination(
                $countryCode,
                $shippingAddress->getPostcode(),
                $storeCountryCode,
                false
            );
        }

        return false;
    }

    /**
     * Returns the outward parcel numbers for the current order
     *
     * @return array
     */
    public function getParcelNumbers()
    {
        $parcelNumbers = [];
        $order = $this->getOrder();
        $tracksCollection = $order->getTracksCollection();

        foreach ($tracksCollection as $track) {
            $trackNumber = $track->getTrackNumber();
            if (self::RETURN_LABEL_LETTER_MARK !== substr($trackNumber, 1, 1)) {
                $parcelNumbers[] = $trackNumber;
            }
        }

        return $parcelNumbers;
    }

    /**
     * Returns the ajax url to send the documents to
     *
     * @return string
     */
    public function getAjaxUrlSendDocuments()
    {
        return $this->getUrl(
            $this->helperData->getAdminRoute('ajax', 'sendCustomsDocuments')
        );
    }
}
