<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\InventoryItem;
use App\Entity\Location;
use App\Entity\MovementLog;
use App\Enum\InventoryCategory;
use App\Form\InventoryItemType;
use App\Trait\SpecificationTrait;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use ValueError;

use function count;
use function in_array;
use function is_array;

use const FILTER_VALIDATE_BOOLEAN;
use const JSON_ERROR_NONE;

#[Route('/api/inventory')]
final class InventoryApiController extends AbstractController
{
    use SpecificationTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {
    }// end __construct()

    /**
     * Создать новый инвентарный объект.
     */
    #[Route('/create', name: 'api_inventory_create', methods: ['POST'])]
    public function createInventoryItem(
        Request $request,
    ): JsonResponse {
        $item = new InventoryItem();

        return $this->save($request, $item);
    }// end createInventoryItem()

    /**
     * Обновить существующий инвентарный объект.
     */
    #[Route('/update/{id}', name: 'api_inventory_update', methods: ['POST'])]
    public function updateInventoryItem(
        Request $request,
        InventoryItem $item,
    ): JsonResponse {
        $oldLocation       = $item->getLocation();
        $oldSpecifications = $item->getSpecifications();

        return $this->save($request, $item, $oldLocation, $oldSpecifications);
    }// end updateInventoryItem()

    /**
     * Установить/снять отметку проверки.
     */
    #[Route('/check/{id}', name: 'api_inventory_check_toggle', methods: ['POST'])]
    public function toggleCheck(InventoryItem $item, Request $request): JsonResponse
    {
        $checked = filter_var($request->request->get('checked', false), FILTER_VALIDATE_BOOLEAN);

        $item->setChecked($checked);
        $this->entityManager->flush();

        return $this->json(
            [
                'success' => true,
                'checked' => $item->isChecked(),
            ],
            Response::HTTP_OK
        );
    }// end toggleCheck()

    /**
     * Сбросить отметку проверки у всех объектов.
     */
    #[Route('/check/reset', name: 'api_inventory_check_reset', methods: ['POST'])]
    public function resetCheck(): JsonResponse
    {
        $this->entityManager->getConnection()->executeStatement('UPDATE inventory_item SET checked = 0');

        return $this->json(['success' => true], Response::HTTP_OK);
    }// end resetCheck()

