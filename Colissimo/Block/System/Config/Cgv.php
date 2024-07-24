<?php

namespace LaPoste\Colissimo\Block\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use LaPoste\Colissimo\Model\AccountApi;

/**
 * Class Cgv
 * @package LaPoste\Colissimo\Block\System\Config
 */
class Cgv extends Field
{
    private AccountApi $accountApi;

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
        if ($this->accountApi->isCgvAccepted()) {
            $output = '<style>#row_lpc_advanced_lpc_general_cgv{ display: none; }</style>';
        } else {
            $urls = $this->accountApi->getAutologinURLs();
            $accountUrl = $urls['urlConnectedCbox'] ?? 'https://www.colissimo.entreprise.laposte.fr';

            $message = __('We have detected that you have not yet signed the latest version of our GTC. Your consent is necessary in order to continue using Colissimo services. We therefore invite you to sign them on your Colissimo entreprise space, by clicking on the link below:');
            $message .= '<br/><a href="' . $accountUrl . '" target="_blank">' . __('Sign the GTC') . '</a>';
            $output = '<script>
            require(["jquery"], function ($) {
                $(document).ready(function () {
                    $(".page-main-actions").after(\'<div id="messages"><div class="messages"><div class="message message-error error"><div data-ui-id="messages-message-error">' . $message . '</div></div></div></div>\');
                });
            });
            </script>';
        }

        return $output;
    }
}
