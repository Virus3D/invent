<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Cartridge;
use App\Form\CartridgeType;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function count;

#[Route('/api/cartridges')]
final class CartridgeApiController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {}// end __construct()

    /**
     * Создать новый материал.
     */
    #[Route('/create', name: 'api_cartridge_create', methods: ['POST'])]
    public function createLicense(Request $request): JsonResponse
    {
        $cartridge = new Cartridge();

        return $this->save($request, $cartridge);
    }// end createLicense()

    /**
     * Обновить существующий материал.
     */
    #[Route('/update/{id}', name: 'api_cartridge_update', methods: ['POST'])]
    public function updateLicense(Request $request, Cartridge $cartridge): JsonResponse
    {
        return $this->save($request, $cartridge);
    }// end updateLicense()

    /**
     * Save or update a cartridge.
     */
    private function save(Request $request, Cartridge $cartridge): JsonResponse
    {
        $new  = null === $cartridge->getId();
        $form = $this->createForm(CartridgeType::class, $cartridge);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Дополнительная валидация.
            $errors = $this->validator->validate($cartridge);

            if (count($errors) > 0) {
                return $this->json(
                    [
                        'success' => false,
                        'message' => $this->translator->trans('validation.error'),
                        'errors'  => $this->formatValidationErrors($errors),
                    ],
                    422
                );
            }

            try {
                if ($new) {
                    $this->entityManager->persist($cartridge);
                }

                $this->entityManager->flush();

                return $this->json(
                    [
                        'success' => true,
                        'message' => $this->translator->trans(
                            $new ? 'cartridge.create.success' : 'cartridge.update.success',
                            domain: 'cartridge'
                        ),
                        'id'      => $cartridge->getId(),
                        'name'    => $cartridge->getName(),
                    ],
                    $new ? 201 : 200
                );
            } catch (Exception $e) {
                return $this->json(
                    [
                        'success' => false,
                        'message' => $this->translator->trans('cartridge.create.error') . ': ' . $e->getMessage(),
                    ],
                    500
                );
            }// end try
        }// end if

        return $this->responseError($form);
    }// end save()

    /**
     * Форматирует ошибки валидации.
     *
     * @return array<string, array<string>>
     */
    private function formatValidationErrors(ConstraintViolationList $errors): array
    {
        $formatted = [];

        foreach ($errors as $error) {
            $field = $error->getPropertyPath();
            if (! isset($formatted[$field])) {
                $formatted[$field] = [];
            }
            $formatted[$field][] = $error->getMessage();
        }

        return $formatted;
    }// end formatValidationErrors()

    /**
     * Форматирует ошибки формы.
     *
     * @return array<string, array<string>>
     */
    private function formatFormErrors(FormInterface $form): array
    {
        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            $field = $error->getOrigin()->getName();
            if (! isset($errors[$field])) {
                $errors[$field] = [];
            }
            $errors[$field][] = $error->getMessage();
        }

        return $errors;
    }// end formatFormErrors()

    /**
     * Возвращает ответ с ошибками формы.
     */
    private function responseError(FormInterface $form): JsonResponse
    {
        return $this->json(
            [
                'success' => false,
                'message' => $this->translator->trans('validation.error'),
                'errors'  => $this->formatFormErrors($form),
            ],
            422
        );
    }// end responseError()
}// end class
