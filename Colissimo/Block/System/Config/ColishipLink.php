<?php

namespace LaPoste\Colissimo\Block\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use LaPoste\Colissimo\Model\AccountApi;

/**
 * Class ColishipLink
 * @package LaPoste\Colissimo\Block\System\Config
 */
class ColishipLink extends Field
{
    private $accountApi;

    public function __construct(
        Context $context,
        AccountApi $accountApi,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->accountApi = $accountApi;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $urls = $this->accountApi->getAutologinURLs();
        $accountUrl = $urls['urlConnectedCbox'] ?? 'https://www.colissimo.entreprise.laposte.fr';
        $links = '<a href="' . $accountUrl . '" target="_blank">' . __('Access Colissimo Box') . '</a>';
        if (!empty($urls['urlParamServices'])) {
            $links .= ' | <a href="' . $urls['urlParamServices'] . '" target="_blank">' . __('Services settings') . '</a>';
        }
        if (!empty($urls['urlEtiquettesEtEmballages'])) {
            $links .= '<br /><br />' . sprintf(
                    __('For your postage needs, order your labels in rolls or boxes directly from your dedicated space %s, and enjoy the best prices and quality standards on the market.'),
                    '<a href="' . $urls['urlEtiquettesEtEmballages'] . '" target="_blank">' . __('Colissimo Box / Labels and packagings') . '</a>'
                );
        }

        return $links;
    }

    /**
     * Remove scope label
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }
}
