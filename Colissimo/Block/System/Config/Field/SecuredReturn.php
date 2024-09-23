<?php

namespace LaPoste\Colissimo\Block\System\Config\Field;

use LaPoste\Colissimo\Helper\Data as HelperData;
use LaPoste\Colissimo\Model\AccountApi;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class SecuredReturn extends Field
{
    protected $_template = 'LaPoste_Colissimo::system/config/field/securedReturn.phtml';
    private AccountApi $accountApi;
    protected HelperData $helperData;

    /**
     * @param Context    $context
     * @param AccountApi $accountApi
     * @param HelperData $helperData
     * @param array      $data
     */
    public function __construct(
        Context $context,
        AccountApi $accountApi,
        HelperData $helperData,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->accountApi = $accountApi;
        $this->helperData = $helperData;
    }

    /**
     * Return element html
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $this->setNamePrefix($element->getName())
             ->setHtmlId($element->getHtmlId());

        return $this->_toHtml();
    }

    public function isOptionActive(): bool
    {
        if ($this->isOptionAuthorized() && '1' === $this->helperData->getAdvancedConfigValue('lpc_return_labels/securedReturn')) {
            return true;
        }

        return false;
    }

    public function isOptionAuthorized(): bool
    {
        $accountInformation = $this->accountApi->getAccountInformation();

        return !empty($accountInformation['optionRetourToken']);
    }

    public function getAccountServiceUrl(): string
    {
        $urls = $this->accountApi->getAutologinURLs();

        return $urls['urlParamServices'] ?? 'https://www.colissimo.entreprise.laposte.fr';
    }
}
