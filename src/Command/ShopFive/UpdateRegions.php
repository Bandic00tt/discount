<?php
namespace App\Command\ShopFive;

use App\Http\ApiClient;
use App\Service\DataHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateRegions extends Command
{
    protected static $defaultName = 'shop:five:update:regions';

    private ApiClient $apiClient;
    private DataHandler $dataHandler;

    public function __construct(ApiClient $apiClient, DataHandler $dataHandler)
    {
        parent::__construct();
        $this->apiClient = $apiClient;
        $this->dataHandler = $dataHandler;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = json_decode($this->apiClient->getRegions(), true);
        $this->dataHandler->clearRegions();
        $result = $this->dataHandler->updateRegions($data['regions']);

        echo "Got $result regions\n";

        return $result;
    }
}