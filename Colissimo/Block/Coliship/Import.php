<?php

namespace LaPoste\Colissimo\Block\Coliship;

use Magento\Framework\Data\Form\FormKey;

class Import extends \Magento\Framework\View\Element\Template
{
    protected $helperData;
    protected $shipmentRepository;
    /**
     * @var FormKey
     */
    protected $formKey;


    /**
     * Import constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \LaPoste\Colissimo\Helper\Data                   $helperData
     * @param FormKey                                          $formKey
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \LaPoste\Colissimo\Helper\Data $helperData,
        FormKey $formKey
    ) {
        parent::__construct($context);

        $this->helperData = $helperData;
        $this->formKey = $formKey;
    }

    /**
     * @return string
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * @return string
     */
    public function getImportUrl()
    {
        return $this->getUrl(
            $this->helperData
                ->getAdminRoute('coliship', 'import')
        );
    }
}
