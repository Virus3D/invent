<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Material;
use App\Form\MaterialType;
use App\Repository\MaterialRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/materials')]
class MaterialController extends AbstractController
{
    /**
     * Displays a list of materials.
     */
    #[Route('/', name: 'app_material_index', methods: ['GET'])]
    public function index(Request $request, MaterialRepository $repository): Response
    {
        $orderBy = [
            'location' => 'ASC',
            'name'     => 'ASC',
        ];
        $criteria = [];
        $locationId = $request->query->getInt('location', 0);
        if ($locationId) {
            $criteria['location'] = $locationId;
        }

        return $this->render(
            'material/index.html.twig',
            [
                'materials' => $repository->findBy($criteria, $orderBy),
            ]
        );
    }

    /**
     * Creates a new material.
     */
    #[Route('/new', name: 'app_material_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $material = new Material();
        $form = $this->createForm(MaterialType::class, $material);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($material);
            $entityManager->flush();

            $this->addFlash('success', 'Материал успешно создан');
            return $this->redirectToRoute('app_material_index');
        }

        return $this->render(
            'material/form.html.twig',
            [
                'page_title' => 'page_title.material_create',
                'material'   => $material,
                'form'       => $form->createView(),
            ]
        );
    }

    /**
     * Handles material search requests.
     */
    #[Route('/search', name: 'app_material_search', methods: ['GET'])]
    public function search(Request $request, MaterialRepository $repository): Response
    {
        $query = $request->query->get('q');
        $materials = $query ? $repository->search($query) : [];

        return $this->render(
            'material/search.html.twig',
            [
                'materials' => $materials,
                'query'    => $query,
            ]
        );
    }

    /**
     * Displays the details of a specific material.
     */
    #[Route('/{id}', name: 'app_material_show', methods: ['GET'])]
    public function show(Material $material): Response
    {
        return $this->render(
            'material/show.html.twig',
            ['material' => $material]
        );
    }

    /**
     * Handles editing a material.
     */
    #[Route('/{id}/edit', name: 'app_material_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Material $material,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(MaterialType::class, $material);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Изменения успешно сохранены');
            return $this->redirectToRoute('app_material_show', ['id' => $material->getId()]);
        }

        return $this->render(
            'material/form.html.twig',
            [
                'page_title' => 'page_title.material_edit',
                'material'   => $material,
                'form'       => $form->createView(),
            ]
        );
    }

    /**
     * Deletes a material.
     */
    #[Route('/{id}', name: 'app_material_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Material $material,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $material->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($material);
            $entityManager->flush();

            $this->addFlash('success', 'Материал удален');
        }

        return $this->redirectToRoute('app_material_index');
    }
}
