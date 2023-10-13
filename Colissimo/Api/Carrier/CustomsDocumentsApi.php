<?php

namespace LaPoste\Colissimo\Api\Carrier;


use Exception;

interface CustomsDocumentsApi
{
    /**
     * @param string $documentType The type among the ones provided in the WS documentation (see lpc_admin_order_banner.php)
     * @param string $parcelNumber The label number
     * @param string $document     The "binary data" of the uploaded file according to the doc (@/tmp/xxxx.pdf in the examples)
     * @param string $documentName The uploaded file name for the error message
     *
     * @return string The uploaded document's uuid with the file extension
     * @throws Exception When an error occurs.
     */
    public function storeDocument($documentType, $parcelNumber, $document, $documentName);

    /**
     * @param string $parcelNumber
     *
     * @return array
     */
    public function getDocuments($parcelNumber);
}
