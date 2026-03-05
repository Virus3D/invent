<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Cartridge;
use App\Entity\CartridgeInstallation;
use App\Entity\InventoryItem;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

use function sprintf;

final class CartridgeInstallationType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'printer',
                EntityType::class,
                [
                    'label'         => 'inventory.printer',
                    'class'         => InventoryItem::class,
                    'choice_label'  => static fn (InventoryItem $p) => sprintf(
                        '%s [%s]',
                        $p->getName(),
                        $p->getInventoryNumber() ?? '-'
                    ),
                    'placeholder'   => 'cartridge.form.printer_select',
                    'query_builder' => static fn (EntityRepository $er) => $er
                        ->createQueryBuilder('p')
                        ->where('p.category = :printerCategory')
                        ->setParameter('printerCategory', 'printer')
                        ->orderBy('p.name', 'ASC'),
                    'disabled'      => $options['printer_locked'] ?? false,
                ]
            )
            ->add(
                'cartridge',
                EntityType::class,
                [
                    'label'         => 'cartridge.form.cartridge_type',
                    'class'         => Cartridge::class,
                    'choice_label'  => static fn (Cartridge $c) => sprintf(
                        '%s%s (%d шт. на складе)',
                        $c->getName(),
                        $c->getColor() ? ' • ' . ucfirst($c->getColor()) : '',
                        $c->getStockQuantity()
                    ),
                    'placeholder'   => 'cartridge.form.cartridge_select',
                    'required'      => ! $options['edit_mode'],
                    'disabled'      => $options['cartridge_locked'] ?? false,
                    'query_builder' => static function (EntityRepository $er) use ($options) {
                        $qb = $er->createQueryBuilder('c')
                            ->orderBy('c.name', 'ASC');

                        // Если указан принтер — фильтруем по совместимости.
                        if ($options['printer']) {
                            $qb->leftJoin('c.printers', 'p')
                                ->where('p.id = :printerId OR SIZE(c.printers) = 0')
                                ->setParameter('printerId', $options['printer']->getId());
                        }

                        // Скрываем отсутствующие на складе (только для новых установок).
                        if (! $options['edit_mode']) {
                            $qb->andWhere('c.stockQuantity > 0');
                        }

                        return $qb;
                    },
                ]
            )
            ->add(
                'printedPages',
                IntegerType::class,
                [
                    'label'       => 'cartridge.form.printed_pages',
                    'required'    => false,
                    'attr'        => [
                        'min'         => 0,
                        'placeholder' => 'cartridge.form.printed_pages_placeholder',
                    ],
                    'constraints' => [new PositiveOrZero()],
                    'help'        => 'cartridge.form.printed_pages_help',
                ]
            )
            ->add(
                'comment',
                TextareaType::class,
                [
                    'label'    => 'cartridge.form.comment',
                    'required' => false,
                    'attr'     => [
                        'rows'        => 3,
                        'placeholder' => 'cartridge.form.comment_placeholder',
                    ],
                ]
            );

        // Динамическая фильтрация картриджей при выборе принтера.
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            static function (FormEvent $event): void {
                $form         = $event->getForm();
                $installation = $event->getData();

                if ($installation && $installation->getPrinter() && ! $form->has('printer')) {
                    // Обновляем фильтр картриджей при редактировании.
                    $form->add(
                        'cartridge',
                        EntityType::class,
                        [
                            'label'         => 'cartridge.form.cartridge_type',
                            'class'         => Cartridge::class,
                            'choice_label'  => static fn (Cartridge $c) => sprintf(
                                '%s%s (%d шт. на складе)',
                                $c->getName(),
                                $c->getColor() ? ' • ' . ucfirst($c->getColor()) : '',
                                $c->getStockQuantity()
                            ),
                            'placeholder'   => 'cartridge.form.cartridge_select',
                            'required'      => ! $options['edit_mode'],
                            'disabled'      => $options['cartridge_locked'] ?? false,
                            'query_builder' => static function (EntityRepository $er) use ($options) {
                                $qb = $er->createQueryBuilder('c')
                                    ->orderBy('c.name', 'ASC')
                                ;

                                // Если указан принтер — фильтруем по совместимости.
                                if ($options['printer']) {
                                    $qb->leftJoin('c.printers', 'p')
                                        ->where('p.id = :printerId OR SIZE(c.printers) = 0')
                                        ->setParameter('printerId', $options['printer']->getId())
                                    ;
                                }

                                // Скрываем отсутствующие на складе (только для новых установок).
                                if (! $options['edit_mode']) {
                                    $qb->andWhere('c.stockQuantity > 0');
                                }

                                return $qb;
                            },
                        ]
                    );
                }// end if
            }
        );
    }// end buildForm()

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(
                [
                    'data_class'         => CartridgeInstallation::class,
                    'attr'               => ['novalidate' => 'novalidate'],
                    'printer_locked'     => false,
                    'cartridge_locked'   => false,
                    'edit_mode'          => false,
                    'printer'            => null,
                    'translation_domain' => 'consumables',
                ]
            )
            ->setAllowedTypes('printer_locked', 'bool')
            ->setAllowedTypes('cartridge_locked', 'bool')
            ->setAllowedTypes('edit_mode', 'bool')
            ->setAllowedTypes('printer', ['null', InventoryItem::class]);
    }// end configureOptions()
}// end class
