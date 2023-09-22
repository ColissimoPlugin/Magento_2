<?php
/**
 * Created by PhpStorm.
 * User: nmekharbeche
 * Date: 27/11/2018
 * Time: 11:20
 */

namespace LaPoste\Colissimo\Block;


use LaPoste\Colissimo\Helper\Data;
use LaPoste\Colissimo\Model\Shipping\ReturnLabelGenerator;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class ReturnLabel extends Template
{
    const XML_PATH_AVAILABLE_TO_CLIENT = 'lpc_return_labels/availableToCustomer';
    const PATH_TO_CONTROLLER = 'lpc/shipment/printreturnlabel';

    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var ReturnLabelGenerator
     */
    protected $labelGenerator;
    /**
     * @var Magento\Framework\App\RequestInterface;
     */
    protected $request;

    /**
     * ReturnLabel constructor.
     * @param Context $context
     * @param Data $helper
     * @param ReturnLabelGenerator $labelGenerator
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        ReturnLabelGenerator $labelGenerator,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->labelGenerator = $labelGenerator;
        $this->request = $context->getRequest();
    }

    /**
     * @return bool
     */
    public function isLabelAvailableToCustomer()
    {
        return (bool) $this->helper->getAdvancedConfigValue(self::XML_PATH_AVAILABLE_TO_CLIENT);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getReturnLabelPrintLink($shipment)
    {
        return $this->getUrl(
            self::PATH_TO_CONTROLLER,
            ['shipment_id' => $shipment->getId()]
        );
    }
}
