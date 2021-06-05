<?php
namespace App\Controller;

use App\Entity\Product;
use App\Service\DateHelper;
use App\Service\ProductHelper;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
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

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route ("/", name="index")
     * @param DateHelper $dateHelper
     * @param ProductHelper $productHelper
     * @return Response
     * @throws Exception
     */
    public function index(DateHelper $dateHelper, ProductHelper $productHelper): Response
    {
        $currentYearDates = $dateHelper->getYearDatesRange(date('Y'));
        $favoritedProducts = $productHelper->getFavoritedProducts();
        // todo: limit for current year
        $discountDates = $productHelper->getDiscountDates($favoritedProducts);

        return $this->render('/site/index.html.twig', [
            'currentYearDates' => $currentYearDates,
            'favoritedProducts' => $favoritedProducts,
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
     * @Route ("/products", name="products")
     * @return Response
     */
    public function products(): Response
    {
        $products = $this->getDoctrine()
            ->getRepository(Product::class)
            ->findAll();

        return $this->render('/site/products.html.twig', [
            'products' => $products
        ]);
    }

    /**
     * todo: rename
     * @Route ("/favorite-product", name="favoriteProduct")
     * @param Request $request
     * @return JsonResponse
     */
    public function favoriteProduct(Request $request): JsonResponse
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
     * todo: delete
     * @Route ("/activity", name="activity")
     * @return Response
     * @throws Exception
     */
    public function activity(): Response
    {
        $dateHelper = new DateHelper();
        $dates = $dateHelper->getYearDatesRange(date('Y'));
        $discountDates = $dateHelper->getDatesFromRange(
            new DateTime('2021-05-01'),
            new DateTime('2021-05-31')
        );

        return $this->render('/site/activity.html.twig', [
            'dates' => $dates,
            'discountDates' => $discountDates,
        ]);
    }


}