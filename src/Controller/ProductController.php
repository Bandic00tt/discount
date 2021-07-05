<?php
namespace App\Controller;

use App\Dto\Location;
use App\Repository\DiscountHistoryRepository;
use App\Repository\DiscountRepository;
use App\Repository\ProductRepository;
use App\Service\ProductItem;
use App\Service\ProductList;
use App\ValueObject\Cities;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ProductController
 * @package App\Controller
 */
class ProductController extends AbstractController
{
    /**
     * @throws Exception
     */
    public function __construct(
        private Location $location,
        private ProductRepository $productRepository,
        private DiscountHistoryRepository $discountHistoryRepository,
        private DiscountRepository $discountRepository
    )
    {
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
     * @throws NoResultException
     * @throws Exception
     */
    public function list(Request $request): Response
    {
        $cityEn = $request->get('cityEn');
        $page = (int) $request->get('page', 1);
        $searchQuery = $request->get('q');

        $productList = new ProductList($this->location->cityId, $page, $searchQuery);
        $productList->init($this->productRepository, $this->discountHistoryRepository);
        $productListViewParams = $productList->getProductListViewParams($this->discountRepository);
        $productPaginationViewParams = $productList->getProductPaginationViewParams();

        return $this->render('/product/list.html.twig', [
            'products' => $productList->getProducts(),
            'params' => ['cityEn' => $cityEn, 'page' => $page],
            'location' => $this->location,
            'year' => $productListViewParams->year,
            'yearDates' => $productListViewParams->yearDates,
            'discountDates' => $productListViewParams->discountDates,
            'discountYears' => $productListViewParams->discountYears,
            'activeProductDiscounts' => $productListViewParams->activeProductDiscounts,
            'currentPage' => $page,
            'firstPage' => $productPaginationViewParams->firstPage,
            'lastPage' => $productPaginationViewParams->lastPage,
            'totalPages' => $productPaginationViewParams->totalPages,
        ]);
    }

    /**
     * @Route ("/{cityEn}/product/{id}", name="app_product", methods={"GET"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function product(Request $request): Response
    {
        $productId = $request->get('id');

        $productItem = new ProductItem($this->location->cityId, $productId);
        $productItem->init($this->productRepository, $this->discountHistoryRepository);
        $productItemViewParams = $productItem->getProductItemViewParams($this->discountRepository);

        return $this->render('/product/product.html.twig', [
            'product' => $productItem->getProduct(),
            'activeDiscounts' => $productItemViewParams->activeDiscounts,
            'discountYears' => $productItemViewParams->discountYears,
            'datesByYears' => $productItemViewParams->datesByYears,
            'discountDatesByYears' => $productItemViewParams->discountDatesByYears,
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

        $discountHistory = $this->discountHistoryRepository
            ->findByLimit($this->location->cityId, $productId, $discountDate);

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

        $productItem = new ProductItem($this->location->cityId, $productId);
        $productItem->init($this->productRepository, $this->discountHistoryRepository);
        $productItemByYearParams = $productItem->getProductItemByYearParams($year);

        $view = $this->renderView('/product/_partials/history.html.twig', [
            'year' => $year,
            'product' => $productItem->getProduct(),
            'yearDates' => $productItemByYearParams->yearDates,
            'discountDates' => $productItemByYearParams->discountDates,
            'discountYears' => $productItemByYearParams->discountYears,
        ]);

        return $this->json(['html' => $view]);
    }

    /**
     * @return Location
     * @throws Exception
     */
    private function getLocation(): Location
    {
        $cities = Cities::list();
        $locationId = (int) ($_COOKIE['discountLocationId'] ?? Cities::MOSCOW_ID);

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