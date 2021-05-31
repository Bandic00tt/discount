<?php
namespace App\Command\Shop\Five;

use App\Service\Shop\Five\ApiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\Shop\Five\DataHandler;

class ParseDiscounts extends Command
{
    protected static $defaultName = 'shop:five:parse:discounts';

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
        $page = 1;
        while (true) {
            $data = json_decode(
                $this->apiClient->getDiscounts($page),
                JSON_UNESCAPED_UNICODE
            );

            if (empty($data['results'])) {
                echo "Parsing finished\n";
                break;
            }

            $total = $this->dataHandler->saveDiscountData($data['results']);

            echo "Saved $total records on $page page\n";

            ++$page;

            sleep(2);
        }

        //$this->dataHandler->clearDiscountData();

        return 0;
    }


}