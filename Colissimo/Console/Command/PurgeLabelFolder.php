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

use LaPoste\Colissimo\Cron\PurgeLabelFolder as PurgeLabelFolderCron;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeLabelFolder extends Command
{
    protected $purgeLabelFolderCron;

    public function __construct(PurgeLabelFolderCron $purgeLabelFolderCron)
    {
        $this->purgeLabelFolderCron = $purgeLabelFolderCron;

        return parent::__construct();
    }

    protected function configure()
    {
        $this->setName('lpc:purge:labelFolder')->setDescription('Purge label folder');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->purgeLabelFolderCron->execute();
            $output->writeln('Purge successful');
        } catch (\Exception $exc) {
            $output->writeln($exc->getMessage());
        }
    }
}
