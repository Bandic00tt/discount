<?php
namespace App\Command\Shop\Five;

use App\Entity\City;
use App\Service\Shop\Five\ApiClient;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
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
    private City $location;

    public function __construct(ApiClient $apiClient, DataHandler $dataHandler)
    {
        parent::__construct();
        $this->apiClient = $apiClient;
        $this->dataHandler = $dataHandler;
        $this->location = $this->getLocation();
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
        echo "Started discount parsing for location: {$this->location->getName()}\n";

        $cityId = $this->location->getCityId();
        $this->dataHandler->clearDiscounts($cityId);

        $results = [];
        $page = 1;
        while (true) {
            $data = json_decode(
                $this->apiClient->getDiscounts($cityId, $page),
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
        $this->dataHandler->logDiscounts($cityId, $results);
        // Сохраняем свежие данные по скидкам
        $totalSaved = $this->dataHandler->updateDiscounts($cityId, $results);
        echo "Saved $totalSaved records from $page pages \n";
        // Сохраняем товары для каталога (справочника)
        $totalNew = $this->dataHandler->updateProducts($cityId, $results);
        echo "Saved $totalNew new products \n";
        // Обновляем историю скидок
        $totalHistory = $this->dataHandler->updateHistory($cityId, $results);
        echo "Saved $totalHistory history rows \n";

        return 0;
    }

    /**
     * @return City
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    public function getLocation(): City
    {
        /** @var City $location */
        $location = $this->dataHandler->em
            ->createQueryBuilder()
            ->select('c')
            ->from(City::class, 'c')
            ->andWhere('c.city_id in (:cityIds)')
            ->setParameter('cityIds', array_keys(DataHandler::CITIES))
            ->orderBy('c.updated_at', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($location) {
            $location->setUpdatedAt(time());
            $this->dataHandler->em->flush();
            $this->dataHandler->em->clear();

            return $location;
        }

        throw new EntityNotFoundException('City not found');
    }
}