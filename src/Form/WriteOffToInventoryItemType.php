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

use function sprintf;

final class WriteOffToInventoryItemType extends AbstractType
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
                ['label' => 'write_off.form.quantity']
            )
            ->add(
                'inventoryItem',
                EntityType::class,
                [
                    'class'        => InventoryItem::class,
                    'choice_label' => static fn (InventoryItem $p) => sprintf(
                        '%s%s%s',
                        $p->getName(),
                        $p->getInventoryNumber() ? " [{$p->getInventoryNumber()}]" : '',
                        $p->getLocation() ? " — {$p->getLocation()}" : '',
                    ),
                    'label'        => 'write_off.form.inventory_item',
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
        $resolver->setDefaults(
            [
                'csrf_protection'    => true,
                'csrf_field_name'    => '_token',
                'csrf_token_id'      => 'writeoff',
                'translation_domain' => 'material',
            ]
        );
    }// end configureOptions()
}// end class
