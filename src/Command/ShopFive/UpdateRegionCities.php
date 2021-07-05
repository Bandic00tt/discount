<?php
namespace App\Command\ShopFive;

use App\Http\ApiClient;
use App\Service\DataHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateRegionCities extends Command
{
    protected static $defaultName = 'shop:five:update:region:cities';

    private ApiClient $apiClient;
    private DataHandler $dataHandler;

    public function __construct(ApiClient $apiClient, DataHandler $dataHandler)
    {
        parent::__construct();
        $this->apiClient = $apiClient;
        $this->dataHandler = $dataHandler;
    }

    protected function configure()
    {
        $this->addArgument('regionId', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $regionId = $input->getArgument('regionId');
        $data = json_decode($this->apiClient->getRegionCities($regionId), true);
        $this->dataHandler->clearRegionCities($regionId);
        $result = $this->dataHandler->updateRegionCities($data['items']);

        echo "Got $result region cities\n";

        return 0;
    }
}