<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Location;
use App\Entity\SoftwareLicense;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SoftwareLicenseType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }// end __construct()

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label'       => $this->translator->trans('license.form.name'),
                    'constraints' => [new NotBlank()],
                    'attr'        => [
                        'class'       => 'form-control',
                        'placeholder' => $this->translator->trans('license.form.name_placeholder'),
                    ],
                ]
            )
            ->add(
                'licenseKey',
                TextType::class,
                [
                    'label'    => $this->translator->trans('license.form.key'),
                    'required' => false,
                    'attr'     => [
                        'class'       => 'form-control',
                        'placeholder' => $this->translator->trans('license.form.key_placeholder'),
                    ],
                ]
            )
            ->add(
                'startDate',
                DateType::class,
                [
                    'label'  => $this->translator->trans('license.form.start_date'),
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd',
                    'attr'   => ['class' => 'form-control'],
                ]
            )
            ->add(
                'endDate',
                DateType::class,
                [
                    'label'    => $this->translator->trans('license.form.end_date'),
                    'widget'   => 'single_text',
                    'format'   => 'yyyy-MM-dd',
                    'required' => false,
                    'attr'     => [
                        'class'       => 'form-control',
                        'placeholder' => $this->translator->trans('license.form.end_date_placeholder'),
                    ],
                ]
            )
            ->add(
                'valid',
                CheckboxType::class,
                [
                    'label'    => $this->translator->trans('license.form.valid'),
                    'required' => false,
                    'attr'     => ['class' => 'form-check-input'],
                ]
            )
            ->add(
                'location',
                EntityType::class,
                [
                    'label'        => $this->translator->trans('license.form.location'),
                    'class'        => Location::class,
                    'choice_label' => 'name',
                    'required'     => false,
                    'placeholder'  => $this->translator->trans('license.form.location_placeholder'),
                    'attr'         => ['class' => 'form-select'],
                ]
            );
    }// end buildForm()

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => SoftwareLicense::class,
            ]
        );
    }// end configureOptions()
}// end class
