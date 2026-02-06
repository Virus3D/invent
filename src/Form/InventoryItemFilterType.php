<?php

declare(strict_types=1);

namespace App\Form;

use App\Enum\InventoryCategory;
use App\Enum\ItemStatus;
use App\Enum\ItemType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class InventoryItemFilterType extends AbstractType
{
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
                        'class' => 'form-control filter-input',
                    ],
                ]
            )
            ->add(
                'category',
                EnumType::class,
                [
                    'label'        => 'inventory_item.form.category',
                    'class'        => InventoryCategory::class,
                    'choice_label' => fn (InventoryCategory $category) => "inventory_item.category.{$category->value}",
                    'required'     => false,
                    'placeholder'  => 'Все категории',
                    'attr'         => [
                        'class'          => 'form-select category-selector filter-input',
                        'data-specs-url' => $options['specs_url'] ?? null,
                    ],
                ]
            )
            ->add(
                'status',
                EnumType::class,
                [
                    'label'        => 'inventory_item.form.status',
                    'class'        => ItemStatus::class,
                    'choice_label' => fn (ItemStatus $status) => "inventory_item.status.{$status->value}",
                    'required'     => false,
                    'placeholder'  => 'Все статусы',
                    'attr'         => [
                        'class' => 'form-select filter-input',
                    ],
                ]
            );
    }// end buildForm()

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'method'             => 'GET',
                'csrf_protection'    => false,
                'allow_extra_fields' => true,
            ]
        );
    }// end configureOptions()

    public function getBlockPrefix(): string
    {
        return '';
    }// end getBlockPrefix()
}// end class
