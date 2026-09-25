<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Furniture;
use App\Form\FurnitureBatchCreateType;
use App\Form\FurnitureType;
use App\Repository\FurnitureRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
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
        private readonly TranslatorInterface $translator,
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
            ->setChoices(\App\Enum\ItemStatus::cases());

        yield AssociationField::new('location');

        yield TextField::new('responsiblePerson')
            ->setHelp('furniture.form.responsible_person_help')
            ->hideOnIndex();

        yield DateTimeField::new('createdAt')
            ->setTemplatePath('fields/furniture_created.html.twig')
            ->onlyOnDetail();

        yield DateTimeField::new('updatedAt')
            ->setFormat('d.m.Y')
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
    public function configureFilters(Filters $filters): Filters
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

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $resetCheck)
            ->add(Crud::PAGE_INDEX, $batchCreate);
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
                $item = new Furniture();
                $item->setName($data->getName());
                $item->setDescription($data->getDescription());
                $item->setCategory($data->getCategory());
                $item->setStatus($data->getStatus());
                $item->setPurchasePrice($data->getPurchasePrice());
                $item->setPurchaseDate($data->getPurchaseDate());
                $item->setResponsiblePerson($data->getResponsiblePerson());
                $item->setLocation($data->getLocation());
                $item->setInventoryNumber($data->getInventoryNumber());
                $item->setBalanceType($data->getBalanceType());

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
     * @inheritDoc
     */
    public function createEditFormBuilder(
        \EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto $entityDto,
        \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore $formOptions,
        AdminContext $context,
    ): FormBuilderInterface {
        $entity = $entityDto->getInstance();

        return $this->createFormBuilderForEntity($entity);
    }// end createEditFormBuilder()

    /**
     * @inheritDoc
     */
    public function createNewFormBuilder(
        \EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto $entityDto,
        \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore $formOptions,
        AdminContext $context,
    ): FormBuilderInterface {
        $entity = $entityDto->getInstance();

        return $this->createFormBuilderForEntity($entity);
    }// end createNewFormBuilder()

    /**
     * @inheritDoc
     */
    private function createFormBuilderForEntity(?Furniture $entity): FormBuilderInterface
    {
        return $this->container->get('form.factory')->createBuilder(
            FurnitureType::class,
            $entity
        );
    }// end createFormBuilderForEntity()
}// end class
