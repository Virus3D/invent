<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Material;
use App\Form\MaterialType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/materials')]
final class MaterialApiController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Создать новый материал.
     */
    #[Route('/create', name: 'api_material_create', methods: ['POST'])]
    public function createMaterial(Request $request): JsonResponse
    {
        $material = new Material();

        return $this->save($request, $material);
    }

    /**
     * Обновить существующий материал.
     */
    #[Route('/update/{id}', name: 'api_material_update', methods: ['POST'])]
    public function updateMaterial(Request $request, Material $material): JsonResponse
    {
        return $this->save($request, $material);
    }

    /**
     * Установить/снять отметку проверки.
     */
    #[Route('/check/{id}', name: 'api_material_check_toggle', methods: ['POST'])]
    public function toggleCheck(Material $material, Request $request): JsonResponse
    {
        $checked = filter_var($request->request->get('checked', false), FILTER_VALIDATE_BOOLEAN);

        $material->setChecked($checked);
        $this->entityManager->flush();

        return $this->json(
            [
                'success' => true,
                'checked' => $material->isChecked(),
            ],
            Response::HTTP_OK
        );
    }

    /**
     * Сбросить отметку проверки у всех материалов.
     */
    #[Route('/check/reset', name: 'api_material_check_reset', methods: ['POST'])]
    public function resetCheck(): JsonResponse
    {
        $this->entityManager->getConnection()->executeStatement('UPDATE material SET checked = 0');

        return $this->json(['success' => true], Response::HTTP_OK);
    }

    /**
     * Save or update a material.
     */
    private function save(Request $request, Material $material): JsonResponse
    {
        $new = $material->getId() === null;
        $form = $this->createForm(MaterialType::class, $material);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Дополнительная валидация.
            $errors = $this->validator->validate($material);

            if (count($errors) > 0) {
                return $this->json(
                    [
                        'success' => false,
                        'message' => $this->translator->trans('material.create.validation_error'),
                        'errors'  => $this->formatValidationErrors($errors),
                    ],
                    422
                );
            }

            try {
                if ($new) {
                    $this->entityManager->persist($material);
                }

                $this->entityManager->flush();

                return $this->json(
                    [
                        'success'  => true,
                        'message'  => $this->translator->trans(
                            $new ? 'material.create.success' : 'material.update.success'
                        ),
                        'id'       => $material->getId(),
                        'name'     => $material->getName(),
                        'quantity' => $material->getQuantity(),
                    ],
                    $new ? 201 : 200
                );
            } catch (\Exception $e) {
                return $this->json(
                    [
                        'success' => false,
                        'message' => $this->translator->trans('material.create.error') . ': ' . $e->getMessage(),
                    ],
                    500
                );
            }
        }

        return $this->responseError($form);
    }

    /**
     * Форматирует ошибки валидации.
     *
     * @param ConstraintViolationList $errors
     *
     * @return array<string, array<string>>
     */
    private function formatValidationErrors(ConstraintViolationList $errors): array
    {
        $formatted = [];

        foreach ($errors as $error) {
            $field = $error->getPropertyPath();
            if (!isset($formatted[$field])) {
                $formatted[$field] = [];
            }
            $formatted[$field][] = $error->getMessage();
        }

        return $formatted;
    }

    /**
     * Форматирует ошибки формы.
     *
     * @param FormInterface<mixed> $form
     *
     * @return array<string, array<string>>
     */
    private function formatFormErrors(FormInterface $form): array
    {
        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            $field = $error->getOrigin()->getName();
            if (!isset($errors[$field])) {
                $errors[$field] = [];
            }
            $errors[$field][] = $error->getMessage();
        }

        return $errors;
    }

    /**
     * Возвращает ответ с ошибками формы.
     *
     * @param FormInterface<mixed> $form
     */
    private function responseError(FormInterface $form): JsonResponse
    {
        return $this->json(
            [
                'success' => false,
                'message' => $this->translator->trans('material.create.validation_error'),
                'errors'  => $this->formatFormErrors($form),
            ],
            422
        );
    }
}
