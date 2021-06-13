<?php
namespace App\Controller;

use App\Service\Shop\Five\DataHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SiteController extends AbstractController
{
    /**
     * @Route ("/city", name="app_city", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     */
    public function city(Request $request): JsonResponse
    {
        $cityId = (int) $request->get('cityId');
        $cityName = DataHandler::CITIES[$cityId] ?? DataHandler::CITIES[DataHandler::MOSCOW_ID];

        return $this->json(['name' => $cityName]);
    }

    /**
     * @Route ("/cities", name="app_cities", methods={"GET"})
     * @return JsonResponse
     */
    public function cities(): JsonResponse
    {
        $html = $this->renderView('/site/cities.html.twig', [
            'cities' => DataHandler::CITIES
        ]);

        return $this->json(['html' => $html]);
    }

    /**
     * @Route ("/select-city/{id}", name="app_select_city", methods={"GET"})
     * @param Request $request
     * @return RedirectResponse
     */
    public function selectCity(Request $request): RedirectResponse
    {
        $cityId = $request->get('id');
        setcookie('discountLocationId', $cityId, time() + 604800 * 52, '/');

        return $this->redirectToRoute('app_index');
    }
}