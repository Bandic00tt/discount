<?php
namespace App\Command\ShopFive;

use App\Http\ApiClient;
use App\Repository\CityRepository;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateRegionCities extends Command
{
    protected static $defaultName = 'shop:five:update:region:cities';

    private ApiClient $apiClient;
    private CityRepository $cityRepository;

    public function __construct(ApiClient $apiClient, CityRepository $cityRepository)
    {
        parent::__construct();
        $this->apiClient = $apiClient;
        $this->cityRepository = $cityRepository;
    }

    protected function configure()
    {
        $this->addArgument('regionId', InputArgument::REQUIRED);
    }

    /**
     * @throws GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $regionId = $input->getArgument('regionId');
        $data = json_decode($this->apiClient->getRegionCities($regionId), true);
        $this->cityRepository->clearByRegionId($regionId);
        $result = $this->cityRepository->update($data['items']);

        echo "Got $result region cities\n";

        return 0;
    }
}