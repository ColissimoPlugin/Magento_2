<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Test\Unit\Model\Carrier;

class GenerateLabelPayloadTest extends \PHPUnit\Framework\TestCase
{
    protected $printFormats;
    protected $registeredMailLevel;
    protected $logger;
    protected $generateLabelPayload;
    protected $helperData;

    const ALL_PRODUCT_CODES = [
        'CDS',
        'COL',
        'COLD',
        'COLI',
        'COM',
        'CORE',
        'CORI',
        'DOM',
        'DOS',
        'HD',
    ];

    const SOME_MINIMAL_SENDER = [
        'companyName' => '__companyName__',
        'street'      => '__street_number_and_name__',
        'countryCode' => '__countryCode__',
        'city'        => '__city__',
        'zipCode'     => '__zipCode__',
    ];

    const SOME_MINIMAL_ADDRESSEE = [
        'companyName' => '__companyName__',
        'street'      => '__street_number_and_name__',
        'countryCode' => '__countryCode__',
        'city'        => '__city__',
        'zipCode'     => '__zipCode__',
    ];


    public function setUp()
    {
        $this->printFormats = $this->createMock(
            \LaPoste\Colissimo\Model\Config\Source\PrintFormats::class
        );
        $this->registeredMailLevel = $this->createMock(
            \LaPoste\Colissimo\Model\Config\Source\RegisteredMailLevel::class
        );
        $this->helperData = $this->createMock(
            \LaPoste\Colissimo\Helper\Data::class
        );
        $this->logger = $this->createMock(\LaPoste\Colissimo\Logger\Colissimo::class);

        $this->generateLabelPayload = $this->getMockBuilder(
            \LaPoste\Colissimo\Model\Carrier\GenerateLabelPayload::class
        )
                                           ->setMethods()
                                           ->setConstructorArgs(
                                               [
                                                   'printFormats' => $this->printFormats,
                                                   'registeredMailLevel' => $this->registeredMailLevel,
                                                   'helperData' => $this->helperData,
                                                   'logger' => $this->logger,
                                               ]
                                           )
                                           ->getMock();
    }


    public function testWithCommercialName()
    {
        $this->generateLabelPayload
            ->withCommercialName('__commercialName__');

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals('__commercialName__', $assembly['letter']['service']['commercialName']);
    }

    public function testWithCommercialNameUsingDefaultConfigurationValue()
    {
        $this->helperData
            ->expects($this->any())
            ->method('getConfigValue')
            ->with('general/store_information/name')
            ->will($this->returnValue('__defaultCommercialName__'));

        $this->generateLabelPayload
            ->withCommercialName();

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals('__defaultCommercialName__', $assembly['letter']['service']['commercialName']);
    }

    public function testWithContractNumber()
    {
        $this->generateLabelPayload
            ->withContractNumber('__contractNumber__');

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals('__contractNumber__', $assembly['contractNumber']);
    }

    public function testWithContractNumberUsingDefaultConfigurationValue()
    {
        $this->helperData
            ->expects($this->any())
            ->method('getAdvancedConfigValue')
            ->with('lpc_general/id_webservices')
            ->will($this->returnValue('__defaultContractNumber__'));

        $this->generateLabelPayload
            ->withContractNumber();

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals('__defaultContractNumber__', $assembly['contractNumber']);
    }

    public function testWithPassword()
    {
        $this->generateLabelPayload
            ->withPassword('__password__');

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals('__password__', $assembly['password']);
    }

    public function testWithPasswordUsingDefaultConfigurationValue()
    {
        $this->helperData
            ->expects($this->any())
            ->method('getAdvancedConfigValue')
            ->with('lpc_general/pwd_webservices')
            ->will($this->returnValue('__defaultPassword__'));

        $this->generateLabelPayload
            ->withPassword();

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals('__defaultPassword__', $assembly['password']);
    }


    public function testWithPickupLocationId()
    {
        $this->generateLabelPayload
            ->withPickupLocationId('__pickupLocationId__');

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals('__pickupLocationId__', $assembly['letter']['parcel']['pickupLocationId']);
    }

    public function testWithProductCode()
    {
        $allowedProductCodes = self::ALL_PRODUCT_CODES;

        foreach ($allowedProductCodes as $productCode) {
            $this->generateLabelPayload
                ->withProductCode($productCode);

            $assembly = $this->generateLabelPayload->assemble();
            $this->assertEquals($productCode, $assembly['letter']['service']['productCode']);
        }
    }

