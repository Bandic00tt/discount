<?php
namespace App\Controller;

use App\Entity\Feedback;
use App\Form\FeedbackType;
use App\Service\Shop\Five\DataHandler;
use App\ValueObject\Cities;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SiteController extends AbstractController
{
    /**
     * @Route ("/cities", name="app_cities", methods={"GET"}, priority="1")
     * @param Request $request
     * @return JsonResponse
     */
    public function cities(Request $request): JsonResponse
    {
        $query = $request->get('query');
        $html = $this->renderView('/site/cities.html.twig', [
            'cities' => $this->getCities($query),
        ]);

        return $this->json(['html' => $html]);
    }

    /**
     * @param string|null $query
     * @return array
     */
    private function getCities(?string $query): array
    {
        $list = Cities::list();
        if ($query) {
            $res = [];
            foreach ($list as $id => $cityItem) {
                if (strpos(mb_strtolower($cityItem['ru'], 'utf-8'), $query) !== false) {
                    $res[$id] = $cityItem;
                }
            }

            return $res;
        }

        return $list;
    }

    /**
     * @Route ("/select-city/{id}", name="app_select_city", methods={"GET"}, priority="1")
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
     * @Route ("/feedback", name="app_feedback", methods={"GET", "POST"}, priority="1")
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