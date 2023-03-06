<?php

namespace App\Command;

use App\Service\FollowUpManager;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FollowUpEmailCommand extends Command
{
    private $followUpManager;
    private $em;

    public function __construct(
        EntityManagerInterface $em,
        FollowUpManager $followUpManager
    ) {
        parent::__construct();

        $this->em = $em;
        $this->followUpManager = $followUpManager;
    }

    protected function configure()
    {
        $this
            ->setName('egd:follow-up-relaunch-email');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
            $this->followUpManager->sendRelaunchByEmail();
    }
}
