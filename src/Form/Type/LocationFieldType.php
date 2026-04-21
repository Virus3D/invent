<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Location;
use App\Repository\LocationRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class LocationFieldType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'class'         => Location::class,
                'choice_label'  => static fn (Location $l) => $l->getName() . ' (' . $l->getRoomNumber() . ')',
                'query_builder' => static fn (LocationRepository $er) => $er->createQueryBuilder('l')
                    ->orderBy('l.roomNumber', 'ASC')
                    ->addOrderBy('l.name', 'ASC'),
                'autocomplete'  => true,
            ]
        );
    }// end configureOptions()

    /**
     * {@inheritDoc}
     */
    public function getParent(): string
    {
        return EntityType::class;
    }// end getParent()
}// end class
