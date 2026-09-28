<?php
// src/Form/DosageProfileType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DosageProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dose', NumberType::class, [
                'attr' => ['min' => 0],
                'label' => 'Dose',
            ])
            ->add('unit', ChoiceType::class, [
                'choices' => [
                    'mg' => 'mg',
                    'g' => 'g',
                    'µg' => 'µg',
                    'UI' => 'UI',
                ],
                'label' => 'Unité',
            ])
            ->add('morning', IntegerType::class, [
                'property_path' => '[moments][morning]',
                'attr' => ['min' => 0],
                'label' => 'Matin',
            ])
            ->add('noon', IntegerType::class, [
                'property_path' => '[moments][noon]',
                'attr' => ['min' => 0],
                'label' => 'Midi',
            ])
            ->add('evening', IntegerType::class, [
                'property_path' => '[moments][evening]',
                'attr' => ['min' => 0],
                'label' => 'Soir',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null, // trabaja con arrays
        ]);
    }
}