    public function testWithProductCodeUsingBadProductCode()
    {
        try {
            $this->generateLabelPayload
                ->withProductCode('__productCode__');
        } catch (\Exception $e) {
            $this->assertEquals(
                __('Unknown Product code!'),
                (string) $e->getMessage()
            );

            return;
        }

        $this->fail('An exception should have occured!');
    }

    public function testWithDepositDate()
    {
        $this->generateLabelPayload
            ->withDepositDate(new \DateTime("2042-01-01"));

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals('2042-01-01', $assembly['letter']['service']['depositDate']);
    }

    public function testWithPreparationDelay()
    {
        $this->generateLabelPayload
            ->withPreparationDelay(42);

        $expected = new \DateTime();
        $expected->add(\DateInterval::createFromDateString("42 days"));
        $expected = $expected->format('Y-m-d');

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals($expected, $assembly['letter']['service']['depositDate']);
    }

    public function testWithPreparationDelayWithNegativeDelay()
    {
        $this->generateLabelPayload
            ->withPreparationDelay(- 42);

        $expected = new \DateTime();
        $expected = $expected->format('Y-m-d');

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals($expected, $assembly['letter']['service']['depositDate']);
    }

    public function testWithDepositDateUsingPastDate()
    {
        $depositDate = new \DateTime();
        $depositDate->add(\DateInterval::createFromDateString("2 days ago"));


        $this->generateLabelPayload
            ->withDepositDate($depositDate);

        $expected = new \DateTime();
        $expected = $expected->format('Y-m-d');

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals($expected, $assembly['letter']['service']['depositDate']);
    }

    public function testWithOutputFormat()
    {
        $outputFormat = '__outputFormat__';

        $this->printFormats
            ->expects($this->any())
            ->method('toOptionArray')
            ->will($this->returnValue([['value' => $outputFormat]]));

        $this->generateLabelPayload
            ->withOutputFormat($outputFormat);

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals(0, $assembly['outputFormat']['x']);
        $this->assertEquals(0, $assembly['outputFormat']['y']);
        $this->assertEquals($outputFormat, $assembly['outputFormat']['outputPrintingType']);
    }

    public function testWithOutputFormatWithBadOutputFormat()
    {
        $this->printFormats
            ->expects($this->any())
            ->method('toOptionArray')
            ->will($this->returnValue([['value' => '__outputFormat__']]));

        try {
            $this->generateLabelPayload
                ->withOutputFormat('__unknownOutputFormat__');
        } catch (\Exception $e) {
            $this->assertEquals(
                __('Bad output format'),
                (string) $e->getMessage()
            );

            return;
        }

        $this->fail('An exception should have occured!');
    }

    public function testWithOrderNumber()
    {
        $this->generateLabelPayload
            ->withOrderNumber('__orderNumber__');

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals('__orderNumber__', $assembly['letter']['service']['orderNumber']);
    }

    public function testWithPackage()
    {
        $package = new \Magento\Framework\DataObject();

        $items = [
            ['weight' => 19, 'weight_unit' => 'KILOGRAM'],
            ['weight' => 21, 'weight_unit' => 'KILOGRAM'],
            ['weight' => 2, 'weight_unit' => 'KILOGRAM'],
        ];

        $this->generateLabelPayload
            ->withPackage($package, $items);

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals(42, $assembly['letter']['parcel']['weight']);
    }

    public function testWithPackageCheckMax2Decimals()
    {
        $package = new \Magento\Framework\DataObject();

        $items = [
            ['weight' => 19.002, 'weight_unit' => 'KILOGRAM'],
            ['weight' => 21.009, 'weight_unit' => 'KILOGRAM'],
            ['weight' => 2, 'weight_unit' => 'KILOGRAM'],
        ];

        $this->generateLabelPayload
            ->withPackage($package, $items);

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals(42.01, $assembly['letter']['parcel']['weight']);
    }

    public function testWithPackageCheckZeroWeight()
    {
        $package = new \Magento\Framework\DataObject();

        $items = [
            ['weight' => 0, 'weight_unit' => 'KILOGRAM'],
            ['weight' => 0, 'weight_unit' => 'KILOGRAM'],
            ['weight' => 0, 'weight_unit' => 'KILOGRAM'],
        ];

        $this->generateLabelPayload
            ->withPackage($package, $items);

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals(0.01, $assembly['letter']['parcel']['weight']);
    }

