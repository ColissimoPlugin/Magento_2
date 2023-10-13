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

use LaPoste\Colissimo\Cron\PurgeOldReturnLabels as PurgeOldReturnLabelsCron;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeOldReturnLabels extends Command
{
    protected $purgeOldReturnLabelsCron;

    public function __construct(PurgeOldReturnLabelsCron $purgeOldReturnLabelsCron)
    {
        $this->purgeOldReturnLabelsCron = $purgeOldReturnLabelsCron;

        return parent::__construct();
    }

    protected function configure()
    {
        $this->setName('lpc:purgeOldReturnLabels')->setDescription('Purge old return labels');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->purgeOldReturnLabelsCron->execute();
            $output->writeln('Purge successful');
        } catch (\Exception $exc) {
            $output->writeln($exc->getMessage());
        }
    }
}
