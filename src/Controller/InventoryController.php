<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\InventoryItem;
use App\Entity\MovementLog;
use App\Enum\BalanceType;
use App\Enum\InventoryCategory;
use App\Form\InventoryItemType;
use App\Form\MovementLogType;
use App\Repository\InventoryItemRepository;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function in_array;

#[Route('/inventory')]
final class InventoryController extends AbstractController
{
    /**
     * Displays a list of inventory items.
     */
    #[Route('/', name: 'app_inventory_index', methods: ['GET'])]
    public function index(Request $request, InventoryItemRepository $repository): Response
    {
        $orderBy = [
            'location'        => 'ASC',
            'inventoryNumber' => 'ASC',
        ];
        $criteria = [];
        $category = $request->query->getString('category', '');
        if ($category) {
            $criteria['category'] = $category;
        }

        return $this->render(
            'inventory/index.html.twig',
            [
                'items' => $repository->findBy($criteria, $orderBy),
            ]
        );
    }// end index()

    /**
     * Creates a new inventory item.
     */
    #[Route('/new', name: 'app_inventory_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $item = new InventoryItem();
        $form = $this->createForm(InventoryItemType::class, $item);
        $form->handleRequest($request);

        return $this->render(
            'inventory/form.html.twig',
            [
                'page_title' => 'page_title.inventory_create',
                'item'       => $item,
                'form'       => $form->createView(),
            ]
        );
    }// end new()

    /**
     * Handles inventory search requests.
     */
    #[Route('/search', name: 'app_inventory_search', methods: ['GET'])]
    public function search(
        Request $request,
        InventoryItemRepository $inventoryRepository,
        LocationRepository $locationRepository,
    ): Response {
        // Build criteria array from request.
        $criteria = [
            'query'             => $request->query->get('q'),
            'category'          => $request->query->get('category'),
            'hasSerial'         => $request->query->getBoolean('has_serial'),
            'hasSpecifications' => $request->query->getBoolean('has_specs'),
            'balanceType'       => $request->query->get('balance_type'),
        ];

        // Location: either ID or special flag.
        $locationParam = $request->query->get('location');
        if ('without_location' === $locationParam) {
            $criteria['hasLocation'] = false;
        } else if ('with_location' === $locationParam) {
            $criteria['hasLocation'] = true;
        } else if (is_numeric($locationParam)) {
            $criteria['location'] = $locationParam;
        }

        // Status flag – mapping from UI to actual repository field.
        $statusParam = $request->query->get('status');
        if (in_array($statusParam, ['with_location', 'without_location'])) {
            // These are already handled by 'hasLocation' above, so ignore here.
        } else if ($statusParam) {
            // If you have a direct 'status' field on InventoryItem, pass it as exact match.
            $criteria['status'] = $statusParam;
        }

        // Sorting.
        $sort      = $request->query->get('sort', 'name');
        $direction = 'ASC';
        if ('createdAt_asc' === $sort) {
            $sort      = 'createdAt';
            $direction = 'ASC';
        } else if ('createdAt' === $sort) {
            $direction = 'DESC';
        }

        // Get results.
        $items = $inventoryRepository->searchByCriteria($criteria, $sort, $direction);

        // Fetch all locations for the dropdown (uncomment and pass).
        $locations = $locationRepository->findBy([], ['roomNumber' => 'ASC']);

        return $this->render(
            'inventory/search.html.twig',
            [
                'items'         => $items,
                'query'         => $criteria['query'],
                'locations'     => $locations,
                'balance_types' => BalanceType::cases(),
                'categories'    => InventoryCategory::cases(),
            ]
        );
    }// end search()

    /**
     * Displays the details of a specific inventory item.
     */
    #[Route('/{id}', name: 'app_inventory_show', methods: ['GET'])]
    public function show(InventoryItem $item): Response
    {
        return $this->render(
            'inventory/show.html.twig',
            ['item' => $item]
        );
    }// end show()

    /**
     * Handles editing an inventory item, including tracking location changes in the movement log.
     */
    #[Route('/{id}/edit', name: 'app_inventory_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        InventoryItem $item,
    ): Response {
        $form = $this->createForm(InventoryItemType::class, $item);
        $form->handleRequest($request);

        return $this->render(
            'inventory/form.html.twig',
            [
                'page_title' => 'page_title.inventory_edit',
                'item'       => $item,
                'form'       => $form->createView(),
            ]
        );
    }// end edit()

    /**
     * Handles the movement of an inventory item to a new location,
     * logging the movement event in the system.
     */
    #[Route('/{id}/move', name: 'app_inventory_move', methods: ['GET', 'POST'])]
    public function move(
        Request $request,
        InventoryItem $item,
        EntityManagerInterface $entityManager,
    ): Response {
        $log = new MovementLog();
        $log->setInventoryItem($item);
        $log->setFromLocation($item->getLocation());

        $form = $this->createForm(MovementLogType::class, $log);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Обновляем местоположение объекта.
            $item->setLocation($log->getToLocation());

            $entityManager->persist($log);
            $entityManager->flush();

            $this->addFlash('success', 'Перемещение зарегистрировано');

            return $this->redirectToRoute('app_inventory_show', ['id' => $item->getId()]);
        }

        return $this->render(
            'inventory/move.html.twig',
            [
                'item' => $item,
                'form' => $form->createView(),
            ]
        );
    }// end move()

    /**
     * Deletes an inventory item.
     */
    #[Route('/{id}', name: 'app_inventory_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        InventoryItem $item,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $item->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($item);
            $entityManager->flush();

            $this->addFlash('success', 'Объект удален');
        }

        return $this->redirectToRoute('app_inventory_index');
    }// end delete()
}// end class
