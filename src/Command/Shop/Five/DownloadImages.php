<?php
namespace App\Command\Shop\Five;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadImages extends Command
{
    const PRODUCTS_PER_QUERY = 20;
    const PRODUCT_IMAGES_PATH = __DIR__ .'/../../../../public/img/products/'; // todo: use root dir alias

    protected static $defaultName = 'shop:five:download:images';
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Product[] $products */
        $products = $this->em
            ->getRepository(Product::class)
            ->findBy(['is_img_local' => 0], ['updated_at' => 'ASC'], self::PRODUCTS_PER_QUERY);

        foreach ($products as $product) {
            $pathInfo = pathinfo($product->getImgLink());
            $fileName = $pathInfo['basename'];

            try {
                $image = file_get_contents($product->getImgLink());
            } catch (Exception $e) {
                echo $e->getMessage() ."\n";
                continue;
            }

            if (file_put_contents(self::PRODUCT_IMAGES_PATH . $fileName, $image)) {
                $product->setIsImgLocal(1);
                echo sprintf("Saved preview file for product with ID %d", $product->getProductId()) ."\n";
            }
        }

        $this->em->flush();
        $this->em->clear();

        return 0;
    }
}