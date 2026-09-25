<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Furniture;
use App\Enum\BalanceType;
use App\Enum\FurnitureCategory;
use App\Enum\ItemStatus;
use App\Form\Type\LocationFieldType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class FurnitureBatchCreateType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label' => 'form.name',
                    'attr'  => [
                        'placeholder' => 'form.name_placeholder',
                        'autofocus'   => 'autofocus',
                    ],
                ]
            )
            ->add(
                'inventoryNumber',
                TextType::class,
                [
                    'label'      => 'form.inventory_number',
                    'label_attr' => ['data-inventory-form-target' => 'inventoryNumberLabel'],
                    'required'   => false,
                    'attr'       => [
                        'placeholder'                => 'form.inventory_number_placeholder',
                        'data-inventory-form-target' => 'inventoryNumberField',
                    ],
                    'help'       => 'form.inventory_number_help',
                ]
            )
            ->add(
                'balanceType',
                EnumType::class,
                [
                    'label' => 'form.balance_type',
                    'class' => BalanceType::class,
                    'attr'  => [
                        'class'                      => 'balance-type-selector',
                        'data-action'                => 'change->inventory-form#onBalanceTypeChange',
                        'data-inventory-form-target' => 'balanceType',
                    ],
                ]
            )
            ->add(
                'category',
                EnumType::class,
                [
                    'label' => 'form.category',
                    'class' => FurnitureCategory::class,
                ]
            )
            ->add(
                'quantity',
                NumberType::class,
                [
                    'label'  => 'form.batch_quantity',
                    'attr'   => [
                        'min'   => 1,
                        'value' => 1,
                    ],
                    'mapped' => false,
                ]
            )
            ->add(
                'status',
                EnumType::class,
                [
                    'label'    => 'form.status',
                    'class'    => ItemStatus::class,
                    'required' => false,
                ]
            )
            ->add(
                'purchasePrice',
                null,
                [
                    'label'    => 'form.purchase_price',
                    'required' => false,
                    'attr'     => ['placeholder' => '0.00'],
                ]
            )
            ->add(
                'purchaseDate',
                DateType::class,
                [
                    'label'    => 'form.purchase_date',
                    'required' => false,
                    'widget'   => 'single_text',
                ]
            )
            ->add(
                'responsiblePerson',
                TextType::class,
                [
                    'label'    => 'form.responsible_person',
                    'required' => false,
                    'attr'     => ['placeholder' => 'form.responsible_person_placeholder'],
                ]
            )
            ->add(
                'location',
                LocationFieldType::class,
                [
                    'label'    => 'form.location',
                    'required' => false,
                ]
            )
            ->add(
                'description',
                TextareaType::class,
                [
                    'label'    => 'form.description',
                    'required' => false,
                    'attr'     => [
                        'rows'        => 3,
                        'placeholder' => 'form.description_placeholder',
                    ],
                ]
            );
    }// end buildForm()

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            ['translation_domain' => 'furniture']
        );
    }// end configureOptions()
}// end class
