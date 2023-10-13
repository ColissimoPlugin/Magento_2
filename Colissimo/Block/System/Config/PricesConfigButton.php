<?php
/**
 * Block for access LaPoste Colissimo advanced configuration button
 */

namespace LaPoste\Colissimo\Block\System\Config;

/**
 * Synchronize button renderer
 */
class PricesConfigButton extends \Magento\Config\Block\System\Config\Form\Field
{

    protected $_template = 'LaPoste_Colissimo::system/config/PricesConfigButton.phtml';
    public $urlConfigPrices;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array                                   $data
     */
    public function __construct(\Magento\Backend\Block\Template\Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        $this->urlConfigPrices = $this->getUrl('laposte_colissimo/prices/index');
    }

    /**
     * Remove scope label
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Generate synchronize button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id'    => 'PricesConfigBtn',
                'label' => __('Configuration'),
            ]
        );

        return $button->toHtml();
    }

}
