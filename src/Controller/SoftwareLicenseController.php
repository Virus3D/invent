<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SoftwareLicense;
use App\Form\SoftwareLicenseType;
use App\Repository\SoftwareLicenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/licenses')]
final class SoftwareLicenseController extends AbstractController
{
    #[Route('/', name: 'app_license_index', methods: ['GET'])]
    public function index(Request $request, SoftwareLicenseRepository $repository): Response
    {
        $licenses = $repository->findBy([], ['name' => 'ASC']);

        return $this->render(
            'license/index.html.twig',
            [
                'licenses' => $licenses,
            ]
        );
    }

    #[Route('/new', name: 'app_license_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $license = new SoftwareLicense();
        $form    = $this->createForm(SoftwareLicenseType::class, $license);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($license);
            $entityManager->flush();

            $this->addFlash('success', 'Лицензия сохранена');

            return $this->redirectToRoute('app_license_index');
        }

        return $this->render(
            'license/form.html.twig',
            [
                'page_title' => 'page_title.license_create',
                'license'    => $license,
                'form'       => $form->createView(),
            ]
        );
    }

    #[Route('/{id}', name: 'app_license_show', methods: ['GET'])]
    public function show(SoftwareLicense $license): Response
    {
        return $this->render(
            'license/show.html.twig',
            [
                'license' => $license,
            ]
        );
    }

    #[Route('/{id}/edit', name: 'app_license_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        SoftwareLicense $license,
        EntityManagerInterface $entityManager,
    ): Response {
        $form = $this->createForm(SoftwareLicenseType::class, $license);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Лицензия обновлена');

            return $this->redirectToRoute('app_license_show', ['id' => $license->getId()]);
        }

        return $this->render(
            'license/form.html.twig',
            [
                'page_title' => 'page_title.license_edit',
                'license'    => $license,
                'form'       => $form->createView(),
            ]
        );
    }

    #[Route('/{id}', name: 'app_license_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        SoftwareLicense $license,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $license->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($license);
            $entityManager->flush();
            $this->addFlash('success', 'Лицензия удалена');
        }

        return $this->redirectToRoute('app_license_index');
    }
}

