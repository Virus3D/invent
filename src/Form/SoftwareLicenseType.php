<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Location;
use App\Entity\SoftwareLicense;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class SoftwareLicenseType extends AbstractType
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
                    'label'       => 'license.form.name',
                    'constraints' => [new NotBlank()],
                    'attr'        => ['placeholder' => 'license.form.name_placeholder'],
                ]
            )
            ->add(
                'licenseKey',
                TextType::class,
                [
                    'label'    => 'license.form.key',
                    'required' => false,
                    'attr'     => ['placeholder' => 'license.form.key_placeholder'],
                ]
            )
            ->add(
                'startDate',
                DateType::class,
                [
                    'label'  => 'license.form.start_date',
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd',
                    'attr'   => ['class' => 'datepicker'],
                ]
            )
            ->add(
                'endDate',
                DateType::class,
                [
                    'label'    => 'license.form.end_date',
                    'widget'   => 'single_text',
                    'format'   => 'yyyy-MM-dd',
                    'required' => false,
                    'attr'     => [
                        'class'       => 'datepicker',
                        'placeholder' => 'license.form.end_date_placeholder',
                    ],
                    'help'     => 'license.form.end_date_hint',
                ]
            )
            ->add(
                'valid',
                CheckboxType::class,
                [
                    'label'    => 'license.form.valid',
                    'required' => false,
                    'attr'     => ['class' => 'form-check-input'],
                ]
            )
            ->add(
                'location',
                EntityType::class,
                [
                    'label'        => 'license.form.location',
                    'class'        => Location::class,
                    'choice_label' => 'name',
                    'required'     => false,
                    'placeholder'  => 'license.form.location_placeholder',
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
                'data_class'         => SoftwareLicense::class,
                'csrf_protection'    => true,
                'csrf_field_name'    => '_token',
                'csrf_token_id'      => 'license',
                'translation_domain' => 'license',
            ]
        );
    }// end configureOptions()
}// end class
