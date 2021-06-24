<?php
namespace App\Command;

use App\Service\Transliterate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    protected static $defaultName = 'app:test';

    private EntityManagerInterface $em;

    /**
     * TestCommand constructor.
     */
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cities = require __DIR__ .'/../../cities.php';
        $filePath = __DIR__ .'/../../translit.txt';

        foreach ($cities as $city) {
            $tCity = strtolower(Transliterate::process($city)) . PHP_EOL;
            file_put_contents($filePath, $tCity, FILE_APPEND);
        }

        return 0;
    }
}
