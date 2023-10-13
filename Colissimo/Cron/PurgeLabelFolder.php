<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Cron;


use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;

class PurgeLabelFolder
{
    const FOLDER_PATH = 'lpc_labels';
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $targetDirectory;

    /**
     * PurgeLabelFolder constructor.
     * @param \Magento\Framework\Filesystem $filesystem
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Filesystem $filesystem
    ) {
        $this->targetDirectory = $filesystem->getDirectoryWrite(DirectoryList::PUB);
    }

    public function execute()
    {
        try {
            $this->targetDirectory->delete(self::FOLDER_PATH);
        } catch (FileSystemException $e) {
        }
    }
}
