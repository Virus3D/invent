<?php

declare(strict_types=1);

namespace App\Form;

use App\Enum\InventoryCategory;
use App\Enum\ItemStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class InventoryItemFilterType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'required' => false,
                    'label'    => 'inventory_item.form.name',
                    'attr'     => [
                        'placeholder' => 'inventory_item.form.name_placeholder',
                        'class'       => 'form-control filter-input',
                    ],
                ]
            )
            ->add(
                'category',
                EnumType::class,
                [
                    'label'       => 'inventory_item.form.category',
                    'class'       => InventoryCategory::class,
                    'required'    => false,
                    'placeholder' => 'inventory_item.form.all_categories',
                    'attr'        => [
                        'class'          => 'form-select category-selector filter-input',
                        'data-specs-url' => $options['specs_url'] ?? null,
                    ],
                ]
            )
            ->add(
                'status',
                EnumType::class,
                [
                    'label'       => 'inventory_item.form.status',
                    'class'       => ItemStatus::class,
                    'required'    => false,
                    'placeholder' => 'inventory_item.form.all_statuses',
                    'attr'        => ['class' => 'filter-input'],
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
                'method'             => 'GET',
                'csrf_protection'    => false,
                'allow_extra_fields' => true,
                'translation_domain' => 'inventory',
            ]
        );
    }// end configureOptions()
}// end class
