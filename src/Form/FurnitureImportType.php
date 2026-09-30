<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Location;
use App\Form\Type\LocationFieldType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class FurnitureImportType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'file',
                FileType::class,
                [
                    'label'    => 'import.file',
                    'required' => true,
                    'attr'     => ['accept' => '.xls,.xlsx'],
                    'mapped'   => false,
                ]
            )
            ->add(
                'location',
                LocationFieldType::class,
                [
                    'label'       => 'import.default_location',
                    'required'    => false,
                    'placeholder' => 'import.default_location_placeholder',
                    'help'        => 'import.default_location_help',
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
