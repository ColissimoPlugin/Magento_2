<?php

namespace LaPoste\Colissimo\Block\Prices;

use LaPoste\Colissimo\Helper\Data;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Import extends Template
{
    protected $helperData;
    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * Import constructor.
     * @param Context $context
     * @param Data    $helperData
     * @param FormKey $formKey
     */
    public function __construct(
        Context $context,
        Data $helperData,
        FormKey $formKey
    ) {
        parent::__construct($context);

        $this->helperData = $helperData;
        $this->formKey = $formKey;
    }

    /**
     * @return string
     */
    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }

    /**
     * @return string
     */
    public function getImportUrl(): string
    {
        return $this->getUrl(
            $this->helperData
                ->getAdminRoute('prices', 'importaction')
        );
    }

    public function getBackUrl(): string
    {
        return $this->getUrl(
            $this->helperData
                ->getAdminRoute('prices', 'index')
        );
    }
}
