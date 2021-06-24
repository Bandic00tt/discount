<?php
namespace App\Command\ExternalParsers;

use App\Entity\ProductExternal;
use DiDom\Exceptions\InvalidSelectorException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DiDom\Document;

class Parse5kaSale extends Command
{
    private const URL = 'https://5ka-sale.ru';
    private const SUB_URL = '/special-offers/';

    protected static $defaultName = 'parse:5ka:sale';
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $document = new Document(self::URL . self::SUB_URL, true);
        $menu = $document->find('.list-group-item-menu');
        echo sprintf("Found %d pages for parsing", count($menu)) . PHP_EOL;
        $results = [];

        foreach ($menu as $item) {
            $link = $item->find('a')[0]->attr('href');
            echo "Started parsing for the page with url ". $link . PHP_EOL;
            $result = $this->parseProducts($link);

            $results = array_merge($results, $result);
            sleep(1);
        }

        echo sprintf("Found %d products", count($results)) . PHP_EOL;

        $this->saveProducts($results);

        return 0;
    }

    /**
     * @param string $link
     * @return array
     * @throws InvalidSelectorException
     */
    private function parseProducts(string $link): array
    {
        $result = [];
        $page = new Document(self::URL . $link, true);
        $products = $page->find('.card-body');

        foreach ($products as $product) {
            if ($product->has('p.pr_title')) {
                $productLink = $product
                    ->find('p.pr_title')[0]
                    ->find('a')[0];

                $urlParts = explode('/', $productLink->attr('href'));
                $productPagePart = end($urlParts);
                $chunks = explode('-', $productPagePart);

                $result[] = [
                    'productId' => $chunks[0],
                    'productName' => $productLink->text()
                ];
            }
        }

        if (empty($result)) {
            echo "Empty result\n";
        }

        return $result;
    }

    private function saveProducts(array $results)
    {
        foreach ($results as $result) {
            $exProduct = new ProductExternal();
            $exProduct->setProductId($result['productId']);
            $exProduct->setName($result['productName']);
            $exProduct->setCreatedAt(time());

            $this->em->persist($exProduct);
        }

        $this->em->flush();
        $this->em->clear();
    }
}