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

class LabellingApiTest extends \PHPUnit\Framework\TestCase
{
    protected $logger;
    protected $labellingApi;

    public function setUp()
    {
        $this->logger = $this->createMock(\LaPoste\Colissimo\Logger\Colissimo::class);

        $this->labellingApi = $this->getMockBuilder(
            \LaPoste\Colissimo\Model\Carrier\LabellingApi::class
        )
                                   ->setMethods()
                                   ->setConstructorArgs(
                                       [
                                           'logger' => $this->logger,
                                       ]
                                   )
                                   ->getMock();
    }


    public function testParseMultipartBody()
    {
        $body = $this->generateMultipartBody();

        $parts = $this->labellingApi
            ->parseMultipartBody($body);


        $expectedJson = '{"success":"some-json"}';
        $this->assertArrayHasKey('<jsonInfos>', $parts);
        $this->assertEquals($expectedJson, $parts['<jsonInfos>']['body']);

        $expectedLabel = 'some-binary-content';
        $this->assertArrayHasKey('<label>', $parts);
        $this->assertEquals($expectedLabel, $parts['<label>']['body']);
    }


    public function testGenerateLabel()
    {
        // we need this trick to override built-in curl functions
        // only for this test
        require(__DIR__ . '/CurlMocker.php');

        $generateLabelPayload = $this->createMock(
            \LaPoste\Colissimo\Model\Carrier\GenerateLabelPayload::class
        );

        [$jsonInfos, $label] = $this->labellingApi
            ->generateLabel($generateLabelPayload);

        $this->assertEquals('some-json', $jsonInfos->success);
        $this->assertEquals('some-binary-content', $label);
    }


    protected function generateMultipartBody()
    {
        $body = <<<'END_BODY'
--uuid:115fc49e-f2cc-4ab0-9971-8db0a7959753
Content-Type: application/json;charset=UTF-8
Content-Transfer-Encoding: binary
Content-ID: <jsonInfos>

{"success":"some-json"}
--uuid:115fc49e-f2cc-4ab0-9971-8db0a7959753
Content-Type: application/octet-stream
Content-Transfer-Encoding: binary
Content-ID: <label>

some-binary-content

--uuid:115fc49e-f2cc-4ab0-9971-8db0a7959753--
END_BODY;

        $body = str_replace("\n", "\r\n", $body); // these are the body response "normal" ends of line

        return $body;
    }
}
