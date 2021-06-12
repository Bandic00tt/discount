<?php
namespace App\Controller;

use App\Entity\Product;
use App\Service\DiscountHelper;
use App\Service\Shop\Five\DataHandler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    private EntityManagerInterface $em;
    private DiscountHelper $discountHelper;

    public function __construct(EntityManagerInterface $em, DiscountHelper $discountHelper)
    {
        $this->em = $em;
        $this->discountHelper = $discountHelper;
    }

    /**
     * @Route ("/", name="index")
     * @param Request $request
     * @return Response
     * @throws QueryException
     */
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $productsQuery = $this->discountHelper->getProducts($page);
        $paginator = new Paginator($productsQuery);
        $pages = ceil($this->discountHelper->getTotalProducts() / DiscountHelper::MAX_RESULTS);

        $products = $productsQuery->getResult();
        $discountHistory = $this->discountHelper->getDiscountHistory(
            $this->getLocationId(),
            $products
        );
        $year = date('Y');
        $yearDates = $this->discountHelper->dateHelper->getYearDates($year);
        $discountDates = $this->discountHelper->getDiscountDates($year, $discountHistory);
        $discountYears = $this->discountHelper->getDiscountYears($discountHistory);
        $activeProductDiscounts = $this->discountHelper->getActiveProductDiscounts($products);

        return $this->render('/product/index.html.twig', [
            'paginator' => $paginator,
            'pages' => $pages,
            'year' => $year,
            'yearDates' => $yearDates,
            'discountDates' => $discountDates,
            'discountYears' => $discountYears,
            'activeProductDiscounts' => $activeProductDiscounts,
        ]);
    }

    /**
     * @Route ("/remove-from-favorites/{productId}", name="remove_from_favorites")
     * @param Request $request
     * @return RedirectResponse
     */
    public function removeFromFavorites(Request $request): RedirectResponse
    {
        $productId = $request->get('productId');

        /** @var Product $product */
        $product = $this->em->getRepository(Product::class)
            ->findOneBy(['product_id' => $productId]);

        $isFavorited = 0;

        $product->setIsFavorited($isFavorited);
        $this->em->flush();
        $this->em->clear();

        return $this->redirectToRoute('index');
    }

    /**
     * todo: it seems too complex, needs refactoring
     * @Route ("/product/{id}", name="product", methods={"GET"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function product(Request $request): Response
    {
        $productId = $request->get('id');

        $product = $this->em
            ->getRepository(Product::class)
            ->findOneBy(['product_id' => $productId]);
        $activeProductDiscounts = $this->discountHelper->getActiveProductDiscounts([$product]);
        $discountHistory = $this->discountHelper->getDiscountHistory(
            $this->getLocationId(),
            [$product]
        );
        $productDiscountYears = $this->discountHelper->getDiscountYears($discountHistory)[$productId];
        $datesByYears = [];
        $productDiscountDatesByYears = [];
        foreach ($productDiscountYears as $year) {
            $productDiscountDatesByYears[$year] = $this->discountHelper->getDiscountDates($year, $discountHistory)[$productId];
            $datesByYears[$year] = $this->discountHelper->dateHelper->getYearDates($year);
        }

        return $this->render('/product/product.html.twig', [
            'product' => $product,
            'activeProductDiscounts' => $activeProductDiscounts,
            'productDiscountYears' => $productDiscountYears,
            'datesByYears' => $datesByYears,
            'productDiscountDatesByYears' => $productDiscountDatesByYears,
        ]);
    }

    /**
     * @Route ("/get-time-limited-discount-data", name="get_time_limited_discount_data")
     * @param Request $request
     * @return JsonResponse
     * @throws NonUniqueResultException
     */
    public function getTimeLimitedDiscountData(Request $request): JsonResponse
    {
        $productId = $request->get('productId');
        $discountDate = $request->get('discountDate');

        $discountHistory = $this->discountHelper->getTimeLimitedDiscountData($productId, $discountDate);

        $view = $this->renderView('/product/partials/productCard.html.twig', [
            'priceDiscount' => $discountHistory->getPriceDiscount(),
            'priceNormal' => $discountHistory->getPriceNormal(),
            'dateBegin' => date('d.m.Y', $discountHistory->getDateBegin()),
            'dateEnd' => date('d.m.Y', $discountHistory->getDateEnd()),
        ]);

        return $this->json(['html' => $view]);
    }

    /**
     * @Route ("/get-discount-data-by-year", name="get_discount_data_by_year")
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getDiscountDataByYear(Request $request): JsonResponse
    {
        $productId = $request->get('productId');
        $year = $request->get('year');

        $yearDates = $this->discountHelper->dateHelper->getYearDates($year);
        $product = $this->em
            ->getRepository(Product::class)
            ->findOneBy(['product_id' => $productId]);

        $discountHistory = $this->discountHelper->getDiscountHistory(
            $this->getLocationId(),
            [$product]
        );
        $productDiscountDates = $this->discountHelper->getDiscountDates($year, $discountHistory)[$productId];
        $productDiscountYears = $this->discountHelper->getDiscountYears($discountHistory)[$productId];

        $view = $this->renderView('/product/partials/history.html.twig', [
            'year' => $year,
            'yearDates' => $yearDates,
            'product' => $product,
            'productDiscountDates' => $productDiscountDates,
            'productDiscountYears' => $productDiscountYears,
        ]);

        return $this->json(['html' => $view]);
    }

    /**
     *
     * todo: transfer query
     * @Route ("/products", name="products")
     * @param Request $request
     * @return Response
     */
    public function products(Request $request): Response
    {
        $q = $request->get('q');

        $query = $this->em
            ->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p');

        if ($q) {
            $query->where('p.name like :q')
                ->setParameter('q', '%'. $q .'%');
        }

        $products = $query->getQuery()->getResult();

        return $this->render('/product/products.html.twig', [
            'products' => $products
        ]);
    }

    /**
     * @Route ("/toggle-product-favorited-status", name="toggleProductFavoritedStatus", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function toggleProductFavoritedStatus(Request $request): JsonResponse
    {
        $productId = $request->get('productId');

        /** @var Product $product */
        $product = $this->em->getRepository(Product::class)
            ->findOneBy(['product_id' => $productId]);

        $isFavorited = (int)$product->getIsFavorited() ^ 1;

        $product->setIsFavorited($isFavorited);
        $this->em->flush();
        $this->em->clear();

        return $this->json(['isFavorited' => $isFavorited]);
    }

    /**
     * @return int
     */
    private function getLocationId(): int
    {
        return (int) $_COOKIE['discountLocationId'] ?? DataHandler::MOSCOW_ID;
    }
}