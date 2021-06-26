<?php
namespace App\Controller;

use App\Dto\Location;
use App\Entity\Product;
use App\Service\DiscountHelper;
use App\Service\Shop\Five\DataHandler;
use App\ValueObject\Cities;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\QueryException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    private const PAGINATION_SIZE = 20;

    private EntityManagerInterface $em;
    private DiscountHelper $discountHelper;
    private Location $location;

    public function __construct(EntityManagerInterface $em, DiscountHelper $discountHelper)
    {
        $this->em = $em;
        $this->discountHelper = $discountHelper;
        $this->location = $this->getLocation();
    }

    /**
     * @Route ("/", name="app_index")
     * @return RedirectResponse
     */
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('app_list', [
            'cityEn' => $this->location->cityNameEn
        ]);
    }

    /**
     * @Route (
     *     "/{cityEn}/{page}",
     *     defaults={"page"=1},
     *     requirements={"cityEn"="\w+", "page"="\d+"},
     *     name="app_list"
     * )
     * @param Request $request
     * @return Response
     * @throws NonUniqueResultException
     * @throws QueryException
     * @throws NoResultException
     */
    public function list(Request $request): Response
    {
        $cityEn = $request->get('cityEn');
        $page = (int) $request->get('page', 1);
        $searchQuery = $request->get('q');
        // Пагинация
        $totalProducts = $this->discountHelper->getTotalProducts($this->location->cityId, $searchQuery);
        $totalPages = ceil($totalProducts / DiscountHelper::MAX_RESULTS);
        $firstPage = 1;
        $lastPage = min($totalPages, self::PAGINATION_SIZE);

        if ($totalPages > $lastPage && $page > self::PAGINATION_SIZE / 2) {
            if (($page + self::PAGINATION_SIZE / 2) < $totalPages) {
                $lastPage = $page + self::PAGINATION_SIZE / 2;
            } else {
                $lastPage = $totalPages;
            }

            $firstPage = $lastPage - self::PAGINATION_SIZE;
        }

        // todo: переместить выборки в специальные классы
        $products =  $this->discountHelper->getProducts($this->location->cityId, $page, $searchQuery);
        $discountHistory = $this->discountHelper->getDiscountHistory($this->location->cityId, $products);
        $year = date('Y');
        $yearDates = $this->discountHelper->dateHelper->getYearDates($year);
        $discountDates = $this->discountHelper->getDiscountDates($year, $discountHistory);
        $discountYears = $this->discountHelper->getDiscountYears($discountHistory);
        $activeProductDiscounts = $this->discountHelper->getActiveProductDiscounts($products);

        return $this->render('/product/list.html.twig', [
            'products' => $products,
            'params' => ['cityEn' => $cityEn, 'page' => $page],
            'location' => $this->location,
            'currentPage' => $page,
            'firstPage' => $firstPage,
            'lastPage' => $lastPage,
            'totalPages' => $totalPages,
            'year' => $year,
            'yearDates' => $yearDates,
            'discountDates' => $discountDates,
            'discountYears' => $discountYears,
            'activeProductDiscounts' => $activeProductDiscounts,
        ]);
    }

    /**
     * todo: it seems too complex, needs refactoring
     * @Route ("/{cityEn}/product/{id}", name="app_product", methods={"GET"})
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
        $discountHistory = $this->discountHelper->getDiscountHistory($this->location->cityId, [$product]);
        $productDiscountYears = $this->discountHelper->getDiscountYears($discountHistory)[$productId] ?? [];
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
     * @Route ("/time-limited-discount-data", name="app_time_limited_discount_data", priority="1")
     * @param Request $request
     * @return JsonResponse
     * @throws NonUniqueResultException
     */
    public function timeLimitedDiscountData(Request $request): JsonResponse
    {
        $productId = $request->get('productId');
        $discountDate = $request->get('discountDate');

        $discountHistory = $this->discountHelper->getTimeLimitedDiscountData($productId, $discountDate);

        $view = $this->renderView('/product/_partials/productCard.html.twig', [
            'priceDiscount' => $discountHistory->getPriceDiscount(),
            'priceNormal' => $discountHistory->getPriceNormal(),
            'dateBegin' => date('d.m.Y', $discountHistory->getDateBegin()),
            'dateEnd' => date('d.m.Y', $discountHistory->getDateEnd()),
        ]);

        return $this->json(['html' => $view]);
    }

    /**
     * @Route ("/discount-data-by-year", name="app_discount_data_by_year", priority="1")
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function discountDataByYear(Request $request): JsonResponse
    {
        $productId = $request->get('productId');
        $year = $request->get('year');

        $yearDates = $this->discountHelper->dateHelper->getYearDates($year);
        $product = $this->em
            ->getRepository(Product::class)
            ->findOneBy(['product_id' => $productId]);

        $discountHistory = $this->discountHelper->getDiscountHistory($this->location->cityId, [$product]);
        $productDiscountDates = $this->discountHelper->getDiscountDates($year, $discountHistory)[$productId];
        $productDiscountYears = $this->discountHelper->getDiscountYears($discountHistory)[$productId];

        $view = $this->renderView('/product/_partials/history.html.twig', [
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
     * @Route ("/products", name="app_products", priority="1")
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
     * @return Location
     * @throws Exception
     */
    private function getLocation(): Location
    {
        $cities = Cities::list();
        $locationId = (int) ($_COOKIE['discountLocationId'] ?? DataHandler::MOSCOW_ID);

        $cityItem = $cities[$locationId] ?? null;

        if ($cityItem) {
            $location = new Location();
            $location->cityId = $locationId;
            $location->cityNameRu = $cityItem['ru'];
            $location->cityNameEn = $cityItem['en'];

            return $location;
        }

        throw new Exception('City item not found');
    }
}