<?php

/*******************************************************
 * Copyright (C) 2019 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Controller\Adminhtml\Coliship;

use LaPoste\Colissimo\Helper;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class FmtFileExample extends Action
{
    const PATH_TO_FILE = __DIR__ . '/../../../resources/magento_colissimo.fmt';

    protected $helperData;

    protected $fileFactory;

    public function __construct(
        Context $context,
        Helper\Data $helperData,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->helperData = $helperData;
        $this->fileFactory = $fileFactory;
    }

    public function execute()
    {
        $content = file_get_contents(self::PATH_TO_FILE);

        return $this->fileFactory->create(
            'magento_colissimo.fmt',
            $content,
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,
            'text/plain'
        );
    }

}
