<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\InventoryItem;
use App\Entity\MovementLog;
use App\Form\InventoryItemType;
use App\Form\MovementLogType;
use App\Repository\InventoryItemRepository;
use App\Repository\MovementLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/inventory')]
class InventoryController extends AbstractController
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
    public function search(Request $request, InventoryItemRepository $repository): Response
    {
        $query = $request->query->get('q');
        $items = $query ? $repository->search($query) : [];

        return $this->render(
            'inventory/search.html.twig',
            [
                'items' => $items,
                'query' => $query,
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
        EntityManagerInterface $entityManager
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
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $item->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($item);
            $entityManager->flush();

            $this->addFlash('success', 'Объект удален');
        }

        return $this->redirectToRoute('app_inventory_index');
    }// end delete()
}// end class
