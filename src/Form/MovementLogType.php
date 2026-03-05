<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\InventoryItem;
use App\Entity\Location;
use App\Entity\MovementLog;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use function sprintf;

final class MovementLogType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'inventoryItem',
                EntityType::class,
                [
                    'class'        => InventoryItem::class,
                    'choice_label' => static fn (InventoryItem $item) => sprintf(
                        '%s (%s)',
                        $item->getName(),
                        $item->getInventoryNumber()
                    ),
                ]
            )
            ->add(
                'fromLocation',
                EntityType::class,
                [
                    'label'        => 'move.form.from_location',
                    'class'        => Location::class,
                    'choice_label' => 'name',
                    'placeholder'  => 'move.not_specified',
                    'required'     => false,
                    'attr'         => ['data-inventory-move-target' => 'fromLocation'],
                    'help'         => 'move.form.from_location_hint',
                ]
            )
            ->add(
                'toLocation',
                EntityType::class,
                [
                    'label'        => 'move.form.to_location',
                    'class'        => Location::class,
                    'choice_label' => 'name',
                    'placeholder'  => 'move.not_specified',
                    'required'     => false,
                    'attr'         => [
                        'data-inventory-move-target' => 'toLocation',
                        'data-action'                => 'change->inventory-move#validateToLocation',
                    ],
                    'help'         => 'move.form.to_location_hint',
                    'constraints'  => [
                        new NotBlank(message: 'move.form.to_location_required'),
                    ],
                ]
            )
            ->add(
                'reason',
                TextareaType::class,
                [
                    'label'    => 'move.form.reason',
                    'required' => false,
                    'attr'     => [
                        'rows'        => 3,
                        'placeholder' => 'move.form.reason_placeholder',
                    ],
                    'help'     => 'move.form.reason_hint',
                ]
            )
            ->add(
                'movedBy',
                TextType::class,
                [
                    'label'       => 'move.form.moved_by',
                    'attr'        => [
                        'placeholder'                => 'move.form.moved_by_placeholder',
                        'data-inventory-move-target' => 'movedBy',
                        'data-action'                => 'input->inventory-move#validateMovedBy',
                    ],
                    'constraints' => [
                        new NotBlank(message: 'move.form.moved_by_required'),
                    ],
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
                'data_class'         => MovementLog::class,
                'translation_domain' => 'move',
            ]
        );
    }// end configureOptions()
}// end class
