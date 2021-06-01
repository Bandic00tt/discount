<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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


        return $this->render('/site/products.html.twig');
    }
}