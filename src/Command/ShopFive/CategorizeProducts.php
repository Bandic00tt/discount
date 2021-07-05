<?php /** @noinspection DuplicatedCode */

namespace App\Command\ShopFive;

use App\Entity\Category;
use App\Http\ApiClient;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CategorizeProducts extends Command
{
    protected static $defaultName = 'shop:five:categorize:products';

    private ApiClient $apiClient;
    private EntityManagerInterface $em;
    private ProductRepository $productRepository;

    public function __construct(
        ApiClient $apiClient,
        EntityManagerInterface $em,
        ProductRepository $productRepository,
    )
    {
        parent::__construct();
        $this->apiClient = $apiClient;
        $this->em = $em;
        $this->productRepository = $productRepository;
    }

    protected function configure()
    {
        $this->addArgument('locationId')
            ->addArgument('categoryId');
    }

    /**
     * @throws NonUniqueResultException
     * @throws GuzzleException
     * @throws EntityNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $locationId = $input->getArgument('locationId');
        $category = $this->getCategory($input->getArgument('categoryId'));

        echo sprintf("Start categorizing product for the category %s", $category->getName()) . PHP_EOL;

        $results = [];
        $page = 1;
        while (true) {
            $data = json_decode($this->apiClient->getDiscounts($locationId, $page, $category->getCategoryId()), true);

            if (empty($data['results'])) {
                echo "Parsing finished\n";
                break;
            }

            $totalOnPage = count($data['results']);
            $results = array_merge($results, $data['results']);

            echo "Got $totalOnPage records on $page page\n";

            ++$page;

            sleep(2);
        }

        $total = $this->productRepository->getNumberOfCategorizedProducts($category->getCategoryId(), $results);

        echo sprintf("%d products have been successfully categorized", $total);

        return 0;
    }

    /**
     * @param int|null $categoryId
     * @return Category
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    private function getCategory(?int $categoryId): Category
    {
        $query = $this->em
            ->createQueryBuilder()
            ->select('c')
            ->from(Category::class, 'c')
            ->andWhere('c.parent_category_id IS NOT NULL');

        if ($categoryId) {
            $query->andWhere('c.category_id = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        $query->orderBy('c.updated_at', 'ASC')
            ->setMaxResults(1);

        /** @var Category $category */
        $category = $query->getQuery()->getOneOrNullResult();

        if ($category) {
            $category->setUpdatedAt(time());
            $this->em->flush();
            $this->em->clear();

            return $category;
        }

        throw new EntityNotFoundException('Category not found');
    }
}