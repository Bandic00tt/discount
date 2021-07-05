<?php
namespace App\Command\ShopFive;

use App\Entity\Category;
use App\Http\ApiClient;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateChildCategories extends Command
{
    protected static $defaultName = 'shop:five:update:child:categories';
    private ApiClient $apiClient;
    private EntityManagerInterface $em;

    public function __construct(ApiClient $apiClient, EntityManagerInterface $em)
    {
        parent::__construct();
        $this->apiClient = $apiClient;
        $this->em = $em;
    }

    protected function configure()
    {
        $this->addArgument('parentCategoryId');
    }

    /**
     * @throws GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $parentCategoryId = $input->getArgument('parentCategoryId');

        $jsonData = $this->apiClient->getChildCategories($parentCategoryId);
        $childCategories = json_decode($jsonData, true);

        foreach ($childCategories as $childCategory) {
            $category = new Category();
            $category->setParentCategoryId($parentCategoryId);
            $category->setCategoryId($childCategory['group_code']);
            $category->setName($childCategory['group_name']);
            $category->setCreatedAt(time());
            $category->setUpdatedAt(time());

            $this->em->persist($category);
        }

        $this->em->flush();
        $this->em->clear();

        echo sprintf("Inserted %d rows for parent category ID %d", count($childCategories), $parentCategoryId) ."\n";

        return 0;
    }
}