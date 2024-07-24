<?php

namespace LaPoste\Colissimo\Block\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Form\Element\AbstractElement;
use LaPoste\Colissimo\Model\AccountApi;

class Onboarding extends Field
{
    protected $_template = 'LaPoste_Colissimo::system/config/field/onboarding.phtml';
    public string $urlOriginAddress;
    public string $urlPricesPage;
    public string $urlMethodsPage;
    private AccountApi $accountApi;

    /**
     * @param Context    $context
     * @param AccountApi $accountApi
     * @param array      $data
     */
    public function __construct(Context $context, AccountApi $accountApi, array $data = [])
    {
        parent::__construct($context, $data);
        $this->urlOriginAddress = $this->getUrl('adminhtml/system_config/edit/section/shipping');
        $this->urlPricesPage = $this->getUrl('laposte_colissimo/prices/index');
        $this->urlMethodsPage = $this->getUrl('adminhtml/system_config/edit/section/carriers') . '#row_carriers_lpc_group_domiciless_enable';
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

    public function getOnboardingUrls(): array
    {
        $urls = $this->accountApi->getAutologinURLs();
        $urls['urlConnectedCbox'] = $urls['urlConnectedCbox'] ?? 'https://www.colissimo.entreprise.laposte.fr';
        $urls['contractTypes'] = $urls['urlContrats'] ?? 'https://www.colissimo.entreprise.laposte.fr/nos-contrats';
        $urls['faciliteUrl'] = 'https://www.colissimo.entreprise.laposte.fr/contrat-facilite';
        $urls['faciliteForm'] = $urls['urlInscription'] ?? 'https://www.colissimo.entreprise.laposte.fr/contrat-facilite';
        $urls['privilegeUrl'] = 'https://www.colissimo.entreprise.laposte.fr/contrat-privilege';
        $urls['privilegeForm'] = $urls['urlContact'] ?? 'https://www.colissimo.entreprise.laposte.fr/contact';

        return $urls;
    }
}
