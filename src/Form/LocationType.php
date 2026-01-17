<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Location;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LocationType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator) {}// end __construct()

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
                    'label'      => $this->translator->trans('location.form.name_label'),
                    'label_attr' => [
                        'class' => 'form-label required-field',
                    ],
                    'attr'       => [
                        'class'       => 'form-control',
                        'placeholder' => 'location.form.name_placeholder',
                    ],
                ]
            )
            ->add(
                'roomNumber',
                TextType::class,
                [
                    'label'      => $this->translator->trans('location.form.room_number_label'),
                    'label_attr' => [
                        'class' => 'form-label required-field',
                    ],
                    'attr'       => [
                        'class'       => 'form-control',
                        'placeholder' => 'location.form.room_number_placeholder',
                    ],
                ]
            )
            ->add(
                'description',
                TextareaType::class,
                [
                    'label'      => $this->translator->trans('location.form.description_label'),
                    'label_attr' => [
                        'class' => 'form-label required-field',
                    ],
                    'attr'       => [
                        'class'       => 'form-control',
                        'placeholder' => 'location.form.description_placeholder',
                        'rows'        => 3,
                    ],
                    'required'   => false,
                ]
            )
        ;
    }// end buildForm()

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class'      => Location::class,
                'csrf_protection' => true,
                'csrf_field_name' => '_token',
                'csrf_token_id'   => 'location',
            ]
        );
    }// end configureOptions()
}// end class
