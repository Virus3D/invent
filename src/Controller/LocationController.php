<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Location;
use App\Form\LocationType;
use App\Repository\InventoryItemRepository;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use function count;
use function sprintf;

#[Route('/location')]
final class LocationController extends AbstractController
{
    /**
     * Display a listing of Location entities.
     */
    #[Route('/', name: 'app_location_index', methods: ['GET'])]
    public function index(
        Request $request,
        LocationRepository $repository,
    ): Response {
        $query = $request->query->get('q');

        if ($query) {
            $locations = $repository->search($query);
        } else {
            $locations = $repository->findBy([], ['roomNumber' => 'ASC']);
        }

        // Статистика.
        $occupiedLocations     = array_filter(
            $locations,
            static fn ($loc) => count($loc->getInventoryItems()) > 0
        );
        $totalObjects          = array_sum(
            array_map(
                static fn ($loc) => count($loc->getInventoryItems()),
                $locations
            )
        );
        $avgObjectsPerLocation = count($locations) > 0 ? $totalObjects / count($locations) : 0;
        $maxObjects            = $locations ? max(
            array_map(
                static fn ($loc) => count($loc->getInventoryItems()),
                $locations
            )
        ) : 0;

        // Сортировка по количеству объектов для топ-списка.
        usort(
            $occupiedLocations,
            static fn ($a, $b) => count($b->getInventoryItems()) <=> count($a->getInventoryItems())
        );

        return $this->render(
            'location/index.html.twig',
            [
                'locations'             => $locations,
                'occupiedLocations'     => $occupiedLocations,
                'totalObjects'          => $totalObjects,
                'avgObjectsPerLocation' => $avgObjectsPerLocation,
                'maxObjects'            => $maxObjects,
                'totalLocations'        => count($locations),
            ]
        );
    }// end index()

    /**
     * Create a new Location entity.
     */
    #[Route('/new', name: 'app_location_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $location = new Location();
        $form     = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        return $this->render(
            'location/form.html.twig',
            [
                'page_title' => 'page_title.location_new',
                'location'   => $location,
                'form'       => $form->createView(),
            ]
        );
    }// end new()

    /**
     * Display a Location entity.
     */
    #[Route('/{id}', name: 'app_location_show', methods: ['GET'])]
    public function show(Location $location): Response
    {
        return $this->render(
            'location/show.html.twig',
            ['location' => $location]
        );
    }// end show()

    /**
     * Edit an existing Location entity.
     */
    #[Route('/{id}/edit', name: 'app_location_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Location $location,
        EntityManagerInterface $entityManager,
    ): Response {
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        return $this->render(
            'location/form.html.twig',
            [
                'page_title' => 'page_title.location_edit',
                'location'   => $location,
                'form'       => $form->createView(),
            ]
        );
    }// end edit()

    /**
     * Deletes a Location entity.
     */
    #[Route('/{id}', name: 'app_location_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Location $location,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $location->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($location);
            $entityManager->flush();

            $this->addFlash('success', 'Местоположение удалено');
        }

        return $this->redirectToRoute('app_location_index');
    }// end delete()

    /**
     * Mass delete locations via API.
     */
    #[Route('/mass-delete', name: 'api_locations_mass_delete', methods: ['POST'])]
    public function massDelete(
        Request $request,
        LocationRepository $locationRepository,
        InventoryItemRepository $inventoryItemRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        // Проверка CSRF токена.
        $data  = json_decode($request->getContent(), true);
        $token = new CsrfToken('location_index', $data['_token'] ?? '');

        if (! $csrfTokenManager->isTokenValid($token)) {
            return $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }

        $ids          = $data['ids'] ?? [];
        $deletedCount = 0;

        foreach ($ids as $id) {
            $location = $locationRepository->find($id);
            if ($location) {
                // Перемещаем все объекты в категорию "без локации".
                foreach ($location->getInventoryItems() as $item) {
                    $item->setLocation(null);
                    $inventoryItemRepository->save($item, true);
                }

                $locationRepository->remove($location, true);
                ++$deletedCount;
            }
        }

        return $this->json(
            [
                'success'      => true,
                'message'      => "Удалено {$deletedCount} локаций",
                'deletedCount' => $deletedCount,
            ]
        );
    }// end massDelete()

    /**
     * Получить список всех доступных локаций.
     */
    #[Route('/available', name: 'api_locations_available', methods: ['GET'])]
    public function availableLocations(LocationRepository $locationRepository): JsonResponse
    {
        $locations = $locationRepository->findBy([], ['name' => 'ASC']);

        $data = array_map(
            static fn ($location) => [
                'id'          => $location->getId(),
                'name'        => $location->getName(),
                'roomNumber'  => $location->getRoomNumber(),
                'objectCount' => $location->getInventoryItems()->count(),
            ],
            $locations
        );

        return $this->json(
            [
                'success'   => true,
                'locations' => $data,
            ]
        );
    }// end availableLocations()

    /**
     * Экспорт локаций в формате CSV.
     */
    #[Route('/export', name: 'api_locations_export', methods: ['POST'])]
    public function export(
        Request $request,
        LocationRepository $locationRepository,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $ids  = $data['ids'] ?? [];

        if ('all' === $ids || empty($ids)) {
            $locations = $locationRepository->findAll();
        } else {
            $locations = $locationRepository->findBy(['id' => $ids]);
        }

        // Формируем CSV.
        $csv = "ID;Название;Номер кабинета;Описание;Количество объектов\n";

        foreach ($locations as $location) {
            $csv .= sprintf(
                "%d;%s;%s;%s;%d\n",
                $location->getId(),
                $location->getName(),
                $location->getRoomNumber() ?? '',
                str_replace(';', ',', $location->getDescription() ?? ''),
                $location->getInventoryItems()->count()
            );
        }

        return $this->json(
            [
                'success' => true,
                'csv'     => $csv,
            ]
        );
    }// end export()
}// end class
