<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Controller\Adminhtml\Bordereau;

class PrintBordereau extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'LaPoste_Colissimo::shipment';

    protected $bordereau;
    protected $bordereauGeneratorApi;
    protected $fileFactory;
    protected $logger;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \LaPoste\Colissimo\Logger\Colissimo $logger,
        \LaPoste\Colissimo\Model\Bordereau $bordereau,
        \LaPoste\Colissimo\Model\BordereauGeneratorApi $bordereauGeneratorApi
    ) {
        $this->fileFactory = $fileFactory;
        $this->logger = $logger;
        $this->bordereau = $bordereau;
        $this->bordereauGeneratorApi = $bordereauGeneratorApi;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $bordereauEntity = $this->bordereau
                ->load($this->getRequest()->getParam('entity_id'));

            $bordereau = $this->bordereauGeneratorApi
                ->getBordereauByNumber($bordereauEntity->getBordereauNumber());

            $content = $bordereau->bordereau->bordereauDataHandler;
            if ($content) {
                return $this->fileFactory->create(
                    'Bordereau(' . $bordereauEntity->getEntityId() . ').pdf',
                    $content,
                    \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,
                    'application/pdf'
                );
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__,
                [
                    'exception' => $e,
                ]
            );

            $this->messageManager->addErrorMessage(__('An error occurred while creating delivery docket.'));
        }

        return $this->_redirect('*/*');
    }
}
