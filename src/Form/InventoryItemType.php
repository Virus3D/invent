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
use Symfony\Contracts\Translation\TranslatorInterface;

final class InventoryItemType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator) {}// end __construct()

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
                    'label' => $this->translator->trans('inventory_item.form.name'),
                    'attr'  => [
                        'class'       => 'form-control',
                        'placeholder' => $this->translator->trans('inventory_item.form.name_placeholder'),
                    ],
                ]
            )
            ->add(
                'description',
                TextareaType::class,
                [
                    'label'    => $this->translator->trans('inventory_item.form.description'),
                    'required' => false,
                    'attr'     => [
                        'class'       => 'form-control',
                        'rows'        => 4,
                        'placeholder' => $this->translator->trans('inventory_item.form.description_placeholder'),
                    ],
                ]
            )
            ->add(
                'inventoryNumber',
                TextType::class,
                [
                    'label'    => $this->translator->trans('inventory_item.form.inventory_number'),
                    'required' => false,
                    'attr'     => [
                        'class'                      => 'form-control',
                        'placeholder'                => $this->translator->trans(
                            'inventory_item.form.inventory_number_placeholder'
                        ),
                        'data-controller'            => 'inventory-form',
                        'data-inventory-form-target' => 'inventoryNumberField',
                    ],
                ]
            )
            ->add(
                'serialNumber',
                TextType::class,
                [
                    'label'    => $this->translator->trans('inventory_item.form.serial_number'),
                    'required' => false,
                    'attr'     => [
                        'class'       => 'form-control',
                        'placeholder' => $this->translator->trans('inventory_item.form.serial_number_placeholder'),
                    ],
                ]
            )
            ->add(
                'category',
                EnumType::class,
                [
                    'label'        => $this->translator->trans('inventory_item.form.category'),
                    'class'        => InventoryCategory::class,
                    'choice_label' => fn (InventoryCategory $category) => $this->translator->trans(
                        "inventory_item.category.{$category->value}"
                    ),
                    'attr'         => [
                        'class'          => 'form-select category-selector',
                        'data-specs-url' => $options['specs_url'] ?? null,
                    ],
                ]
            )
            ->add(
                'balanceType',
                EnumType::class,
                [
                    'label'        => $this->translator->trans('inventory_item.form.balance_type'),
                    'class'        => BalanceType::class,
                    'choice_label' => fn (BalanceType $type) => $this->translator->trans(
                        "inventory_item.balance_type.{$type->value}"
                    ),
                    'attr'         => [
                        'class'                      => 'form-select balance-type-selector',
                        'data-action'                => 'change->inventory-form#onBalanceTypeChange',
                        'data-inventory-form-target' => 'balanceTypeSelector',
                    ],
                ]
            )
            ->add(
                'status',
                EnumType::class,
                [
                    'label'        => $this->translator->trans('inventory_item.form.status'),
                    'class'        => ItemStatus::class,
                    'choice_label' => fn (ItemStatus $status) => $this->translator->trans(
                        "inventory_item.status.{$status->value}"
                    ),
                    'attr'         => ['class' => 'form-select'],
                ]
            )
            ->add(
                'type',
                EnumType::class,
                [
                    'label'        => $this->translator->trans('inventory_item.form.type'),
                    'class'        => ItemType::class,
                    'choice_label' => fn (ItemType $type) => $this->translator->trans(
                        "inventory_item.type.{$type->value}"
                    ),
                    'attr'         => ['class' => 'form-select'],
                ]
            )
            ->add(
                'purchasePrice',
                NumberType::class,
                [
                    'label'       => $this->translator->trans('inventory_item.form.purchase_price'),
                    'required'    => false,
                    'scale'       => 2,
                    'html5'       => false,
                    'attr'        => [
                        'class'       => 'form-control',
                        'placeholder' => '0.00',
                        'inputmode'   => 'decimal',
                        'step'        => '0.01',
                        'min'         => '0',
                    ],
                    'constraints' => [
                        new PositiveOrZero(
                            [
                                'message' => $this->translator->trans('validation.positive_or_zero'),
                            ]
                        ),
                    ],
                ]
            )
            ->add(
                'purchaseDate',
                DateType::class,
                [
                    'label'    => $this->translator->trans('inventory_item.form.purchase_date'),
                    'required' => false,
                    'widget'   => 'single_text',
                    'html5'    => false,
                    'format'   => 'dd.MM.yyyy',
                    'attr'     => [
                        'class'       => 'form-control datepicker',
                        'placeholder' => $this->translator->trans('inventory_item.form.purchase_date_placeholder'),
                    ],
                ]
            )
            ->add(
                'commissioningDate',
                DateType::class,
                [
                    'label'    => $this->translator->trans('inventory_item.form.commissioning_date'),
                    'required' => false,
                    'widget'   => 'single_text',
                    'html5'    => false,
                    'format'   => 'dd.MM.yyyy',
                    'attr'     => [
                        'class'       => 'form-control datepicker',
                        'placeholder' => $this->translator->trans('inventory_item.form.commissioning_date_placeholder'),
                    ],
                ]
            )
            ->add(
                'responsiblePerson',
                TextType::class,
                [
                    'label'    => $this->translator->trans('inventory_item.form.responsible_person'),
                    'required' => false,
                    'attr'     => [
                        'class'       => 'form-control',
                        'placeholder' => $this->translator->trans('inventory_item.form.responsible_person_placeholder'),
                    ],
                ]
            )
            ->add(
                'location',
                EntityType::class,
                [
                    'label'        => $this->translator->trans('inventory_item.form.location'),
                    'class'        => Location::class,
                    'choice_label' => 'name',
                    'required'     => false,
                    'placeholder'  => $this->translator->trans('inventory_item.form.location_placeholder'),
                    'attr'         => ['class' => 'form-select'],
                ]
            )
        ;
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
                        new FormError($this->translator->trans('validation.inventory_number_required'))
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
                'data_class' => InventoryItem::class,
                'specs_url'  => null,
                'empty_data' => static function () {
                    $item = new InventoryItem();
                    $item->setCategory(InventoryCategory::OTHER);
                    $item->setBalanceType(BalanceType::ON_BALANCE);
                    $item->setStatus(ItemStatus::NEW);
                    $item->setType(ItemType::FIXED_ASSET);

                    return $item;
                },
            ]
        );

        $resolver->setAllowedTypes('specs_url', ['null', 'string']);
    }// end configureOptions()
}// end class
