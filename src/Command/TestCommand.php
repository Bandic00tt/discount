<?php

namespace App\Command;

use App\Service\Shop\Five\DataHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TestCommand extends Command
{
    protected static $defaultName = 'app:test';

    private MailerInterface $mailer;
    private DataHandler $dataHandler;

    /**
     * TestCommand constructor.
     * @param MailerInterface $mailer
     */
    public function __construct(MailerInterface $mailer, DataHandler $dataHandler)
    {
        parent::__construct();
        $this->mailer = $mailer;
        $this->dataHandler= $dataHandler;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        var_dump($this->dataHandler->getExistingDiscountIds(8169));

        return 0;
    }
}
