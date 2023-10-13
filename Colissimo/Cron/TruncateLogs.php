<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Cron;

class TruncateLogs
{
    const RELATIVE_FILEPATH = '/colissimo/';

    protected $directoryList;
    protected $helperData;

    public function __construct(
        \LaPoste\Colissimo\Helper\Data $helperData,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList
    ) {
        $this->helperData = $helperData;
        $this->directoryList = $directoryList;
    }

    public function execute()
    {
        $keepDaysOfLog = (int) $this->helperData->getAdvancedConfigValue('lpc_debug/keepDaysOfLog');
        if ($keepDaysOfLog > 0) {
            $limitDate = new \DateTime();
            $limitDate->sub(new \DateInterval("P{$keepDaysOfLog}D"));
            $limitDateStr = $limitDate->format('Y-m-d H:i:s');


            $this->keepDataAfter($limitDateStr);
        }
    }

    private function keepDataAfter($limitDateStr)
    {
        $logFileDirectoryPath = $this->directoryList->getPath(
                \Magento\Framework\App\Filesystem\DirectoryList::LOG
            )
                                . self::RELATIVE_FILEPATH;

        $logFiles = glob($logFileDirectoryPath . '*.log');

        foreach ($logFiles as $logFile) {
            $lines = file($logFile);
            if ($lines) {
                $flipped = array_reverse($lines);

                for ($i = 0; $i < count($flipped); $i ++) {
                    $matchesNb = preg_match('|^\[(.*)\] |', $flipped[$i], $matches);
                    if (1 === $matchesNb) {
                        $lineDate = $matches[1];

                        if (strcmp($lineDate, $limitDateStr) <= - 1) {
                            break;
                        }
                    }
                }

                $interestingLines = array_reverse(array_slice($flipped, 0, $i));
                $concatenatedInterestingLines = implode($interestingLines);
                file_put_contents($logFile, $concatenatedInterestingLines);
            }
        }
    }
}
