<?php
namespace App\Controller;

use App\Entity\Product;
use App\Service\DateHelper;
use App\Service\DiscountHelper;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SiteController extends AbstractController
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
     * @return Response
     * @throws QueryException
     */
    public function index(): Response
    {
        $currentYearDates = $this->discountHelper->dateHelper->getYearDatesRange(date('Y'));
        $favoritedProducts = $this->discountHelper->getFavoritedProducts();
        // todo: limit for current year
        $discountDates = $this->discountHelper->getDiscountDates($favoritedProducts);
        $productDiscounts = $this->discountHelper->getProductDiscounts($favoritedProducts);

        return $this->render('/site/index.html.twig', [
            'currentYearDates' => $currentYearDates,
            'favoritedProducts' => $favoritedProducts,
            'productDiscounts' => $productDiscounts,
            'discountDates' => $discountDates,
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

        return $this->json([
            'priceDiscount' => $discountHistory->getPriceDiscount(),
            'priceNormal' => $discountHistory->getPriceNormal(),
            'dateBegin' => date('d.m.Y', $discountHistory->getDateBegin()),
            'dateEnd' => date('d.m.Y', $discountHistory->getDateEnd()),
        ]);
    }

    /**
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

        return $this->render('/site/products.html.twig', [
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
}