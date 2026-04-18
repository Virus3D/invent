<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\InventoryItem;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WriteOffToInventoryItemType extends AbstractType
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
                ['label' => 'Количество']
            )
            ->add(
                'inventoryItem',
                EntityType::class,
                [
                    'class'        => InventoryItem::class,
                    'choice_label' => fn(InventoryItem $i) => $i->getName() . ' (инв. № ' . $i->getInventoryNumber() . ')',
                    'label'        => 'Оборудование',
                    'autocomplete' => true,
                ]
            )
            ->add(
                'comment',
                TextareaType::class,
                [
                    'label'    => 'Комментарий',
                    'required' => false,
                ]
            );
    }// end buildForm()

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }// end configureOptions()
}// end class
