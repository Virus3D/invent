<?php

declare(strict_types=1);

namespace App\Form;

use App\Enum\InventoryCategory;
use App\Trait\SpecificationTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InventorySpecificationsType extends AbstractType
{
    use SpecificationTrait;

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $category = $options['category'] ?? null;
        $specifications = $options['specifications'] ?? [];

        if ($category instanceof InventoryCategory && $category->hasSpecifications()) {
            $allowedSpecs = $category->getAllowedSpecifications();
            $labels = $this->getSpecificationLabels($category);
            $requiredSpecs = $category->getRequiredSpecifications();

            foreach ($allowedSpecs as $specKey) {
                $label = $labels[$specKey] ?? ucfirst(str_replace('_', ' ', $specKey));
                $isRequired = in_array($specKey, $requiredSpecs, true);

                $builder->add(
                    $specKey,
                    TextType::class,
                    [
                        'label'    => $label,
                        'required' => $isRequired,
                        'data'     => $specifications[$specKey] ?? null,
                        'attr'     => [
                            'class'         => 'form-control spec-input',
                            'placeholder'   => 'Введите ' . strtolower($label),
                            'data-spec-key' => $specKey,
                        ],
                    ]
                );
            }
        }// end if
    }// end buildForm()

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class'     => null,
                'category'       => null,
                'specifications' => [],
                'mapped'         => false,
            ]
        );
    }// end configureOptions()
}// end class
