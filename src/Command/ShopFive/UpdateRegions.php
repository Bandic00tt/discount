<?php
namespace App\Command\ShopFive;

use App\Http\ApiClient;
use App\Repository\RegionRepository;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateRegions extends Command
{
    protected static $defaultName = 'shop:five:update:regions';

    private ApiClient $apiClient;
    private RegionRepository $regionRepository;

    public function __construct(ApiClient $apiClient, RegionRepository $cityRepository)
    {
        parent::__construct();
        $this->apiClient = $apiClient;
        $this->regionRepository = $cityRepository;
    }

    /**
     * @throws GuzzleException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = json_decode($this->apiClient->getRegions(), true);
        $this->regionRepository->clear();
        $result = $this->regionRepository->update($data['regions']);

        echo "Got $result regions\n";

        return $result;
    }
}