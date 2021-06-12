<?php
namespace App\Command\Shop\Five;

use App\Service\Shop\Five\ApiClient;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\Shop\Five\DataHandler;

class ParseDiscounts extends Command
{
    protected static $defaultName = 'shop:five:parse:discounts';

    private ApiClient $apiClient;
    private DataHandler $dataHandler;
    private int $locationId;

    public function __construct(ApiClient $apiClient, DataHandler $dataHandler)
    {
        parent::__construct();
        $this->apiClient = $apiClient;
        $this->dataHandler = $dataHandler;
        $this->locationId = $this->getLocationId();
    }

    /**
     * todo
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Очищаем старые неактуальные данные
        $this->dataHandler->clearDiscounts($this->locationId);

        $results = [];
        $page = 1;
        while (true) {
            $data = json_decode(
                $this->apiClient->getDiscounts($this->locationId, $page),
                JSON_UNESCAPED_UNICODE
            );

            if (empty($data['results'])) {
                echo "Parsing finished\n";
                --$page;
                break;
            }

            $totalOnPage = count($data['results']);
            $results = array_merge($results, $data['results']);

            echo "Got $totalOnPage records on $page page\n";

            ++$page;

            sleep(2);
        }

        // Логируем данные на случай если что-то пойдет не так
        $this->dataHandler->logDiscounts($this->locationId, $results);
        // Сохраняем свежие данные по скидкам
        $totalSaved = $this->dataHandler->updateDiscounts($this->locationId, $results);
        echo "Saved $totalSaved records from $page pages \n";
        // Сохраняем товары для каталога (справочника)
        $totalNew = $this->dataHandler->updateProducts($this->locationId, $results);
        echo "Saved $totalNew new products \n";
        // Обновляем историю скидок
        $totalHistory = $this->dataHandler->updateHistory($this->locationId, $results);
        echo "Saved $totalHistory history rows \n";

        return 0;
    }

    /**
     * todo change
     * @return int
     */
    public function getLocationId(): int
    {
        return ApiClient::DEFAULT_LOCATION_ID;
    }
}