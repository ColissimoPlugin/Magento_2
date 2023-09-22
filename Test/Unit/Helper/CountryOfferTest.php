<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Test\Unit\Helper;

use \LaPoste\Colissimo\Model\Carrier\Colissimo;

class CountryOfferTest extends \PHPUnit\Framework\TestCase
{
    protected $countryOfferHelper;

    public function setUp()
    {
        $context = $this->createMock(
            \Magento\Framework\App\Helper\Context::class
        );
        $this->countryOfferHelper = $this->getMockBuilder(
            \LaPoste\Colissimo\Helper\CountryOffer::class
        )
                                         ->setMethods()
                                         ->setConstructorArgs(
                                             [
                                                 'context' => $context,
                                             ]
                                         )
                                         ->getMock();
    }


    public function testGetProductCodeForDestination_FR()
    {
        $fr_country_code = 'FR';

        $this->assertEquals(
            'DOM',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_DOMICILE_SS,
                    $fr_country_code,
                    null
                )
        );

        $this->assertEquals(
            'DOS',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_DOMICILE_AS,
                    $fr_country_code,
                    null
                )
        );

        $this->assertEquals(
            'CORE',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_DOMICILE_SS,
                    $fr_country_code,
                    null,
                    true
                )
        );

        $this->assertEquals(
            'CORE',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_DOMICILE_AS,
                    $fr_country_code,
                    null,
                    true
                )
        );
    }

    public function testGetProductCodeForDestination_OM()
    {
        $om_country_code = 'MQ';

        $this->assertEquals(
            'COM',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_DOMICILE_SS,
                    $om_country_code,
                    null
                )
        );

        $this->assertEquals(
            'CDS',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_DOMICILE_AS,
                    $om_country_code,
                    null
                )
        );

        $this->assertEquals(
            'CORI',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_DOMICILE_SS,
                    $om_country_code,
                    null,
                    true
                )
        );

        $this->assertEquals(
            'CORI',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_DOMICILE_AS,
                    $om_country_code,
                    null,
                    true
                )
        );
    }

    public function testGetProductCodeForDestination_International()
    {
        $international_country_code = 'VA';

        $this->assertEquals(
            'COLI',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_EXPERT,
                    $international_country_code,
                    null
                )
        );

        $this->assertEquals(
            null,
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_EXPERT,
                    $international_country_code,
                    null,
                    true
                )
        );


        $international_country_code = 'US';

        $this->assertEquals(
            'COLI',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_EXPERT,
                    $international_country_code,
                    null
                )
        );

        $this->assertEquals(
            null,
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_EXPERT,
                    $international_country_code,
                    null,
                    true
                )
        );


        $international_country_code = 'AU';

        $this->assertEquals(
            'COLI',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_EXPERT,
                    $international_country_code,
                    null
                )
        );

        $this->assertEquals(
            'CORI',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_EXPERT,
                    $international_country_code,
                    null,
                    true
                )
        );
    }

    public function testGetProductCodeForDestination_InternationalEurope()
    {
        $international_europe_country_code = 'DE';

        $this->assertEquals(
            null,
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_DOMICILE_SS,
                    $international_europe_country_code,
                    null
                )
        );

        $this->assertEquals(
            'DOS',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_DOMICILE_AS,
                    $international_europe_country_code,
                    null
                )
        );

        $this->assertEquals(
            'COLI',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_EXPERT,
                    $international_europe_country_code,
                    null
                )
        );

        $this->assertEquals(
            'CORI',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_DOMICILE_SS,
                    $international_europe_country_code,
                    null,
                    true
                )
        );

        $this->assertEquals(
            'CORI',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_DOMICILE_AS,
                    $international_europe_country_code,
                    null,
                    true
                )
        );

        $this->assertEquals(
            'CORI',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_EXPERT,
                    $international_europe_country_code,
                    null,
                    true
                )
        );


        $international_europe_country_code = 'BE';

        $this->assertEquals(
            'DOM',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_DOMICILE_SS,
                    $international_europe_country_code,
                    null
                )
        );

        $this->assertEquals(
            'DOS',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_DOMICILE_AS,
                    $international_europe_country_code,
                    null
                )
        );

        $this->assertEquals(
            'COLI',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_EXPERT,
                    $international_europe_country_code,
                    null
                )
        );

        $this->assertEquals(
            'CORI',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_DOMICILE_SS,
                    $international_europe_country_code,
                    null,
                    true
                )
        );

        $this->assertEquals(
            'CORI',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_DOMICILE_AS,
                    $international_europe_country_code,
                    null,
                    true
                )
        );

        $this->assertEquals(
            'CORI',
            $this->countryOfferHelper
                ->getProductCodeForDestination(
                    Colissimo::CODE_SHIPPING_METHOD_EXPERT,
                    $international_europe_country_code,
                    null,
                    true
                )
        );
    }
}
