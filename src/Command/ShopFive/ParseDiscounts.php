<?php
namespace App\Command\ShopFive;

use App\Entity\City;
use App\Http\ApiClient;
use App\ValueObject\Cities;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\DataHandler;

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

    protected function configure()
    {
        $this->addArgument('locationId');
    }

    /**
     * todo
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws EntityNotFoundException
     * @throws GuzzleException
     * @throws NonUniqueResultException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $location = $this->getLocation($input->getArgument('locationId'));

        echo "Started discount parsing for location: {$location->getName()}\n";

        $cityId = $location->getCityId();
        // Очищаем старые неактуальные данные
        $this->dataHandler->clearDiscounts($cityId);

        $results = [];
        $page = 1;
        while (true) {
            $data = json_decode($this->apiClient->getDiscounts($cityId, $page), true);

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
     * @param int|null $locationId
     * @return City
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    public function getLocation(?int $locationId): City
    {
        $query = $this->dataHandler->em
            ->createQueryBuilder()
            ->select('c')
            ->from(City::class, 'c');

        if ($locationId) {
            $query->andWhere('c.city_id = :cityId')
                ->setParameter('cityId', $locationId);
        } else {
            $query->andWhere('c.city_id in (:cityIds)')
                ->setParameter('cityIds', array_keys(Cities::list()));
        }
            $query->orderBy('c.updated_at', 'ASC')
            ->setMaxResults(1);

        /** @var City $location */
        $location = $query->getQuery()->getOneOrNullResult();

        if ($location) {
            $location->setUpdatedAt(time());
            $this->dataHandler->em->flush();
            $this->dataHandler->em->clear();

            return $location;
        }

        throw new EntityNotFoundException('City not found');
    }
}