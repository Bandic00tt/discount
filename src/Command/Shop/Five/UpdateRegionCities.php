<?php
namespace App\Command\Shop\Five;

use App\Service\Shop\Five\ApiClient;
use App\Service\Shop\Five\DataHandler;
use Symfony\Component\Console\Command\Command;
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = json_decode($this->apiClient->getRegionCities($this->getRegionId()), true);
        $this->dataHandler->clearRegionCities();
        $result = $this->dataHandler->updateRegionCities($data['items']);

        echo "Got $result region cities\n";

        return $result;
    }

    private function getRegionId(): int
    {
        return ApiClient::DEFAULT_REGION_ID;
    }
}