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
use Symfony\Component\Validator\Constraints\PositiveOrZero;

final class FurnitureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label' => 'furniture.form.name',
                    'attr'  => [
                        'placeholder' => 'furniture.form.name_placeholder',
                        'autofocus'   => 'autofocus',
                    ],
                ]
            )
            ->add(
                'description',
                TextareaType::class,
                [
                    'label'    => 'furniture.form.description',
                    'required' => false,
                    'attr'     => [
                        'rows'        => 3,
                        'placeholder' => 'furniture.form.description_placeholder',
                    ],
                ]
            )
            ->add(
                'inventoryNumber',
                TextType::class,
                [
                    'label'      => 'furniture.form.inventory_number',
                    'required'   => false,
                    'attr'       => [
                        'placeholder' => 'furniture.form.inventory_number_placeholder',
                    ],
                    'help'       => 'furniture.form.inventory_number_help',
                ]
            )
            ->add(
                'category',
                EnumType::class,
                [
                    'label' => 'furniture.form.category',
                    'class' => FurnitureCategory::class,
                ]
            )
            ->add(
                'balanceType',
                EnumType::class,
                [
                    'label' => 'furniture.form.balance_type',
                    'class' => BalanceType::class,
                ]
            )
            ->add(
                'status',
                EnumType::class,
                [
                    'label'    => 'furniture.form.status',
                    'class'    => ItemStatus::class,
                    'required' => false,
                ]
            )
            ->add(
                'purchasePrice',
                NumberType::class,
                [
                    'label'       => 'furniture.form.purchase_price',
                    'required'    => false,
                    'scale'       => 2,
                    'html5'       => false,
                    'attr'        => [
                        'placeholder' => '0.00',
                        'inputmode'   => 'decimal',
                        'step'        => '0.01',
                        'min'         => '0',
                    ],
                    'constraints' => [
                        new PositiveOrZero(),
                    ],
                ]
            )
            ->add(
                'purchaseDate',
                DateType::class,
                [
                    'label'    => 'furniture.form.purchase_date',
                    'required' => false,
                    'widget'   => 'single_text',
                    'html5'    => true,
                    'attr'     => [
                        'placeholder'  => 'furniture.form.purchase_date_placeholder',
                        'autocomplete' => 'off',
                    ],
                ]
            )
            ->add(
                'responsiblePerson',
                TextType::class,
                [
                    'label'    => 'furniture.form.responsible_person',
                    'required' => false,
                    'attr'     => ['placeholder' => 'furniture.form.responsible_person_placeholder'],
                ]
            )
            ->add(
                'location',
                LocationFieldType::class,
                [
                    'label'       => 'furniture.form.location',
                    'required'    => false,
                    'placeholder' => 'furniture.form.location_placeholder',
                ]
            );
    }// end buildForm()

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class'         => Furniture::class,
                'csrf_protection'    => false,
                'empty_data'         => static function () {
                    $furniture = new Furniture();
                    $furniture->setCategory(FurnitureCategory::OTHER);
                    $furniture->setBalanceType(BalanceType::ON_BALANCE);
                    $furniture->setStatus(ItemStatus::NEW);

                    return $furniture;
                },
                'translation_domain' => 'furniture',
            ]
        );
    }// end configureOptions()
}// end class
