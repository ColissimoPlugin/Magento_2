<?php

namespace LaPoste\Colissimo\Block\System\Config\Field;

use LaPoste\Colissimo\Model\AccountApi;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class BlockCode extends Field
{
    protected $_template = 'LaPoste_Colissimo::system/config/field/blockCode.phtml';
    private AccountApi $accountApi;

    /**
     * @param Context    $context
     * @param AccountApi $accountApi
     * @param array      $data
     */
    public function __construct(
        Context $context,
        AccountApi $accountApi,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->accountApi = $accountApi;
    }

    /**
     * Return element html
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    public function isOptionActive(): bool
    {
        $accountInformation = $this->accountApi->getAccountInformation();

        return !empty($accountInformation['statutCodeBloquant']);
    }
}
