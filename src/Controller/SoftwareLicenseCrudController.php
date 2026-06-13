<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SoftwareLicense;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SoftwareLicenseCrudController extends AbstractCrudController
{
    /**
     * @inheritDoc
     */
    public static function getEntityFqcn(): string
    {
        return SoftwareLicense::class;
    }// end getEntityFqcn()

    /**
     * @inheritDoc
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(50);
    }// end configureCrud()

    /**
     * @inheritDoc
     */
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name')->setColumns(12);
        yield TextField::new('licenseKey')->setColumns(12);
        yield DateField::new('startDate')->setColumns(6);
        yield DateField::new('endDate')
            ->setColumns(6)
            ->setHelp('entities.' . $this->getEntityFqcn() . '.properties.endDate_help')
            ->setTemplatePath('fields/license_end_date.html.twig');
        yield AssociationField::new('location');
        yield BooleanField::new('valid');
        yield DateField::new('createdAt')->onlyOnDetail();
        yield DateField::new('updatedAt')->onlyOnDetail();
    }// end configureFields()
}// end class
