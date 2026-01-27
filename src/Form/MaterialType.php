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
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MaterialType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

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
                    'label'       => $this->translator->trans('material.form.name'),
                    'constraints' => [new NotBlank()],
                    'attr'        => [
                        'class'       => 'form-control',
                        'placeholder' => $this->translator->trans('material.form.name_placeholder'),
                    ],
                ]
            )
            ->add(
                'description',
                TextareaType::class,
                [
                    'label'    => $this->translator->trans('material.form.description'),
                    'required' => false,
                    'attr'     => [
                        'class'       => 'form-control',
                        'rows'        => 4,
                        'placeholder' => $this->translator->trans('material.form.description_placeholder'),
                    ],
                ]
            )
            ->add(
                'quantity',
                NumberType::class,
                [
                    'label'       => $this->translator->trans('material.form.quantity'),
                    'scale'       => 2,
                    'html5'       => true,
                    'attr'        => [
                        'class'       => 'form-control',
                        'placeholder' => '0.00',
                        'inputmode'   => 'decimal',
                        'step'        => '0.01',
                        'min'         => '0',
                    ],
                    'constraints' => [
                        new PositiveOrZero(
                            [
                                'message' => $this->translator->trans('validation.positive_or_zero'),
                            ]
                        ),
                    ],
                ]
            )
            ->add(
                'location',
                EntityType::class,
                [
                    'label'        => $this->translator->trans('material.form.location'),
                    'class'        => Location::class,
                    'choice_label' => 'name',
                    'required'     => false,
                    'placeholder'  => $this->translator->trans('material.form.location_placeholder'),
                    'attr'         => ['class' => 'form-select'],
                ]
            );
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Material::class,
            ]
        );
    }
}
