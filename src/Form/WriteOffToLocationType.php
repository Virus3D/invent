<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Location;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WriteOffToLocationType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'quantity',
                NumberType::class,
                [
                    'label' => 'write_off.form.quantity',
                    'html5' => true,
                ]
            )
            ->add(
                'location',
                EntityType::class,
                [
                    'class'        => Location::class,
                    'choice_label' => fn(Location $l) => $l->getName() . ' (' . $l->getRoomNumber() . ')',
                    'label'        => 'write_off.form.location',
                    'autocomplete' => true,
                ]
            )
            ->add(
                'comment',
                TextareaType::class,
                [
                    'label'    => 'write_off.form.comment',
                    'required' => false,
                ]
            );
    }// end buildForm()

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection'    => true,
            'csrf_field_name'    => '_token',
            'csrf_token_id'      => 'writeoff',
            'translation_domain' => 'material',
        ]);
    }// end configureOptions()
}// end class
