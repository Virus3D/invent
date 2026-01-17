<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Location;
use App\Entity\MovementLog;
use App\Form\LocationType;
use App\Repository\InventoryItemRepository;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/location')]
class LocationApiController extends AbstractController
{
    /**
     * Проверяет, существует ли местоположение с указанным номером комнаты или именем.
     */
    #[Route('/check/{roomNumber}/{name}', name: 'api_location_check', methods: ['GET'])]
    public function checkLocation(
        string $roomNumber,
        string $name,
        LocationRepository $repository,
        Request $request
    ): JsonResponse {
        $excludeId = $request->query->get('exclude');

        $query = $repository->createQueryBuilder('l')
            ->where('l.roomNumber = :roomNumber')
            ->orWhere('l.name = :name')
            ->setParameter('roomNumber', $roomNumber)
            ->setParameter('name', $name);

        if ($excludeId) {
            $query->andWhere('l.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        $existing = $query->getQuery()->getResult();

        if (count($existing) > 0) {
            $location = $existing[0];
            return $this->json(
                [
                    'exists'     => true,
                    'id'         => $location->getId(),
                    'roomNumber' => $location->getRoomNumber(),
                    'name'       => $location->getName(),
                    'message'    => 'Местоположение уже существует',
                ]
            );
        }

        return $this->json(
            [
                'exists'  => false,
                'message' => 'Местоположение свободно',
            ]
        );
    }// end checkLocation()

    /**
     * Создать новое местоположение.
     */
    #[Route('/create', name: 'api_location_create', methods: ['POST'])]
    public function createLocation(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $location = new Location();
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($location);
            $entityManager->flush();

            return $this->json(
                [
                    'success'    => true,
                    'message'    => 'Местоположение создано',
                    'id'         => $location->getId(),
                    'name'       => $location->getName(),
                    'roomNumber' => $location->getRoomNumber(),
                ]
            );
        }

        return $this->json(
            [
                'success' => false,
                'message' => 'Ошибка при создании: ' . $form->getErrors()->__toString() ?? 'Неизвестная ошибка',
            ]
        );
    }// end createLocation()

    /**
     * Обновить существующее местоположение.
     */
    #[Route('/update/{id}', name: 'api_location_update', methods: ['POST'])]
    public function updateLocation(
        Request $request,
        Location $location,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->json(
                [
                    'success' => true,
                    'message' => 'Изменения сохранены',
                    'id'      => $location->getId(),
                ]
            );
        }

        return $this->json(
            [
                'success' => false,
                'message' => 'Ошибка при обновлении: ' . $form->getErrors()->__toString() ?? 'Неизвестная ошибка',
            ]
        );
    }// end updateLocation()

    /**
     * Получить инвентарь, привязанный к местоположению.
     */
    #[Route('/{id}/objects', name: 'api_location_objects', methods: ['GET'])]
    public function getLocationObjects(Location $location): JsonResponse
    {
        $objects = [];

        foreach ($location->getInventoryItems() as $item) {
            $objects[] = [
                'id'              => $item->getId(),
                'inventoryNumber' => $item->getInventoryNumber(),
                'name'            => $item->getName(),
                'category'        => $item->getCategory()->getLabel(),
            ];
        }

        return $this->json(
            [
                'objects' => $objects,
                'total'   => count($objects),
            ]
        );
    }// end getLocationObjects()

    /**
     * Mass delete locations and appropriately handle inventory items.
     */
    #[Route('/mass-delete', name: 'api_locations_mass_delete', methods: ['POST'])]
    public function massDeleteLocations(
        Request $request,
        EntityManagerInterface $entityManager,
        InventoryItemRepository $itemRepository
    ): JsonResponse {
        $ids = json_decode($request->getContent(), true)['ids'] ?? [];
        $deletedCount = 0;

        foreach ($ids as $id) {
            $location = $entityManager->getRepository(Location::class)->find($id);

            if ($location) {
                // Перемещаем все объекты в статус "без местоположения".
                foreach ($location->getInventoryItems() as $item) {
                    $item->setLocation(null);
                    $entityManager->persist($item);

                    // Создаем запись в логе.
                    $log = new MovementLog();
                    $log->setInventoryItem($item);
                    $log->setFromLocation($location);
                    $log->setMovedBy('Система');
                    $log->setReason('Удаление местоположения');
                    $entityManager->persist($log);
                }

                $entityManager->remove($location);
                $deletedCount++;
            }
        }// end foreach

        try {
            $entityManager->flush();

            return $this->json(
                [
                    'success'      => true,
                    'message'      => 'Удалено местоположений: ' . $deletedCount,
                    'deletedCount' => $deletedCount,
                ]
            );
        } catch (\Exception $e) {
            return $this->json(
                [
                    'success' => false,
                    'message' => 'Ошибка при удалении: ' . $e->getMessage(),
                ]
            );
        }
    }// end massDeleteLocations()

    /**
     * Mass move objects from one or more locations to a new location (or to 'без местоположения').
     */
    #[Route('/mass-move', name: 'api_objects_mass_move', methods: ['POST'])]
    public function massMoveObjects(
        Request $request,
        EntityManagerInterface $entityManager,
        InventoryItemRepository $itemRepository,
        LocationRepository $locationRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $sourceLocationIds = $data['sourceLocationIds'] ?? [];
        $targetLocationId = $data['targetLocationId'] ?? null;
        $reason = $data['reason'] ?? 'Массовое перемещение';

        $targetLocation = null;
        if ($targetLocationId && $targetLocationId !== 'none') {
            $targetLocation = $locationRepository->find($targetLocationId);
        }

        $movedCount = 0;

        // Находим все объекты в указанных местоположениях.
        foreach ($sourceLocationIds as $locationId) {
            $location = $locationRepository->find($locationId);

            if ($location) {
                foreach ($location->getInventoryItems() as $item) {
                    $oldLocation = $item->getLocation();

                    if ($oldLocation !== $targetLocation) {
                        $item->setLocation($targetLocation);
                        $entityManager->persist($item);

                        // Создаем запись в логе.
                        $log = new MovementLog();
                        $log->setInventoryItem($item);
                        $log->setFromLocation($oldLocation);
                        $log->setToLocation($targetLocation);
                        $log->setMovedBy('Система');
                        $log->setReason($reason);
                        $entityManager->persist($log);

                        $movedCount++;
                    }
                }
            }// end if
        }// end foreach

        try {
            $entityManager->flush();

            return $this->json(
                [
                    'success'    => true,
                    'message'    => 'Перемещено объектов: ' . $movedCount,
                    'movedCount' => $movedCount,
                ]
            );
        } catch (\Exception $e) {
            return $this->json(
                [
                    'success' => false,
                    'message' => 'Ошибка при перемещении: ' . $e->getMessage(),
                ]
            );
        }
    }// end massMoveObjects()
}// end class
