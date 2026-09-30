<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Furniture;
use App\Entity\MovementLog;
use App\Form\FurnitureBatchCreateType;
use App\Form\FurnitureImportType;
use App\Form\FurnitureMovementLogType;
use App\Form\FurnitureType;
use App\Repository\FurnitureRepository;
use App\Repository\MovementLogRepository;
use App\Service\FurnitureImportService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters as EasyAdminFilters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD-контроллер для управления мебелью.
 */
final class FurnitureCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly FurnitureRepository $furnitureRepository,
        private readonly MovementLogRepository $movementLogRepository,
        private readonly TranslatorInterface $translator,
        private readonly FurnitureImportService $importService,
    ) {
    }// end __construct()

    /**
     * @inheritDoc
     */
    public static function getEntityFqcn(): string
    {
        return Furniture::class;
    }// end getEntityFqcn()

    /**
     * @inheritDoc
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->overrideTemplate('crud/detail', 'furniture/detail.html.twig')
            ->overrideTemplate('crud/edit', 'furniture/edit.html.twig')
            ->overrideTemplate('crud/new', 'furniture/new.html.twig')
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(50)
            ->setDefaultSort(['category' => 'ASC', 'name' => 'ASC']);
    }// end configureCrud()

    /**
     * @inheritDoc
     *
     * Расширенный detail: добавляет историю перемещений для отображения в шаблоне.
     */
    public function detail(AdminContext $context): KeyValueStore|Response
    {
        /**
         * Furniture.
         *
         * @var Furniture $furniture
         */
        $furniture = $context->getEntity()->getInstance();

        $movementLogs = $this->movementLogRepository->findByFurniture($furniture->getId());

        $response = parent::detail($context);

        if ($response instanceof KeyValueStore) {
            $response->set('movementLogs', $movementLogs);
        }

        return $response;
    }// end detail()

    /**
     * @inheritDoc
     */
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('inventoryNumber')
            ->setTemplatePath('fields/inventory_number.html.twig');

        yield TextField::new('name')
            ->setTemplatePath('fields/furniture_name.html.twig');

        yield ChoiceField::new('category')
            ->setChoices(\App\Enum\FurnitureCategory::cases())
            ->setTemplatePath('fields/furniture_category.html.twig');

        yield TextField::new('description')
            ->setRequired(false);

        yield ChoiceField::new('balanceType')
            ->setChoices(\App\Enum\BalanceType::cases())
            ->hideOnIndex();

        yield ChoiceField::new('status')
            ->setTemplatePath('fields/status.html.twig')
            ->setChoices(\App\Enum\ItemStatus::cases());

        yield AssociationField::new('location');

        yield TextField::new('responsiblePerson')
            ->setHelp('furniture.form.responsible_person_help')
            ->hideOnIndex();

        yield DateTimeField::new('createdAt')
            ->setTemplatePath('fields/time.html.twig')
            ->onlyOnDetail();

        yield DateTimeField::new('updatedAt')
            ->setTemplatePath('fields/time.html.twig')
            ->onlyOnDetail();

        yield BooleanField::new('checked');

        yield NumberField::new('purchasePrice')
            ->setNumDecimals(2)
            ->setRequired(false)
            ->hideOnIndex();
            // ->renderAsCurrency('RUB');
        yield DateTimeField::new('purchaseDate')
            ->hideOnIndex();
    }// end configureFields()

    /**
     * @inheritDoc
     */
    public function configureFilters(EasyAdminFilters $filters): EasyAdminFilters
    {
        return $filters
            ->add('category')
            ->add(EntityFilter::new('location'))
            ->add('status');
    }// end configureFilters()

    /**
     * @inheritDoc
     */
    public function configureActions(Actions $actions): Actions
    {
        $resetCheck = Action::new('checkResetAll', 'actions.check.reset_all')
            ->setIcon('bi bi-arrow-counterclockwise')
            ->linkToCrudAction('checkResetAll')
            ->createAsGlobalAction();

        $batchCreate = Action::new('batchCreate', 'actions.batch_create')
            ->setIcon('bi bi-plus-circle')
            ->linkToCrudAction('batchCreate')
            ->createAsGlobalAction();

        $importAction = Action::new('import', 'actions.import')
            ->setIcon('bi bi-file-earmark-arrow-up')
            ->linkToCrudAction('import')
            ->createAsGlobalAction();

        $downloadTemplate = Action::new('downloadTemplate', 'actions.download_template')
            ->setIcon('bi bi-download')
            ->linkToCrudAction('downloadTemplate')
            ->createAsGlobalAction();

        $moveAction = Action::new('move', 'actions.move')
            ->setIcon('fas fa-exchange-alt')
            ->linkToCrudAction('move');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $resetCheck)
            ->add(Crud::PAGE_INDEX, $batchCreate)
            ->add(Crud::PAGE_INDEX, $importAction)
            ->add(Crud::PAGE_INDEX, $downloadTemplate)
            ->add(Crud::PAGE_INDEX, $moveAction)
            ->add(Crud::PAGE_DETAIL, $moveAction);
    }// end configureActions()

    /**
     * Сбрасывает флаг "checked" у всей мебели.
     */
    #[AdminRoute]
    public function checkResetAll(): RedirectResponse
    {
        $this->entityManager->getConnection()->executeStatement('UPDATE furniture SET checked = 0');

        $this->addFlash('success', $this->translator->trans('flash.check_reset_success', domain: 'furniture'));

        return $this->redirect(
            $this->adminUrlGenerator->setController(self::class)->setAction('index')->generateUrl()
        );
    }// end checkResetAll()

    /**
     * Массовое создание одинаковых единиц мебели.
     */
    #[AdminRoute]
    public function batchCreate(AdminContext $context, Request $request): Response
    {
        $furniture = new Furniture();
        $form = $this->createForm(FurnitureBatchCreateType::class, $furniture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $quantity = (int) $form->get('quantity')->getData();

            $createdCount = 0;

            for ($i = 1; $i <= $quantity; $i++) {
                $item = clone $data;

                $this->entityManager->persist($item);
                $createdCount++;
            }

            $this->entityManager->flush();

            $this->addFlash(
                'success',
                $this->translator->trans(
                    'furniture.flash.batch_created',
                    ['%count%' => $createdCount],
                    'furniture'
                )
            );

            return $this->redirect(
                $this->adminUrlGenerator->setController(self::class)->setAction('index')->generateUrl()
            );
        }// end if

        return $this->render(
            'furniture/batch_create.html.twig',
            [
                'form' => $form->createView(),
                'item' => new Furniture(),
            ]
        );
    }// end batchCreate()

    /**
     * Импорт мебели из XLS/XLSX файла.
     */
    #[AdminRoute]
    public function import(Request $request): Response
    {
        $form = $this->createForm(FurnitureImportType::class);
        $form->handleRequest($request);

        $createdCount = 0;
        $skippedCount = 0;
        $errors       = [];

        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * Файл.
             *
             * @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $file
             */
            $file     = $form->get('file')->getData();
            $location = $form->get('location')->getData();

            if ($file) {
                $result = $this->importService->importFromExcel($file, $location);

                $createdCount = $result->created;
                $skippedCount = $result->skipped;
                $errors       = $result->errors;
            }
        }

        return $this->render(
            'furniture/import.html.twig',
            [
                'form'         => $form->createView(),
                'item'         => new Furniture(),
                'createdCount' => $createdCount,
                'skippedCount' => $skippedCount,
                'errors'       => $errors,
            ]
        );
    }// end import()

    /**
     * Скачивание шаблона для импорта.
     */
    #[AdminRoute]
    public function downloadTemplate(): Response
    {
        return $this->importService->generateTemplate();
    }// end downloadTemplate()

    /**
     * Кастомная страница перемещения мебели (GET – форма, POST – обработка).
     */
    #[AdminRoute]
    public function move(AdminContext $context, Request $request): Response
    {
        $furniture = $context->getEntity()->getInstance();
        if (!$furniture instanceof Furniture) {
            throw $this->createNotFoundException();
        }

        $log = new MovementLog();
        $log->setFurniture($furniture);
        $log->setFromLocation($furniture->getLocation());

        $form = $this->createForm(FurnitureMovementLogType::class, $log);
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            // Обновляем местоположение мебели.
            $furniture->setLocation($log->getToLocation());

            $this->entityManager->persist($log);
            $this->entityManager->flush();
            $this->addFlash('success', 'Перемещение зарегистрировано.');

            $redirectUrl = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction('detail')
                ->setEntityId($furniture->getId())
                ->generateUrl();

            // Для AJAX-запроса возвращаем JSON с URL редиректа.
            if ($request->isXmlHttpRequest()) {
                return new \Symfony\Component\HttpFoundation\JsonResponse(
                    [
                        'success'     => true,
                        'redirectUrl' => $redirectUrl,
                    ]
                );
            }

            return $this->redirect($redirectUrl);
        }// end if

        return $this->render(
            'furniture/move.html.twig',
            [
                'form' => $form->createView(),
                'item' => $furniture,
            ]
        );
    }// end move()
}// end class
