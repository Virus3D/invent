<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Location;
use App\Entity\Material;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class MaterialType extends AbstractType
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
                    'label'       => 'material.form.name',
                    'constraints' => [new NotBlank()],
                    'attr'        => ['placeholder' => 'material.form.name_placeholder'],
                ]
            )
            ->add(
                'description',
                TextareaType::class,
                [
                    'label'    => 'material.form.description',
                    'required' => false,
                    'attr'     => [
                        'rows'        => 4,
                        'placeholder' => 'material.form.description_placeholder',
                    ],
                ]
            )
            ->add(
                'quantity',
                NumberType::class,
                [
                    'label' => 'material.form.quantity',
                    'scale' => 2,
                    'html5' => true,
                    'attr'  => [
                        'inputmode' => 'numeric',
                        'step'      => '1',
                        'min'       => '0',
                    ],
                ]
            )
            ->add(
                'location',
                EntityType::class,
                [
                    'label'        => 'material.form.location',
                    'class'        => Location::class,
                    'choice_label' => 'name',
                    'required'     => false,
                    'placeholder'  => 'material.form.location_placeholder',
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
                'data_class'         => Material::class,
                'csrf_protection'    => true,
                'csrf_field_name'    => '_token',
                'csrf_token_id'      => 'material',
                'translation_domain' => 'material',
            ]
        );
    }// end configureOptions()
}// end class
