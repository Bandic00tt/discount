<?php
namespace App\Command\Shop\Five;

use App\Service\Shop\Five\DataHandler;
use App\Service\YandexDisk;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * todo: improve
 * Class GetUploadedDiscounts
 * @package App\Command\Shop\Five
 */
class GetUploadedDiscounts extends Command
{
    protected static $defaultName = 'shop:five:get:uploaded:discounts';

    private DataHandler $dataHandler;

    public function __construct(DataHandler $dataHandler)
    {
        parent::__construct();
        $this->dataHandler = $dataHandler;
    }

    protected function configure()
    {
        $this->addArgument('locationId');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cityId = $input->getArgument('locationId');
        $uploadedFileName = $cityId .'.json';

        $yandexDisk = new YandexDisk($_ENV['YD_TOKEN'], $uploadedFileName);
        $results = json_decode($yandexDisk->downloadFile(), true);
        $yandexDisk->deleteFile($uploadedFileName);

        // Логируем данные на случай если что-то пойдет не так
        $this->dataHandler->logDiscounts($cityId, $results);
        // Сохраняем свежие данные по скидкам
        $totalSaved = $this->dataHandler->updateDiscounts($cityId, $results);
        echo "Saved $totalSaved records \n";
        // Сохраняем товары для каталога (справочника)
        $totalNew = $this->dataHandler->updateProducts($cityId, $results);
        echo "Saved $totalNew new products \n";
        // Обновляем историю скидок
        $totalHistory = $this->dataHandler->updateHistory($cityId, $results);
        echo "Saved $totalHistory history rows \n";

        return 0;
    }
}