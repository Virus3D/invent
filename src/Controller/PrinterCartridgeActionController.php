<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\CartridgeInstallation;
use App\Entity\InventoryItem;
use App\Repository\CartridgeRepository;
use App\Repository\InventoryItemRepository;
use App\Service\CartridgeManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/inventory/printer')]
class PrinterCartridgeActionController extends AbstractController
{
    public function __construct(
        private readonly CartridgeManager $cartridgeManager,
        private readonly CartridgeRepository $cartridgeRepo,
        private readonly InventoryItemRepository $printerRepo,
        private readonly TranslatorInterface $translator,
    ) {
    }// end __construct()

    #[Route('/install-quick', name: 'printer_cartridge_install_quick', methods: ['POST'])]
    public function installQuick(Request $request): Response
    {
        $cartridgeId = $request->request->get('cartridge_id');
        $printerId = $request->request->get('printer_id');
        $comment = trim($request->request->get('comment', ''));

        // Валидация входных данных.
        if (!$cartridgeId || !$printerId) {
            $this->addFlash(
                'danger',
                $this->translator->trans('cartridge.flash.invalid_data', domain: 'cartridge')
            );
            return $this->redirectToRoute('app_cartridge_index');
        }

        // Загрузка сущностей.
        $cartridge = $this->cartridgeRepo->find($cartridgeId);
        $printer = $this->printerRepo->find($printerId);

        if (!$cartridge || !$printer) {
            $this->addFlash(
                'danger',
                $this->translator->trans('cartridge.flash.entity_not_found', domain: 'cartridge')
            );
            return $this->redirectToRoute('app_cartridge_index');
        }

        // Проверка категории принтера.
        if ($printer->getCategory()?->value !== 'printer') {
            $this->addFlash(
                'danger',
                $this->translator->trans('cartridge.flash.not_a_printer', domain: 'cartridge')
            );
            return $this->redirectToRoute('app_cartridge_index');
        }

        try {
            $this->cartridgeManager->installCartridge($cartridge, $printer, $comment);

            $this->addFlash(
                'success',
                $this->translator->trans(
                    'cartridge.flash.cartridge_installed',
                    [
                        '%name%'    => $cartridge->getName(),
                        '%printer%' => $printer->getName(),
                    ],
                    domain: 'cartridge',
                )
            );

            return $this->redirectToRoute('app_inventory_show', ['id' => $printer->getId()]);
        } catch (\RuntimeException $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('app_cartridge_show', ['id' => $cartridge->getId()]);
        }
    }// end installQuick()

    #[Route('/inventory/printer/{id}/install', name: 'printer_cartridge_install', methods: ['GET', 'POST'])]
    public function install(Request $request, InventoryItem $printer): Response
    {
        // Проверка что это принтер.
        if ($printer->getCategory()?->value !== 'printer') {
            $this->addFlash('danger', $this->translator->trans('cartridge.flash.not_a_printer', domain: 'cartridge'));
            return $this->redirectToRoute('app_inventory_show', ['id' => $printer->getId()]);
        }

        if ($request->isMethod('POST')) {
            $cartridgeId = $request->request->get('cartridge_id');
            $comment = $request->request->get('comment', '');

            $cartridge = $this->cartridgeRepo->find($cartridgeId);

            if (!$cartridge) {
                $this->addFlash('danger', $this->translator->trans('cartridge.flash.entity_not_found', domain: 'cartridge'));
                return $this->redirectToRoute('app_inventory_show', ['id' => $printer->getId()]);
            }

            try {
                $this->cartridgeManager->installCartridge($cartridge, $printer, $comment);

                $this->addFlash(
                    'success',
                    $this->translator->trans(
                        'cartridge.flash.cartridge_installed',
                        [
                            '%name%'    => $cartridge->getName(),
                            '%printer%' => $printer->getName(),
                        ],
                        domain: 'cartridge',
                    )
                );
            } catch (\RuntimeException $e) {
                $this->addFlash('danger', $e->getMessage());
            }

            return $this->redirectToRoute('app_inventory_show', ['id' => $printer->getId()]);
        }// end if

        // GET запрос - показываем форму (если нужно отдельное страница).
        return $this->redirectToRoute('app_inventory_show', ['id' => $printer->getId()]);
    }// end install()

    #[Route('/installation/{id}/remove', name: 'printer_cartridge_remove', methods: ['POST'])]
    public function remove(Request $request, CartridgeInstallation $installation): Response
    {
        if (!$this->isCsrfTokenValid('remove' . $installation->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('flash.csrf_invalid', domain: 'messages'));
            return $this->redirectToRoute('app_inventory_show', ['id' => $installation->getPrinter()->getId()]);
        }

        $printedPages = $request->request->get('printed_pages');
        $this->cartridgeManager->removeCartridge(
            $installation,
            $printedPages ? (int) $printedPages : null
        );

        $this->addFlash('success', $this->translator->trans('cartridge.flash.cartridge_removed', domain: 'cartridge'));

        return $this->redirectToRoute('app_inventory_show', ['id' => $installation->getPrinter()->getId()]);
    }// end remove()
}// end class