    public function testWithPackageCheckWeightConversion()
    {
        $package = new \Magento\Framework\DataObject();

        $items = [
            ['weight' => 1, 'weight_unit' => 'KILOGRAM'],
            ['weight' => 2, 'weight_unit' => 'POUND'],
        ];

        $this->generateLabelPayload
            ->withPackage($package, $items);

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals(1.91, $assembly['letter']['parcel']['weight']);
    }

    public function testWithPackageUsingCustomsDeclaration()
    {
        $package = new \Magento\Framework\DataObject();

        $items = [
            ['weight' => 1, 'weight_unit' => 'KILOGRAM'],
            ['weight' => 2, 'weight_unit' => 'KILOGRAM'],
        ];

        $this->generateLabelPayload
            ->withPackage($package, $items, true);

        $expected = [
            // TODO by 2685
        ];

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals($expected, $assembly['letter']['customsDeclarations']);
    }

    public function testWithInsuranceValue()
    {
        $this->generateLabelPayload
            ->withInsuranceValue(42);

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals(4200, $assembly['letter']['parcel']['insuranceValue']);
    }

    public function testWithRecommendation()
    {
        $recommendationLevel = '__recommendationLevel__';


        $this->registeredMailLevel
            ->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue([$recommendationLevel => null]));


