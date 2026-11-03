<?php

namespace LaPoste\Colissimo\Model\Pdf;

use setasign\Fpdi\Fpdi;

/**
 * FPDI raises the version of the generated document to the highest version among the imported files, and offers no way to lower it back.
 */
class VersionedPdf extends Fpdi
{
    public function setPdfVersion(string $version): void
    {
        $this->PDFVersion = $version;
    }
}
