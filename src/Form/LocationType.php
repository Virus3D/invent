<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Location;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class LocationType extends AbstractType
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
                    'label' => 'location.form.name_label',
                    'attr'  => [
                        'placeholder' => 'location.form.name_placeholder',
                        'autofocus'   => 'autofocus',
                    ],
                    'help'  => 'location.form.name_hint',
                ]
            )
            ->add(
                'roomNumber',
                TextType::class,
                [
                    'label' => 'location.form.room_number_label',
                    'attr'  => ['placeholder' => 'location.form.room_number_placeholder'],
                    'help'  => 'location.form.room_number_hint',
                ]
            )
            ->add(
                'description',
                TextareaType::class,
                [
                    'label'    => 'location.form.description_label',
                    'attr'     => [
                        'placeholder' => 'location.form.description_placeholder',
                        'rows'        => 3,
                    ],
                    'required' => false,
                    'help'     => 'location.form.description_hint',
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
                'data_class'         => Location::class,
                'csrf_protection'    => true,
                'csrf_field_name'    => '_token',
                'csrf_token_id'      => 'location',
                'translation_domain' => 'location',
            ]
        );
    }// end configureOptions()
}// end class
