<?php
namespace App\Command\ShopFive;

use App\Repository\DiscountHistoryRepository;
use App\Repository\DiscountLogRepository;
use App\Repository\DiscountRepository;
use App\Repository\ProductRepository;
use App\Http\YandexDisk;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GetUploadedDiscounts
 * @package App\Command\ShopFive\Five
 */
class GetUploadedDiscounts extends Command
{
    protected static $defaultName = 'shop:five:get:uploaded:discounts';

    private DiscountLogRepository $discountLogRepository;
    private DiscountRepository $discountRepository;
    private DiscountHistoryRepository $discountHistoryRepository;
    private ProductRepository $productRepository;

    public function __construct(
        DiscountLogRepository $discountLogRepository,
        DiscountRepository $discountRepository,
        DiscountHistoryRepository $discountHistoryRepository,
        ProductRepository $productRepository,
    )
    {
        $this->discountLogRepository = $discountLogRepository;
        $this->discountRepository = $discountRepository;
        $this->discountHistoryRepository = $discountHistoryRepository;
        $this->productRepository = $productRepository;
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('locationId');
    }

    /**
     * @throws GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cityId = $input->getArgument('locationId');
        $uploadedFileName = $cityId .'.json';

        $yandexDisk = new YandexDisk($_ENV['YD_TOKEN'], $uploadedFileName);
        $results = json_decode($yandexDisk->downloadFile(), true);
        $yandexDisk->deleteFile($uploadedFileName);

        // Логируем данные на случай если что-то пойдет не так
        $this->discountLogRepository->logByLocationId($cityId, $results);
        // Сохраняем свежие данные по скидкам
        $totalSaved = $this->discountRepository->updateByLocationId($cityId, $results);
        echo "Saved $totalSaved records \n";
        // Сохраняем товары для каталога (справочника)
        $totalNew = $this->productRepository->updateByLocationId($cityId, $results);
        echo "Saved $totalNew new products \n";
        // Обновляем историю скидок
        $totalHistory = $this->discountHistoryRepository->updateByLocationId($cityId, $results);
        echo "Saved $totalHistory history rows \n";

        return 0;
    }
}