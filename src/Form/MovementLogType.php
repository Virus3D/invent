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

class MovementLogType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'inventoryItem',
                EntityType::class,
                [
                    'label'        => 'Инвентарный объект',
                    'class'        => InventoryItem::class,
                    'choice_label' => function (InventoryItem $item) {
                        return sprintf('%s (%s)', $item->getName(), $item->getInventoryNumber());
                    },
                    'attr'         => ['class' => 'form-select'],
                ]
            )
            ->add(
                'fromLocation',
                EntityType::class,
                [
                    'label'        => 'Откуда',
                    'class'        => Location::class,
                    'choice_label' => 'name',
                    'required'     => false,
                    'placeholder'  => 'Не указано',
                    'attr'         => ['class' => 'form-select'],
                ]
            )
            ->add(
                'toLocation',
                EntityType::class,
                [
                    'label'        => 'Куда',
                    'class'        => Location::class,
                    'choice_label' => 'name',
                    'required'     => false,
                    'placeholder'  => 'Не указано',
                    'attr'         => ['class' => 'form-select'],
                ]
            )
            ->add(
                'reason',
                TextareaType::class,
                [
                    'label'    => 'Причина перемещения',
                    'required' => false,
                    'attr'     => [
                        'class' => 'form-control',
                        'rows'  => 3,
                    ],
                ]
            )
            ->add(
                'movedBy',
                TextType::class,
                [
                    'label' => 'Кто переместил',
                    'attr'  => ['class' => 'form-control'],
                ]
            );
    }// end buildForm()

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => MovementLog::class,
            ]
        );
    }// end configureOptions()
}// end class
