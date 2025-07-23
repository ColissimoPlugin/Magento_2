<?php

/*******************************************************
 * Copyright (C) 2023 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Controller\Adminhtml\Configuration;

use LaPoste\Colissimo\Helper\Data;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;

class DownloadButton extends Action
{
    protected const DEBUG_RELATIVE_FILEPATH = '/colissimo/debug.log';
    protected const DOC_RELATIVE_FILEPATH = __DIR__ . '/../../../resources/doc.pdf';

    protected Data $helperData;

    protected FileFactory $fileFactory;
    protected DirectoryList $directoryList;

    public function __construct(
        Context $context,
        Data $helperData,
        FileFactory $fileFactory,
        DirectoryList $directoryList
    ) {
        parent::__construct($context);

        $this->helperData = $helperData;
        $this->fileFactory = $fileFactory;
        $this->directoryList = $directoryList;
    }

    public function execute()
    {
        $type = $this->getRequest()->getParam('type');

        if ('logs' === $type) {
            $logFilePath = $this->directoryList->getPath(DirectoryList::LOG);
            $logFilePath .= self::DEBUG_RELATIVE_FILEPATH;
            $content = file_get_contents($logFilePath);

            return $this->fileFactory->create(
                'colissimo.log',
                $content,
                DirectoryList::VAR_DIR,
                'text/plain'
            );
        }

        return $this->fileFactory->create(
            'Guide Colissimo pour Magento 2.pdf',
            file_get_contents(self::DOC_RELATIVE_FILEPATH),
            DirectoryList::VAR_DIR,
            'application/pdf'
        );
    }
}
