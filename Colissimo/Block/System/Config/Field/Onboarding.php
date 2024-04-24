<?php

namespace LaPoste\Colissimo\Block\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Onboarding extends Field
{
    protected $_template = 'LaPoste_Colissimo::system/config/field/onboarding.phtml';
    public string $urlOriginAddress;
    public string $urlPricesPage;
    public string $urlMethodsPage;

    /**
     * @param Context $context
     * @param array   $data
     */
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        $this->urlOriginAddress = $this->getUrl('adminhtml/system_config/edit/section/shipping');
        $this->urlPricesPage = $this->getUrl('laposte_colissimo/prices/index');
        $this->urlMethodsPage = $this->getUrl('adminhtml/system_config/edit/section/carriers') . '#row_carriers_lpc_group_domiciless_enable';
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
}
