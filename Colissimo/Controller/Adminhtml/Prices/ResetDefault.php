<?php

namespace LaPoste\Colissimo\Controller\Adminhtml\Prices;

use LaPoste\Colissimo\Logger\Colissimo;
use LaPoste\Colissimo\Model\Prices\DefaultPrices;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;

class ResetDefault extends Action
{
    const ADMIN_RESOURCE = 'LaPoste_Colissimo::Prices_update';

    protected DefaultPrices $defaultPrices;
    protected Colissimo $logger;

    public function __construct(
        Context $context,
        DefaultPrices $defaultPrices,
        Colissimo $logger
    ) {
        $this->defaultPrices = $defaultPrices;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        try {
            $insertedCount = $this->defaultPrices->reset();
            $this->messageManager->addSuccessMessage(
                __('A total of %1 default price range(s) have been restored.', $insertedCount)
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->messageManager->addErrorMessage(
                __('An error occurred during the prices reset: %1', $e->getMessage())
            );
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/');
    }
}
