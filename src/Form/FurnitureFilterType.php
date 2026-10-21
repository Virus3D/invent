<?php

declare(strict_types=1);

namespace App\Form;

use App\Enum\FurnitureCategory;
use App\Enum\ItemStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class FurnitureFilterType extends AbstractType
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
                    'label'    => 'form.name',
                    'attr'     => [
                        'placeholder' => 'form.name_placeholder',
                        'class'       => 'form-control',
                    ],
                ]
            )
            ->add(
                'category',
                EnumType::class,
                [
                    'label'       => 'form.category',
                    'class'       => FurnitureCategory::class,
                    'required'    => false,
                    'placeholder' => 'form.all_categories',
                    'attr'        => [
                        'class'          => 'form-select',
                        'data-specs-url' => $options['specs_url'] ?? null,
                    ],
                ]
            )
            ->add(
                'status',
                EnumType::class,
                [
                    'label'       => 'form.status',
                    'class'       => ItemStatus::class,
                    'required'    => false,
                    'placeholder' => 'form.all_statuses',
                    'attr'        => ['class' => 'form-select'],
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
                'translation_domain' => 'furniture',
            ]
        );
    }// end configureOptions()
}// end class
