<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\InventoryItem;
use App\Entity\Location;
use App\Enum\BalanceType;
use App\Enum\InventoryCategory;
use App\Enum\ItemStatus;
use App\Enum\ItemType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

final class InventoryItemType extends AbstractType
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
                    'label' => 'inventory_item.form.name',
                    'attr'  => [
                        'placeholder' => 'inventory_item.form.name_placeholder',
                        'autofocus'   => 'autofocus',
                    ],
                ]
            )
            ->add(
                'description',
                TextareaType::class,
                [
                    'label'    => 'inventory_item.form.description',
                    'required' => false,
                    'attr'     => [
                        'rows'        => 3,
                        'placeholder' => 'inventory_item.form.description_placeholder',
                    ],
                ]
            )
            ->add(
                'inventoryNumber',
                TextType::class,
                [
                    'label'      => 'inventory_item.form.inventory_number',
                    'label_attr' => ['data-inventory-form-target' => 'inventoryNumberLabel'],
                    'required'   => false,
                    'attr'       => [
                        'placeholder'                => 'inventory_item.form.inventory_number_placeholder',
                        'data-inventory-form-target' => 'inventoryNumberField',
                    ],
                    'help'       => 'inventory_item.form.inventory_number_help',
                ]
            )
            ->add(
                'serialNumber',
                TextType::class,
                [
                    'label'    => 'inventory_item.form.serial_number',
                    'required' => false,
                    'attr'     => ['placeholder' => 'inventory_item.form.serial_number_placeholder'],
                ]
            )
            ->add(
                'category',
                EnumType::class,
                [
                    'label' => 'inventory_item.form.category',
                    'class' => InventoryCategory::class,
                    'attr'  => [
                        'class'                      => 'category-selector',
                        'data-specs-url'             => $options['specs_url'] ?? null,
                        'data-inventory-form-target' => 'categorySelector',
                        'data-action'                => 'change->inventory-form#onCategoryChange',
                    ],
                ]
            )
            ->add(
                'balanceType',
                EnumType::class,
                [
                    'label' => 'inventory_item.form.balance_type',
                    'class' => BalanceType::class,
                    'attr'  => [
                        'class'                      => 'balance-type-selector',
                        'data-action'                => 'change->inventory-form#onBalanceTypeChange',
                        'data-inventory-form-target' => 'balanceType',
                    ],
                ]
            )
            ->add(
                'status',
                EnumType::class,
                [
                    'label' => 'inventory_item.form.status',
                    'class' => ItemStatus::class,
                ]
            )
            ->add(
                'type',
                EnumType::class,
                [
                    'label' => 'inventory_item.form.type',
                    'class' => ItemType::class,
                ]
            )
            ->add(
                'purchasePrice',
                NumberType::class,
                [
                    'label'       => 'inventory_item.form.purchase_price',
                    'required'    => false,
                    'scale'       => 2,
                    'html5'       => false,
                    'attr'        => [
                        'placeholder' => '0.00',
                        'inputmode'   => 'decimal',
                        'step'        => '0.01',
                        'min'         => '0',
                    ],
                    'constraints' => [
                        new PositiveOrZero(),
                    ],
                ]
            )
            ->add(
                'purchaseDate',
                DateType::class,
                [
                    'label'    => 'inventory_item.form.purchase_date',
                    'required' => false,
                    'widget'   => 'single_text',
                    'html5'    => false,
                    'format'   => 'dd.MM.yyyy',
                    'attr'     => [
                        'class'       => 'datepicker',
                        'placeholder' => 'inventory_item.form.purchase_date_placeholder',
                    ],
                ]
            )
            ->add(
                'commissioningDate',
                DateType::class,
                [
                    'label'    => 'inventory_item.form.commissioning_date',
                    'required' => false,
                    'widget'   => 'single_text',
                    'html5'    => false,
                    'format'   => 'dd.MM.yyyy',
                    'attr'     => [
                        'class'       => 'datepicker',
                        'placeholder' => 'inventory_item.form.commissioning_date_placeholder',
                    ],
                ]
            )
            ->add(
                'responsiblePerson',
                TextType::class,
                [
                    'label'    => 'inventory_item.form.responsible_person',
                    'required' => false,
                    'attr'     => ['placeholder' => 'inventory_item.form.responsible_person_placeholder'],
                ]
            )
            ->add(
                'location',
                EntityType::class,
                [
                    'label'        => 'inventory_item.form.location',
                    'class'        => Location::class,
                    'choice_label' => 'name',
                    'required'     => false,
                    'placeholder'  => 'inventory_item.form.location_placeholder',
                ]
            );

        // Добавляем событие для динамического изменения обязательности полей.
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            static function (FormEvent $event): void {
                $data = $event->getData();
                $form = $event->getForm();

                if (! $data instanceof InventoryItem) {
                    return;
                }

                // Если объект на балансе, но инвентарный номер не указан.
                if ($data->isOnBalance() && empty($data->getInventoryNumber())) {
                    $form->get('inventoryNumber')->addError(
                        new FormError('validation.inventory_number_required')
                    );
                }
            }
        );
    }// end buildForm()

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class'         => InventoryItem::class,
                'csrf_protection'    => false,
                'specs_url'          => null,
                'empty_data'         => static function () {
                    $item = new InventoryItem();
                    $item->setCategory(InventoryCategory::OTHER);
                    $item->setBalanceType(BalanceType::ON_BALANCE);
                    $item->setStatus(ItemStatus::NEW);
                    $item->setType(ItemType::FIXED_ASSET);

                    return $item;
                },
                'translation_domain' => 'inventory',
            ]
        );

        $resolver->setAllowedTypes('specs_url', ['null', 'string']);
    }// end configureOptions()
}// end class
