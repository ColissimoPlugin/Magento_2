<?php

namespace LaPoste\Colissimo\Block\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use LaPoste\Colissimo\Model\AccountApi;

class AccountInformation extends Field
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
        $accountInformation = $this->accountApi->getAccountInformation();
        if (empty($accountInformation['contractType'])) {
            return __('No account information available.');
        }

        $args = [
            'contractType'                   => ucfirst(strtolower($accountInformation['contractType'])),
            'outOfHomeContract'              => ucfirst(strtolower($accountInformation['statutHD'])),
            'pickupNeighborRelay'            => $accountInformation['statutPickme'] ? 'Activated' : 'Deactivated',
            'mimosa'                         => empty($accountInformation['mimosaSubscribed']) ? 'Deactivated' : 'Activated',
            'securedShipping'                => $accountInformation['statutCodeBloquant'] ? 'Activated' : 'Deactivated',
            'estimatedShippingDate'          => $accountInformation['statutTunnelCommande'] ? 'Activated' : 'Deactivated',
            'estimatedShippingDateDepotList' => empty($accountInformation['siteDepotList']) ? [] : $accountInformation['siteDepotList'],
            'securedReturn'                  => $accountInformation['optionRetourToken'] ? 'Activated' : 'Deactivated',
            'returnMailbox'                  => $accountInformation['optionRetourBAL'] ? 'Activated' : 'Deactivated',
            'returnPostOffice'               => $accountInformation['optionRetourBP'] ? 'Activated' : 'Deactivated',
        ];

        $output = '<ul>
			<li>
				<span class="colissimo-account-information-label">' . __('Contract type:') . '</span>
				<span class="colissimo-account-information-value">' . $args['contractType'] . '</span>
			</li>
			<li>
				<span class="colissimo-account-information-label">' . __('Out-of-home contract type:') . '</span>
				<span class="colissimo-account-information-value">' . $args['outOfHomeContract'] . '</span>
			</li>
			<li>
				<span class="colissimo-account-information-label">' . __('Pickup neighbor-relay option:') . '</span>
				<span class="colissimo-account-information-value">' . __($args['pickupNeighborRelay']) . '</span>
			</li>
			<li>
				<span class="colissimo-account-information-label">' . __('Mimosa option:') . '</span>
				<span class="colissimo-account-information-value">' . __($args['mimosa']) . '</span>
			</li>
			<li>
				<span class="colissimo-account-information-label">' . __('Secured shipping option:') . '</span>
				<span class="colissimo-account-information-value">' . __($args['securedShipping']) . '</span>
			</li>
			<li>
				<span class="colissimo-account-information-label">' . __('Estimated shipping date option:') . '</span>
				<span class="colissimo-account-information-value">' . __($args['estimatedShippingDate']) . '</span>
			</li>';

        if ('Activated' === $args['estimatedShippingDate'] && !empty($args['estimatedShippingDateDepotList'])) {
            $output .= '<li>
                <span class="colissimo-account-information-label">' . __('Your Colissimo deposit places:') . '</span>
                <ul class="lpc_depot_list">';

            foreach ($args['estimatedShippingDateDepotList'] as $depot) {
                $output .= '<li>' . $depot['codeRegate'] . ' - ' . $depot['libellepfc'] . '</li>';
            }

            $output .= '</ul></li>';
        }

        $output .= '<li>
				<span class="colissimo-account-information-label">' . __('Secured return option:') . '</span>
				<span class="colissimo-account-information-value">' . __($args['securedReturn']) . '</span>
			</li>
			<li>
				<span class="colissimo-account-information-label">' . __('Return in mailbox option:') . '</span>
				<span class="colissimo-account-information-value">' . __($args['returnMailbox']) . '</span>
			</li>
			<li>
				<span class="colissimo-account-information-label">' . __('Return in post office option:') . '</span>
				<span class="colissimo-account-information-value">' . __($args['returnPostOffice']) . '</span>
			</li>
		</ul>';

        return $output;
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
