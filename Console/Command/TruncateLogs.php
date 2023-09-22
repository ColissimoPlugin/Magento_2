<?php
/*******************************************************
 * Copyright (C) 2018 La Poste.
 *
 * This file is part of La Poste - Colissimo module.
 *
 * La Poste - Colissimo module can not be copied and/or distributed without the express
 * permission of La Poste.
 *******************************************************/

namespace LaPoste\Colissimo\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use LaPoste\Colissimo\Cron\TruncateLogs as CronTruncate;

class TruncateLogs extends Command
{
    protected $cronTruncate;

    public function __construct(CronTruncate $cronTruncate)
    {
        $this->cronTruncate = $cronTruncate;

        return parent::__construct();
    }

    protected function configure()
    {
        $this->setName('lpc:cleanlog')->setDescription('Clean old Colissimo logs');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->cronTruncate->execute();
            $output->writeln('Clean successful');
        } catch (\Exception $exc) {
            $output->writeln($exc->getMessage());
        }
    }
}
