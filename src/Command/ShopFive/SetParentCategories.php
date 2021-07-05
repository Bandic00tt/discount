<?php

namespace App\Command\ShopFive;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetParentCategories extends Command
{
    protected static $defaultName = 'shop:five:set:parent:categories';

    private EntityManagerInterface $em;
    private CategoryRepository $categoryRepository;

    public function __construct(
        EntityManagerInterface $em,
        CategoryRepository $categoryRepository,
    )
    {
        parent::__construct();
        $this->em = $em;
        $this->categoryRepository = $categoryRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->categoryRepository->clearParentCategories();
        $parentCategories = require __DIR__ . '/../../../../parent_categories.php';

        foreach ($parentCategories as $id => $name) {
            $category = new Category();
            $category->setCategoryId($id);
            $category->setName($name);
            $category->setCreatedAt(time());
            $category->setUpdatedAt(time());

            $this->em->persist($category);
        }

        $this->em->flush();
        $this->em->clear();

        echo sprintf("%d parent categories inserted", count($parentCategories));

        return 0;
    }
}