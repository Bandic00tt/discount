<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TestCommand extends Command
{
    protected static $defaultName = 'app:test';

    private MailerInterface $mailer;

    /**
     * TestCommand constructor.
     * @param MailerInterface $mailer
     */
    public function __construct(MailerInterface $mailer)
    {
        parent::__construct();
        $this->mailer = $mailer;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mail = (new Email())
            ->from('admin@discount.ru')
            ->to('someone@discount.ru')
            ->subject('test')
            ->text('test text');

        $this->mailer->send($mail);

        return 0;
    }
}
