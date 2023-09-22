<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Model\Mail\Template;


class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    /**
     * @param        $body
     * @param string $mimeType
     * @param string $disposition
     * @param string $encoding
     * @param null   $filename
     * @return $this
     */
    public function addAttachment(
        $body,
        $mimeType = \Zend_Mime::TYPE_OCTETSTREAM,
        $disposition = \Zend_Mime::DISPOSITION_ATTACHMENT,
        $encoding = \Zend_Mime::ENCODING_BASE64,
        $filename = null
    ) {
        $this->message->createAttachment($body, $mimeType, $disposition, $encoding, $filename);

        return $this;
    }

    /**
     * @param        $body
     * @param null   $filename
     * @param string $mimeType
     * @param string $disposition
     * @param string $encoding
     * @return \LaPoste\Colissimo\Model\Mail\Template\TransportBuilder
     */
    public function addPdfAttachment(
        $body,
        $filename = null,
        $mimeType = 'application/pdf',
        $disposition = \Zend_Mime::DISPOSITION_ATTACHMENT,
        $encoding = \Zend_Mime::ENCODING_BASE64
    ) {
        return $this->addAttachment(
            $body,
            $mimeType,
            $disposition,
            $encoding,
            $filename
        );
    }
}
