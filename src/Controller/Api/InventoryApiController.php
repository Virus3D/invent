<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Enum\InventoryCategory;
use App\Service\Aida64Parser;
use App\Service\EncodingNormalizer;
use App\Trait\SpecificationTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use ValueError;

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
                    'label'             => $categoryEnum,
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
                    'message' => $this->translator->trans(
                        'inventory_item.validation.category_not_found',
                        domain: 'inventory'
                    ),
                ],
                404
            );
        }// end try
    }// end getCategorySpecs()

    /**
     * Aida64Parser.
     */
    #[Route('/parse-aida', name: 'api_inventory_parse_aida', methods: ['POST'])]
    public function parseAidaReport(
        Request $request,
        Aida64Parser $parser,
        EncodingNormalizer $encodingNormalizer,
    ): JsonResponse {
        $file = $request->files->get('aida_report');
        if (! $file) {
            return $this->json(['error' => 'Файл не загружен'], 400);
        }

        $rawContent = file_get_contents($file->getPathname());

        // Нормализуем кодировку.
        $normalizedContent = $encodingNormalizer->normalizeToUtf8($rawContent);

        // Если содержимое пустое после нормализации – ошибка.
        if (empty(mb_trim($normalizedContent))) {
            return $this->json(['error' => 'Файл пуст или содержит некорректные данные'], 400);
        }

        $systemInfo = $parser->parse($normalizedContent);

        return $this->json($systemInfo->toArray());
    }// end parseAidaReport()
}// end class
