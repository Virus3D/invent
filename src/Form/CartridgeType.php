<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Cartridge;
use App\Entity\InventoryItem;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

use function sprintf;

final class CartridgeType extends AbstractType
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
                    'label' => 'cartridge.form.name',
                    'attr'  => ['placeholder' => 'cartridge.form.name_placeholder'],
                    'help'  => 'cartridge.form.name_help',
                ]
            )
            ->add(
                'color',
                ChoiceType::class,
                [
                    'label'       => 'cartridge.form.color',
                    'choices'     => [
                        'cartridge.colors.black'   => 'black',
                        'cartridge.colors.cyan'    => 'cyan',
                        'cartridge.colors.magenta' => 'magenta',
                        'cartridge.colors.yellow'  => 'yellow',
                        'cartridge.colors.color'   => 'color',
                        'cartridge.colors.other'   => 'other',
                    ],
                    'placeholder' => 'cartridge.form.color_placeholder',
                ]
            )
            ->add(
                'yieldPages',
                IntegerType::class,
                [
                    'label'       => 'cartridge.form.yield_pages',
                    'required'    => false,
                    'attr'        => [
                        'min'         => 0,
                        'placeholder' => 'cartridge.form.yield_pages_placeholder',
                    ],
                    'constraints' => [new PositiveOrZero()],
                    'help'        => 'cartridge.form.yield_pages_help',
                ]
            )
            ->add(
                'stockQuantity',
                IntegerType::class,
                [
                    'label'       => 'cartridge.form.stock_quantity',
                    'attr'        => ['min' => 0],
                    'constraints' => [new PositiveOrZero()],
                    'help'        => 'cartridge.form.stock_quantity_help',
                ]
            )
            ->add(
                'printers',
                EntityType::class,
                [
                    'label'         => 'cartridge.form.printers',
                    'class'         => InventoryItem::class,
                    'choice_label'  => static fn (InventoryItem $p) => sprintf(
                        '%s (%s)',
                        $p->getName(),
                        $p->getInventoryNumber() ?? '-'
                    ),
                    'multiple'      => true,
                    'expanded'      => false,
                    'required'      => false,
                    'placeholder'   => 'cartridge.form.printers_placeholder',
                    'query_builder' => static fn (EntityRepository $er) => $er
                        ->createQueryBuilder('p')
                        ->where('p.category = :printerCategory')
                        ->setParameter('printerCategory', 'printer')
                        ->orderBy('p.name', 'ASC'),
                    'help'          => 'cartridge.form.printers_help',
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
                'data_class'         => Cartridge::class,
                'attr'               => ['novalidate' => 'novalidate'],
                'translation_domain' => 'cartridge',
            ]
        );
    }// end configureOptions()
}// end class
