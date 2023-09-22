<?php

namespace LaPoste\Colissimo\Plugin\Quote\Address;

class Rate
{
    /**
     * @param                                                   $subject
     * @param                                                   $result
     * @param \Magento\Quote\Model\Quote\Address\AbstractResult $rate
     * @return \Magento\Quote\Model\Quote\Address\Rate
     */
    public function afterImportShippingRate($subject, $result, $rate)
    {
        if ($rate instanceof \Magento\Quote\Model\Quote\Address\RateResult\Method) {
            $colissimoDescription = '';
            if (!empty($rate->getColissimoDescription())) {
                $colissimoDescription = $rate->getColissimoDescription();
            }
            $result->setColissimoDescription($colissimoDescription);
        }

        return $result;
    }
}
