<?php
namespace App\Controller;

use App\Entity\Product;
use App\Service\DateHelper;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SiteController extends AbstractController
{

    /**
     * @Route ("/", name="index")
     * @return Response
     */
    public function index(): Response
    {


        return $this->render('/site/index.html.twig');
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
     * @Route ("/favorite-product", name="favoriteProduct")
     * @param EntityManagerInterface $em
     * @param Request $request
     * @return JsonResponse
     */
    public function favoriteProduct(EntityManagerInterface $em, Request $request): JsonResponse
    {
        $productId = $request->get('productId');

        /** @var Product $product */
        $product = $em->getRepository(Product::class)
            ->findOneBy(['product_id' => $productId]);

        $isFavorited = (int)$product->getIsFavorited() ^ 1;

        $product->setIsFavorited($isFavorited);
        $em->flush();

        return $this->json(['isFavorited' => $isFavorited]);
    }

    /**
     * @Route ("/activity", name="activity")
     * @return Response
     * @throws Exception
     */
    public function activity(): Response
    {
        $dateHelper = new DateHelper();
        $dates = $dateHelper->getYearDatesRange(date('Y'));
        $discountDates = $dateHelper->getDatesRange(
            new DateTime('2021-05-01'),
            new DateTime('2021-05-31')
        );

        return $this->render('/site/activity.html.twig', [
            'dates' => $dates,
            'discountDates' => $discountDates,
        ]);
    }


}