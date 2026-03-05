<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Cartridge;
use App\Form\CartridgeType;
use App\Repository\CartridgeInstallationRepository;
use App\Repository\CartridgeRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function sprintf;

#[Route('/cartridges')]
final class CartridgeController extends AbstractController
{
    /**
     * Displays a list of cartridges.
     */
    #[Route('/', name: 'app_cartridge_index', methods: ['GET'])]
    public function index(CartridgeRepository $repo): Response
    {
        return $this->render(
            'cartridge/index.html.twig',
            [
                'cartridges' => $repo->findAll(),
            ]
        );
    }// end index()

    /**
     * Creates a new cartridge.
     */
    #[Route('/new', name: 'app_cartridge_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $cartridge = new Cartridge();
        $form      = $this->createForm(CartridgeType::class, $cartridge);
        $form->handleRequest($request);

        return $this->render(
            'cartridge/form.html.twig',
            [
                'page_title' => 'page.create',
                'cartridge'  => $cartridge,
                'form'       => $form->createView(),
            ]
        );
    }// end new()

    /**
     * Displays the details of a specific cartridge.
     */
    #[Route('/{id}', name: 'app_cartridge_show', methods: ['GET'])]
    public function show(
        Cartridge $cartridge,
        Request $request,
        CartridgeInstallationRepository $cartridgeInstallation,
    ): Response {
        $from = new DateTimeImmutable(
            $request->query->get('from', 'first day of this year')
        );
        $to = new DateTimeImmutable(
            $request->query->get('to', 'today')
        );

        $stats = $cartridgeInstallation->getUsageStats($cartridge, $from, $to);

        return $this->render(
            'cartridge/show.html.twig',
            [
                'cartridge' => $cartridge,
                'stats'     => $stats,
                'date_from' => $from,
                'date_to'   => $to,
            ]
        );
    }// end show()

    /**
     * Handles editing a cartridge.
     */
    #[Route('/{id}/edit', name: 'app_cartridge_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Cartridge $cartridge,
    ): Response {
        $form = $this->createForm(CartridgeType::class, $cartridge);
        $form->handleRequest($request);

        return $this->render(
            'cartridge/form.html.twig',
            [
                'page_title' => 'page.edit',
                'cartridge'  => $cartridge,
                'form'       => $form->createView(),
            ]
        );
    }// end edit()

    #[Route('/{id}/add-stock', name: 'app_cartridge_add_stock', methods: ['POST'])]
    public function addStock(Request $request, Cartridge $cartridge, EntityManagerInterface $em): Response
    {
        $quantity = max(1, (int) $request->request->get('quantity', 1));
        $cartridge->increaseStock($quantity);
        $em->flush();

        $this->addFlash('success', sprintf('Добавлено %d картридж(ей) "%s" на склад', $quantity, $cartridge->getName()));

        return $this->redirectToRoute('app_cartridge_show', ['id' => $cartridge->getId()]);
    }// end addStock()

    /**
     * Deletes a cartridge.
     */
    #[Route('/{id}', name: 'app_cartridge_delete', methods: ['POST'])]
    public function delete(Request $request, Cartridge $cartridge, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $cartridge->getId(), $request->request->get('_token'))) {
            // Мягкое удаление: не удаляем, если есть активные установки.
            if ($cartridge->getInstallations()->exists(static fn ($k, $i) => $i->isInstalled())) {
                $this->addFlash('error', 'Нельзя удалить картридж, который сейчас установлен на принтере');

                return $this->redirectToRoute('app_cartridge_show', ['id' => $cartridge->getId()]);
            }

            $em->remove($cartridge);
            $em->flush();
            $this->addFlash('success', 'Картридж удалён');
        }

        return $this->redirectToRoute('app_cartridge_index');
    }// end delete()
}// end class
