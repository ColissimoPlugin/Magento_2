<?php

namespace LaPoste\Colissimo\Controller\Adminhtml\Simulation;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use LaPoste\Colissimo\Api\CotationApi;
use LaPoste\Colissimo\Block\Adminhtml\Simulation\Form;
use LaPoste\Colissimo\Model\AccountApi;
use LaPoste\Colissimo\Logger;

class Calculate extends Action
{
    const ADMIN_RESOURCE = 'LaPoste_Colissimo::simulation';

    protected JsonFactory $resultJsonFactory;
    protected CotationApi $cotationApi;
    protected AccountApi $accountApi;
    protected Logger\Colissimo $logger;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CotationApi $cotationApi,
        AccountApi $accountApi,
        Logger\Colissimo $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->cotationApi = $cotationApi;
        $this->accountApi = $accountApi;
        $this->logger = $logger;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->getRequest()->isAjax()) {
            return $result->setData(['error' => __('Invalid request.')]);
        }

        $params = [
            'codePaysExpediteur'     => $this->getRequest()->getParam('codePaysExpediteur'),
            'codePostalExpediteur'   => $this->getRequest()->getParam('codePostalExpediteur'),
            'codePaysDestinataire'   => $this->getRequest()->getParam('codePaysDestinataire'),
            'codePostalDestinataire' => $this->getRequest()->getParam('codePostalDestinataire'),
            // Economic shipping is not available for now.
            'economique'             => false,
            // With/without commitment is deduced from the account contract type (Privilège vs Facilité).
            'avecEngagement'         => $this->hasCommitment(),
            'poids'                  => $this->getRequest()->getParam('poids'),
            'typeTarif'              => $this->getRequest()->getParam('typeTarif'),
            'optionsValorisees'      => $this->getRequest()->getParam('optionsValorisees'),
            // Not used but the API returns an error if missing
            'offreEntreprise'        => true,
        ];

        // The delivery mode dropdown drives home delivery, signature and pickup point type.
        $params += $this->resolveDeliveryMode($this->getRequest()->getParam('deliveryMode'));

        try {
            $response = $this->cotationApi->calculateCost($params);

            return $result->setData(['success' => true, 'result' => $response]);
        } catch (\Exception $e) {
            $this->logger->error($e);

            return $result->setData(
                [
                    'success' => false,
                    'error'   => __('Colissimo Api error: %1', $e->getMessage()),
                ]
            );
        }
    }

    /**
     * Translate the single delivery mode dropdown into the web service parameters.
     *
     * @param string|null $deliveryMode
     *
     * @return array
     */
    protected function resolveDeliveryMode(?string $deliveryMode): array
    {
        switch ($deliveryMode) {
            case Form::DELIVERY_MODE_HOME_SIGNATURE:
                return [
                    'livraisonDomicile' => true,
                    'avecSignature'     => true,
                    'typeSiteLivraison' => '',
                    'retour'            => false,
                ];
            case Form::DELIVERY_MODE_PICKUP_BPR:
                return [
                    'livraisonDomicile' => false,
                    'avecSignature'     => false,
                    'typeSiteLivraison' => 'BPR',
                    'retour'            => false,
                ];
            case Form::DELIVERY_MODE_PICKUP_A2P:
                return [
                    'livraisonDomicile' => false,
                    'avecSignature'     => false,
                    'typeSiteLivraison' => 'A2P',
                    'retour'            => false,
                ];
            case Form::DELIVERY_MODE_RETURN:
                // Return parcel : home delivery with signature, retour flag on.
                return [
                    'livraisonDomicile' => true,
                    'avecSignature'     => true,
                    'typeSiteLivraison' => '',
                    'retour'            => true,
                ];
            case Form::DELIVERY_MODE_HOME:
            default:
                return [
                    'livraisonDomicile' => true,
                    'avecSignature'     => false,
                    'typeSiteLivraison' => '',
                    'retour'            => false,
                ];
        }
    }

    /**
     * Whether the account is a commitment contract (Privilège) rather than Facilité.
     *
     * @return bool
     */
    protected function hasCommitment(): bool
    {
        $accountInformation = $this->accountApi->getAccountInformation();

        if (empty($accountInformation['contractType'])) {
            return false;
        }

        return AccountApi::CONTRACT_TYPE_FACILITE !== $accountInformation['contractType'];
    }
}