    /**
     * Save or update an inventory item.
     *
     * @param array<string, mixed>|null $oldSpecifications
     */
    private function save(
        Request $request,
        InventoryItem $item,
        ?Location $oldLocation = null,
        ?array $oldSpecifications = null,
    ): JsonResponse {
        $new  = null == $oldLocation;
        $form = $this->createForm(
            InventoryItemType::class,
            $item,
            [
                'specs_url' => $this->generateUrl('api_category_specs', ['category' => '__CATEGORY__']),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Обработка спецификаций из запроса.
            $specifications = $this->processSpecificationsFromRequest($request, $item->getCategory());
            $item->setSpecifications($specifications);

            // Дополнительная валидация.
            $errors = $this->validator->validate($item);

            if (count($errors) > 0) {
                return $this->json(
                    [
                        'success' => false,
                        'message' => $this->translator->trans('inventory_item.create.validation_error'),
                        'errors'  => $this->formatValidationErrors($errors),
                    ],
                    422
                );
            }

            if (! $new) {
                $newLocation       = $item->getLocation();
                $newSpecifications = $item->getSpecifications();

                // Проверяем изменения спецификаций.
                $specificationsChanged = $oldSpecifications !== $newSpecifications;

                // Если изменилось местоположение или спецификации, создаем запись в логе.
                if ($oldLocation !== $newLocation || $specificationsChanged) {
                    $log = new MovementLog();
                    $log->setInventoryItem($item);
                    $log->setFromLocation($oldLocation);
                    $log->setToLocation($newLocation);
                    $log->setMovedBy($this->getUser() ? $this->getUser()->getUserIdentifier() : 'Система');

                    if ($oldLocation !== $newLocation) {
                        $log->setReason($this->translator->trans('movement_log.reason.location_change'));
                    } else {
                        $log->setReason($this->translator->trans('movement_log.reason.specifications_update'));
                    }

                    $this->entityManager->persist($log);
                }
            }// end if

            try {
                if ($new) {
                    $this->entityManager->persist($item);
                }

                $this->entityManager->flush();

                return $this->json(
                    [
                        'success'         => true,
                        'message'         => $this->translator->trans(
                            $new ? 'inventory_item.create.success' : 'inventory_item.update.success'
                        ),
                        'id'              => $item->getId(),
                        'name'            => $item->getName(),
                        'inventoryNumber' => $item->getInventoryNumber(),
                        'category'        => $item->getCategory()->value,
                        'specifications'  => $item->getSpecifications(),
                    ],
                    $new ? 201 : 200
                );
            } catch (Exception $e) {
                return $this->json(
                    [
                        'success' => false,
                        'message' => $this->translator->trans('inventory_item.create.error') . ': ' . $e->getMessage(),
                    ],
                    500
                );
            }// end try
        }// end if

        return $this->responseError($form);
    }// end save()

    /**
     * Обрабатывает спецификации из запроса.
     *
     * @return array<string, mixed>
     */
    private function processSpecificationsFromRequest(Request $request, InventoryCategory $category): array
    {
        $specifications = [];

        // Пробуем получить спецификации из JSON поля.
        $specsJson = $request->request->get('specifications');
        if ($specsJson) {
            $decoded = json_decode($specsJson, true);
            if (JSON_ERROR_NONE === json_last_error() && is_array($decoded)) {
                $specifications = $decoded;
            }
        }

        // Также пробуем получить спецификации из отдельных полей.
        $allParameters = $request->request->all();
        foreach ($allParameters as $key => $value) {
            if (str_starts_with($key, 'spec_')) {
                // Убираем 'spec_' префикс.
                $specKey = mb_substr($key, 5);
                if (! empty($value)) {
                    $specifications[$specKey] = $value;
                }
            }
        }

        // Если есть категория, фильтруем только разрешенные спецификации.
        if ($category instanceof InventoryCategory) {
            $allowedSpecs  = $category->getAllowedSpecifications();
            $filteredSpecs = [];

            foreach ($specifications as $key => $value) {
                if (in_array($key, $allowedSpecs, true) && ! empty(mb_trim($value))) {
                    $filteredSpecs[$key] = mb_trim($value);
                }
            }

            return $filteredSpecs;
        }

        return $specifications;
    }// end processSpecificationsFromRequest()

    /**
     * Форматирует ошибки валидации для JSON ответа.
     *
     * @return array<int, array{field: string, message: string, invalidValue: mixed}>
     */
    private function formatValidationErrors(ConstraintViolationList $errors): array
    {
        $formattedErrors = [];
        foreach ($errors as $error) {
            $formattedErrors[] = [
                'field'        => $error->getPropertyPath(),
                'message'      => $error->getMessage(),
                'invalidValue' => $error->getInvalidValue(),
            ];
        }

        return $formattedErrors;
    }// end formatValidationErrors()

    /**
     * Получить спецификации для категории.
     */
    #[Route('/category/{category}/specs', name: 'api_category_specs', methods: ['GET'])]
    public function getCategorySpecs(string $category): JsonResponse
    {
        try {
            $categoryEnum = InventoryCategory::from($category);

            $template = $this->renderView(
                'inventory/_specifications_form.html.twig',
                [
                    'category'       => $categoryEnum,
                    'specifications' => [],
                ]
            );

            return $this->json(
                [
                    'success'           => true,
                    'category'          => $categoryEnum->value,
                    'label'             => $this->translator->trans('inventory_item.category.' . $categoryEnum->value),
                    'hasSpecifications' => $categoryEnum->hasSpecifications(),
                    'requiredSpecs'     => $categoryEnum->getRequiredSpecifications(),
                    'allowedSpecs'      => $categoryEnum->getAllowedSpecifications(),
                    'specLabels'        => $this->getSpecificationLabels($categoryEnum),
                    'template'          => $template,
                ]
            );
        } catch (ValueError $e) {
            return $this->json(
                [
                    'success' => false,
                    'message' => $this->translator->trans('inventory_item.validation.category_not_found'),
                ],
                404
            );
        }// end try
    }// end getCategorySpecs()

    /**
     * Формирует JSON-ответ с ошибками формы.
     */
    private function responseError(FormInterface $form): JsonResponse
    {
        // Собираем ошибки валидации формы.
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $field          = $error->getOrigin() ? $error->getOrigin()->getName() : 'global';
            $errors[$field] = $error->getMessage();
        }

        return $this->json(
            [
                'success' => false,
                'message' => $this->translator->trans('inventory_item.update.validation_error'),
                'errors'  => $errors,
            ],
            422
        );
    }// end responseError()
}// end class
