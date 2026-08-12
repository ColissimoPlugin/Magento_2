<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Helper;


use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

class Pdf extends AbstractHelper
{
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * Pdf constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Filesystem         $filesystem
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->filesystem = $filesystem;
    }

    /**
     * Combine array of labels (PDF binary strings or image strings) into one PDF binary string
     *
     * @param array $labelsContent
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \setasign\Fpdi\PdfParser\PdfParserException
     */
    public function combineLabelsPdf(array $labelsContent)
    {
        $outputPdf = $this->createPdfDocument();
        foreach ($labelsContent as $content) {
            if (!$content) {
                continue;
            }
            if (stripos($content, '%PDF-') !== false) {
                $this->addPdfPages($outputPdf, $content);
            } else {
                $this->addImagePage($outputPdf, $content);
            }
        }

        return $outputPdf->Output('S');
    }

    /**
     * Convert an image string (JPEG, PNG, GIF, WBMP, or GD2 format) to a one-page PDF binary string
     *
     * @param string $imageString
     * @return string|false
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function imageStringToPdf($imageString)
    {
        $outputPdf = $this->createPdfDocument();
        if (!$this->addImagePage($outputPdf, $imageString)) {
            return false;
        }

        return $outputPdf->Output('S');
    }

    /**
     * @return \setasign\Fpdi\Fpdi
     */
    protected function createPdfDocument()
    {
        $pdf = new Fpdi('P', 'pt');
        $pdf->SetMargins(0, 0);
        $pdf->SetAutoPageBreak(false);

        return $pdf;
    }

    /**
     * Import all pages of a PDF binary string into the given document
     *
     * @param \setasign\Fpdi\Fpdi $outputPdf
     * @param string              $content
     * @return void
     * @throws \setasign\Fpdi\PdfParser\PdfParserException
     */
    protected function addPdfPages(Fpdi $outputPdf, $content)
    {
        $pageCount = $outputPdf->setSourceFile(StreamReader::createByString($content));
        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $templateId = $outputPdf->importPage($pageNumber);
            $size = $outputPdf->getTemplateSize($templateId);
            $outputPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $outputPdf->useTemplate($templateId);
        }
    }

    /**
     * Add a page containing the given image to the document. Supports JPEG, PNG, GIF, WBMP, and GD2 formats.
     *
     * @param \setasign\Fpdi\Fpdi $outputPdf
     * @param string              $imageString
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function addImagePage(Fpdi $outputPdf, $imageString)
    {
        /** @var \Magento\Framework\Filesystem\Directory\Write $directory */
        $directory = $this->filesystem->getDirectoryWrite(
            DirectoryList::TMP
        );
        $directory->create();
        $image = imagecreatefromstring($imageString);
        if (!$image) {
            return false;
        }

        $xSize = imagesx($image);
        $ySize = imagesy($image);

        imageinterlace($image, 0);
        $tmpFileName = $directory->getAbsolutePath(
            'shipping_labels_' . uniqid(\Magento\Framework\Math\Random::getRandomNumber()) . time() . '.png'
        );
        imagepng($image, $tmpFileName);
        $outputPdf->AddPage($xSize > $ySize ? 'L' : 'P', [$xSize, $ySize]);
        $outputPdf->Image($tmpFileName, 0, 0, $xSize, $ySize);
        $directory->delete($directory->getRelativePath($tmpFileName));
        imagedestroy($image);

        return true;
    }
}