        $this->generateLabelPayload
            ->withRecommendationLevel($recommendationLevel);

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals($recommendationLevel, $assembly['letter']['parcel']['recommendationLevel']);
    }

    public function testWithRecommendationLevelWithUnknownLevel()
    {
        $this->registeredMailLevel
            ->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue(['__recommendationLevel__' => null]));

        try {
            $this->generateLabelPayload
                ->withRecommendationLevel('__unknownRecommendationLevel__');
        } catch (\Exception $e) {
            $this->assertEquals(
                __('Bad recommendation level'),
                (string) $e->getMessage()
            );

            return;
        }

        $this->fail('An exception should have occured!');
    }

    public function testWithRecommendationLevelAndInsuranceValue()
    {
        $this->registeredMailLevel
            ->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue(['__recommendationLevel__' => null]));

        try {
            $this->generateLabelPayload
                ->withInsuranceValue(42)
                ->withRecommendationLevel('__recommendationLevel__');
        } catch (\Exception $e) {
            $this->assertEquals(
                __('RecommendationLevel and InsuranceValue are mutually incompatible.'),
                (string) $e->getMessage()
            );

            return;
        }

        $this->fail('An exception should have occured!');
    }

    public function testWithInsuranceValueAndRecommendationLevel()
    {
        $this->registeredMailLevel
            ->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue(['__recommendationLevel__' => null]));

        try {
            $this->generateLabelPayload
                ->withRecommendationLevel('__recommendationLevel__')
                ->withInsuranceValue(42);
        } catch (\Exception $e) {
            $this->assertEquals(
                __('RecommendationLevel and InsuranceValue are mutually incompatible.'),
                (string) $e->getMessage()
            );

            return;
        }

        $this->fail('An exception should have occured!');
    }

    public function testWithCODAmount()
    {
        $this->generateLabelPayload
            ->withCODAmount(42);

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertTrue($assembly['letter']['parcel']['COD']);
        $this->assertEquals(4200, $assembly['letter']['parcel']['CODAmount']);
    }

    public function testWithCODAmountNegativeAmount()
    {
        $this->generateLabelPayload
            ->withCODAmount(- 42);

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertArrayNotHasKey('COD', $assembly['letter']['parcel']);
        $this->assertArrayNotHasKey('CODAmount', $assembly['letter']['parcel']);
    }

    public function testWithReturnReceipt()
    {
        $this->generateLabelPayload
            ->withReturnReceipt();

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertTrue($assembly['letter']['parcel']['returnReceipt']);
    }

    public function testWithInstructions()
    {
        $this->generateLabelPayload
            ->withInstructions('__instructions__');

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals('__instructions__', $assembly['letter']['parcel']['instructions']);
    }

    public function testWithAddressee()
    {
        $data = [
            'companyName' => '__companyName__',
            'firstName'   => '__firstName__',
            'lastName'    => '__lastName__',
            'street'      => '__street_number_and_name__',
            'street2'     => '__some_other_street_info__',
            'countryCode' => '__countryCode__',
            'city'        => '__city__',
            'zipCode'     => '__zipCode__',
            'email'       => '__email__',
        ];

        $this->generateLabelPayload
            ->withAddressee($data);

        $expected = array_merge($data);
        $expected['line2'] = $data['street'];
        unset($expected['street']);
        $expected['line3'] = $data['street2'];
        unset($expected['street2']);

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals($expected, $assembly['letter']['addressee']['address']);
    }

    public function testWithSender()
    {
        $data = [
            'companyName' => '__companyName__',
            'street'      => '__street_number_and_name__',
            'street2'     => '__some_other_street_info__',
            'countryCode' => '__countryCode__',
            'city'        => '__city__',
            'zipCode'     => '__zipCode__',
            'email'       => '__email__',
        ];

        $this->generateLabelPayload
            ->withSender($data);

        $expected = array_merge($data);
        $expected['line2'] = $data['street'];
        unset($expected['street']);
        $expected['line3'] = $data['street2'];
        unset($expected['street2']);

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals($expected, $assembly['letter']['sender']['address']);
    }

    public function testWithSenderUsingDefaultSender()
    {
        $expected = [
            'companyName' => '__companyName__',
            'line2'       => '__street_number_and_name__',
            'line3'       => '__some_other_street_info__',
            'countryCode' => '__countryCode__',
            'city'        => '__city__',
            'zipCode'     => '__zipCode__',
            'email'       => '__email__',
        ];

        $this->helperData
            ->method('getConfigValue')
            ->will($this->returnCallback(
                function ($k) use ($expected) {
                    switch ($k) {
                        case 'general/store_information/name':
                            return $expected['companyName'];
                        case 'general/store_information/country_id':
                            return $expected['countryCode'];
                        case 'general/store_information/postcode':
                            return $expected['zipCode'];
                        case 'general/store_information/city':
                            return $expected['city'];
                        case 'general/store_information/street_line1':
                            return $expected['line2'];
                        case 'general/store_information/street_line2':
                            return $expected['line3'];
                        case 'sales_email/shipment_comment/identity':
                            return $expected['email'];
                    }
                }
            ));


        $this->generateLabelPayload
            ->withSender();

        $assembly = $this->generateLabelPayload->assemble();
        $this->assertEquals($expected, $assembly['letter']['sender']['address']);
    }

    public function testFtdForOverseeDestinations()
    {
        $overseeDestinationProductCodes = [
            'CDS',
            'COM',
            'CORI',
            'ECO',
        ];

        foreach ($overseeDestinationProductCodes as $overseeDestinationProductCode) {
            $this->generateLabelPayload
                ->withProductCode($overseeDestinationProductCode);

            $assembly = $this->generateLabelPayload->assemble();
            $this->assertTrue($assembly['letter']['parcel']['ftd']);
        }

        $nonOverseeDestinationProductCodes = array_diff(
            self::ALL_PRODUCT_CODES,
            $overseeDestinationProductCodes
        );

        foreach ($nonOverseeDestinationProductCodes as $nonOverseeDestinationProductCode) {
            $this->generateLabelPayload
                ->withProductCode($nonOverseeDestinationProductCode);

            $assembly = $this->generateLabelPayload->assemble();
            $this->assertArrayNotHasKey('ftd', $assembly['letter']['parcel']);
        }
    }

    public function testPickupLocationIdAgainstProductCode()
    {
        $productCodesNeedingPickupLocationIdSet = [
            'HD',
        ];

        foreach ($productCodesNeedingPickupLocationIdSet as $productCode) {
            try {
                $assembly = $this->generateLabelPayload
                    ->withProductCode($productCode)
                    ->withSender(self::SOME_MINIMAL_SENDER)
                    ->withAddressee(self::SOME_MINIMAL_ADDRESSEE)
                    ->withPickupLocationId(null)
                    ->checkConsistency();
                $this->fail('An exception should have occured!');
            } catch (\PHPUnit\Framework\AssertionFailedError $e) {
                throw $e;
            } catch (\Exception $e) {
                // OK, this exception was expected
            }
        }

        $productCodesNotNeedingPickupLocationIdSet = array_diff(
            self::ALL_PRODUCT_CODES,
            $productCodesNeedingPickupLocationIdSet
        );
        foreach ($productCodesNotNeedingPickupLocationIdSet as $productCode) {
            try {
                $assembly = $this->generateLabelPayload
                    ->withProductCode($productCode)
                    ->withSender(self::SOME_MINIMAL_SENDER)
                    ->withAddressee(self::SOME_MINIMAL_ADDRESSEE)
                    ->withPickupLocationId('some-pickup-location-id')
                    ->checkConsistency();

                return $this->fail('An exception should have occured!');
            } catch (\PHPUnit\Framework\AssertionFailedError $e) {
                throw $e;
            } catch (\Exception $e) {
                // OK, this exception was expected
            }
        }
    }

    public function testCommercialNameAgainstProductCode()
    {
        $productCodesNeedingCommercialNameSet = [
            'HD',
        ];

        $productCodesNeedingPickupLocationIdSet = [
            'HD',
        ];

        foreach ($productCodesNeedingCommercialNameSet as $productCode) {
            try {
                $assembly = $this->generateLabelPayload
                    ->withProductCode($productCode)
                    ->withSender(self::SOME_MINIMAL_SENDER)
                    ->withAddressee(self::SOME_MINIMAL_ADDRESSEE)
                    ->withCommercialName(null)
                    ->checkConsistency();
                $this->fail('An exception should have occured!');
            } catch (\PHPUnit\Framework\AssertionFailedError $e) {
                throw $e;
            } catch (\Exception $e) {
                // OK, this exception was expected
            }
        }

        $productCodesNotNeedingCommercialNameSet = array_diff(
            self::ALL_PRODUCT_CODES,
            $productCodesNeedingCommercialNameSet
        );
        foreach ($productCodesNotNeedingCommercialNameSet as $productCode) {
            // it can be not set
            $assembly = $this->generateLabelPayload
                ->withProductCode($productCode)
                ->withSender(self::SOME_MINIMAL_SENDER)
                ->withAddressee(self::SOME_MINIMAL_ADDRESSEE)
                ->withPickupLocationId(in_array($productCode, $productCodesNeedingPickupLocationIdSet) ? 'some-pickup-location-id' : null)
                ->withCommercialName(null)
                ->checkConsistency();

            // or it can be set
            $assembly = $this->generateLabelPayload
                ->withProductCode($productCode)
                ->withSender(self::SOME_MINIMAL_SENDER)
                ->withAddressee(self::SOME_MINIMAL_ADDRESSEE)
                ->withPickupLocationId(in_array($productCode, $productCodesNeedingPickupLocationIdSet) ? 'some-pickup-location-id' : null)
                ->withCommercialName('some-commercial-name')
                ->checkConsistency();
        }
    }

    public function testSenderAddressRequiredFileds()
    {
        $requiredFields = ['companyName', 'street', 'countryCode', 'zipCode', 'city'];

        foreach ($requiredFields as $requidField) {
            try {
                $sender = array_merge(self::SOME_MINIMAL_SENDER);
                unset($sender[$requidField]);

                $assembly = $this->generateLabelPayload
                    ->withProductCode('DOS')
                    ->withSender($sender)
                    ->withAddressee(self::SOME_MINIMAL_ADDRESSEE)
                    ->checkConsistency();

                return $this->fail('An exception should have occured!');
            } catch (\PHPUnit\Framework\AssertionFailedError $e) {
                throw $e;
            } catch (\Exception $e) {
                // OK, this exception was expected
            }
        }
    }

    public function testAddresseeAddressRequiredFileds()
    {
        $requiredFields = ['street', 'countryCode', 'zipCode', 'city'];

        foreach ($requiredFields as $requidField) {
            try {
                $addressee = array_merge(self::SOME_MINIMAL_ADDRESSEE);
                unset($addressee[$requidField]);

                $assembly = $this->generateLabelPayload
                    ->withProductCode('DOS')
                    ->withSender(self::SOME_MINIMAL_SENDER)
                    ->withAddressee($addressee)
                    ->checkConsistency();

                return $this->fail('An exception should have occured!');
            } catch (\PHPUnit\Framework\AssertionFailedError $e) {
                throw $e;
            } catch (\Exception $e) {
                // OK, this exception was expected
            }
        }

        $addressee = array_merge(self::SOME_MINIMAL_ADDRESSEE);
        unset($addressee['companyName']);

        $assembly = $this->generateLabelPayload
            ->withProductCode('DOS')
            ->withSender(self::SOME_MINIMAL_SENDER)
            ->withAddressee(array_merge(
                                $addressee,
                                ['firstName' => 'some-firstname', 'lastName' => 'some-lastname']
                            ))
            ->checkConsistency();

        try {
            $assembly = $this->generateLabelPayload
                ->withProductCode('DOS')
                ->withSender(self::SOME_MINIMAL_SENDER)
                ->withAddressee($addressee)
                ->checkConsistency();

            return $this->fail('An exception should have occured!');
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            throw $e;
        } catch (\Exception $e) {
            // OK, this exception was expected
        }
    }
}
