<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Material;
use App\Form\WriteOffToInventoryItemType;
use App\Form\WriteOffToLocationType;
use App\Repository\MaterialConsumptionRepository;
use App\Service\MaterialWriteOffService;
use DateTimeImmutable;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/material/write-off')]
final class MaterialWriteOffController extends AbstractController
{
    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
    ) {
    }// end __construct()

    /**
     * Списание на место.
     */
    #[Route('/location/{id}', name: 'material_write_off_location_form', methods: ['GET', 'POST'])]
    public function writeOffLocationForm(Material $material, Request $request, MaterialWriteOffService $service): Response
    {
        $form = $this->createForm(WriteOffToLocationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $service->writeOffToLocation(
                    $material,
                    (int) $data['quantity'],
                    $data['location'],
                    $this->getUser(),
                    $data['comment'] ?? null
                );
                $this->addFlash('success', $this->translator->trans('flash.write_off_location_success', domain: 'material'));

                return $this->redirectToRoute('app_material_index');
            } catch (Exception $e) {
                $this->addFlash(
                    'danger',
                    $this->translator->trans('flash.write_off_error', ['%error%' => $e->getMessage()], 'material')
                );
            }
        }// end if

        return $this->render(
            'material/write_off_location.html.twig',
            [
                'material' => $material,
                'form'     => $form->createView(),
            ]
        );
    }// end writeOffLocationForm()

    /**
     * Списание на объект.
     */
    #[Route('/inventory/{id}', name: 'material_write_off_item_form', methods: ['GET', 'POST'])]
    public function writeOffItemForm(Material $material, Request $request, MaterialWriteOffService $service): Response
    {
        $form = $this->createForm(WriteOffToInventoryItemType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $inventoryItem = $data['inventoryItem'];

                $service->writeOffToInventoryItem(
                    $material,
                    (int) $data['quantity'],
                    $inventoryItem,
                    $this->getUser(),
                    $data['comment'] ?? null
                );
                $session = $this->requestStack->getSession();
                $session->set('last_used_material_id', $material->getId());

                $this->addFlash('success', $this->translator->trans('flash.write_off_item_success', domain: 'material'));

                return $this->redirectToRoute('app_inventory_edit', ['id' => $inventoryItem->getId()]);
            } catch (Exception $e) {
                $this->addFlash(
                    'danger',
                    $this->translator->trans('flash.write_off_error', ['%error%' => $e->getMessage()], 'material')
                );
            }// end try
        }// end if

        return $this->render(
            'material/write_off_item.html.twig',
            [
                'material' => $material,
                'form'     => $form->createView(),
            ]
        );
    }// end writeOffItemForm()

    /**
     * Отчет списания.
     */
    #[Route('/report', name: 'material_report', methods: ['GET'])]
    public function report(Request $request, MaterialConsumptionRepository $consumptionRepo): Response
    {
        $start = new DateTimeImmutable($request->query->get('start', 'first day of this month'));
        $end   = new DateTimeImmutable($request->query->get('end', 'last day of this month'));

        $consumptions = $consumptionRepo->findByPeriod($start, $end);
        $aggregated   = $consumptionRepo->getAggregatedByPeriod($start, $end);

        return $this->render(
            'material_consumption/report.html.twig',
            [
                'start'        => $start,
                'end'          => $end,
                'consumptions' => $consumptions,
                'aggregated'   => $aggregated,
            ]
        );
    }// end report()
}// end class
