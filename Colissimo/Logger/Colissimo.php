<?php

/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Logger;

use Monolog\Logger;
use LaPoste\Colissimo\Logger\Handler\HandlerFactory;

class Colissimo extends Logger
{
    protected $helperData;

    /**
     * @var array
     */
    protected $defaultHandlerTypes = [
        'error',
        'info',
        'debug',
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(
        \LaPoste\Colissimo\Helper\Data $helperData,
        HandlerFactory $handlerFactory,
        $name = 'Colissimo',
        array $handlers = [],
        array $processors = []
    ) {
        $this->helperData = $helperData;
        if ($this->helperData->getAdvancedConfigValue('lpc_debug/debugMode')) {
            foreach ($this->defaultHandlerTypes as $handlerType) {
                if (!array_key_exists($handlerType, $handlers)) {
                    $handlers[$handlerType] = $handlerFactory->create($handlerType);
                }
            }
        }
        parent::__construct($name, $handlers, $processors);
    }
}
