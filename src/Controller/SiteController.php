<?php
namespace App\Controller;

use App\Entity\Feedback;
use App\Form\FeedbackType;
use App\Service\Shop\Five\DataHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @noinspection PhpUnused
     */
    public function selectCity(Request $request): RedirectResponse
    {
        $cityId = $request->get('id');
        setcookie('discountLocationId', $cityId, time() + 604800 * 52, '/');

        return $this->redirectToRoute('app_index');
    }

    /**
     * @Route ("/feedback", name="app_feedback", methods={"GET", "POST"})
     * @param Request $request
     * @return Response
     */
    public function feedback(Request $request): Response
    {
        $feedback = new Feedback();
        $form = $this->createForm(FeedbackType::class, $feedback);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Feedback $feedback */
            $feedback = $form->getData();
            $feedback->setCreatedAt(time());
            $feedback->setUpdatedAt(time());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($feedback);
            $entityManager->flush();
            $entityManager->clear();

            return $this->redirectToRoute('app_index');
        }

        return $this->render('/site/feedback.html.twig', [
            'form' => $form->createView()
        ]);
    }
}