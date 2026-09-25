<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Furniture;
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

            return $actions
                ->add(Crud::PAGE_INDEX, Action::DETAIL)
                ->add(Crud::PAGE_INDEX, $resetCheck);
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
}// end class
