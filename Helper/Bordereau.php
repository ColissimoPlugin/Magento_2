<?php
/**
 * ******************************************************
 *  * Copyright (C) 2018 La Poste.
 *  *
 *  * This file is part of La Poste - Colissimo module.
 *  *
 *  * La Poste - Colissimo module can not be copied and/or distributed without the express
 *  * permission of La Poste.
 *  ******************************************************
 *
 */

namespace LaPoste\Colissimo\Helper;

use LaPoste\Colissimo\Api\BordereauGeneratorApi;
use LaPoste\Colissimo\Model\BordereauFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Class Bordereau to generate bordereau from a list of shipments
 * @package LaPoste\Colissimo\Controller\Adminhtml\Bordereau
 */
class Bordereau extends AbstractHelper
{
    /**
     * Maximum numbers of labels per bordereau
     */
    const MAX_LABEL_PER_BORDEREAU = 50;

    const RETURN_LABEL_LETTER_MARK = 'R';

    /**
     * @var BordereauGeneratorApi
     */
    protected $bordereauGeneratorApi;

    /**
     * @var BordereauFactory
     */
    protected $bordereauFactory;

    /**
     * Bordereau constructor.
     * @param Context               $context
     * @param BordereauFactory      $bordereauFactory
     * @param BordereauGeneratorApi $bordereauGeneratorApi
     */
    public function __construct(
        Context $context,
        BordereauFactory $bordereauFactory,
        BordereauGeneratorApi $bordereauGeneratorApi
    ) {
        parent::__construct($context);
        $this->bordereauFactory = $bordereauFactory;
        $this->bordereauGeneratorApi = $bordereauGeneratorApi;
    }

    /**
     * @param $trackingNumbers
     * @return bool
     */
    public function generateBordereau($trackingNumbers)
    {
        $trackingNumbersPerBatch = $this->prepareBatch($trackingNumbers);

        foreach ($trackingNumbersPerBatch as $oneBatchOfTrack) {
            $retrievedBordereau = $this->bordereauGeneratorApi->generateBordereauByParcelsNumbers($oneBatchOfTrack);

            $bordereauHeader = $retrievedBordereau->bordereau->bordereauHeader;
            $bordereauId = $bordereauHeader->bordereauNumber;

            $this->bordereauFactory->create()
                                   ->setBordereauNumber($bordereauId)
                                   ->setCodeSitePch($bordereauHeader->codeSitePCH)
                                   ->setNumberOfParcels($bordereauHeader->numberOfParcels)
                                   ->setParcelsNumbers(implode(',', $oneBatchOfTrack))
                                   ->setPublishingDate($bordereauHeader->publishingDate)
                                   ->save();
        }

        return true;
    }

    /**
     * Prepare arrays of tracking numbers to limit the number of label per bordereau
     * @param $trackingNumbers
     * @return array
     */
    protected function prepareBatch($trackingNumbers)
    {
        $res = [];
        $batch = 0; // index for first level of result array
        $key = 0; // use a different index in case the array does not have numeric index or not in order...
        foreach ($trackingNumbers as $oneTrack) {
            if ($this->isReturnTrackingNumber($oneTrack)) {
                // we don't want Return labels in borderau
                continue;
            }

            if ($key % self::MAX_LABEL_PER_BORDEREAU == 0) {
                $res[$batch] = [];
            }
            $res[$batch][] = $oneTrack;
            if ($key % self::MAX_LABEL_PER_BORDEREAU == self::MAX_LABEL_PER_BORDEREAU - 1) {
                $batch ++;
            }
            $key ++;
        }

        return $res;
    }

    protected function isReturnTrackingNumber($trackingNumber)
    {
        return self::RETURN_LABEL_LETTER_MARK === substr($trackingNumber, 1, 1);
    }
}
