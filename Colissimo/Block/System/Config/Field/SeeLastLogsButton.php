<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Block\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class SeeLastLogsButton extends Field
{
    const DEBUG_RELATIVE_FILEPATH = '/colissimo/debug.log';


    /**
     * @var string
     */
    protected $_template = 'LaPoste_Colissimo::system/config/field/seeLastLogsButton.phtml';

    protected $directoryList;

    /**
     * @param Context $context
     * @param array   $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->directoryList = $directoryList;
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

    /**
     * Return element html
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getLastLines()
    {
        $logFilePath = $this->directoryList->getPath(
                \Magento\Framework\App\Filesystem\DirectoryList::LOG
            )
                       . self::DEBUG_RELATIVE_FILEPATH;

        return '[..]' . $this->tailCustom($logFilePath, 1000);
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id'    => 'lpc_see_last_logs',
                'label' => __('See last logs'),
            ]
        );

        return $button->toHtml();
    }


    private function tailCustom($filepath, $lines = 1)
    {
        $output = '';

        if (!file_exists($filepath)) {
            return $output;
        }

        // Open file
        $f = fopen($filepath, 'rb');
        if ($f === false) {
            return $output;
        }

        $buffer = 4096;

        // Jump to last character
        fseek($f, - 1, SEEK_END);

        // Read it and adjust line number if necessary
        if (fread($f, 1) != PHP_EOL) {
            $lines -= 1;
        }

        // Start reading
        $chunk = '';

        // While we would like more
        while (ftell($f) > 0 && $lines >= 0) {
            // Figure out how far back we should jump
            $seek = min(ftell($f), $buffer);
            // Do the jump (backwards, relative to where we are)
            fseek($f, - $seek, SEEK_CUR);
            // Read a chunk and prepend it to our output
            $output = ($chunk = fread($f, $seek)) . $output;
            // Jump back to where we started reading
            fseek($f, - mb_strlen($chunk, '8bit'), SEEK_CUR);
            // Decrease our line counter
            $lines -= substr_count($chunk, PHP_EOL);
        }

        // Close file and return
        fclose($f);

        return trim($output);
    }
}
