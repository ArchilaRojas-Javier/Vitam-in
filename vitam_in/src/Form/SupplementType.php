<?php

namespace App\Form;

use App\Entity\Benefit;
use App\Entity\Supplement;
use App\Entity\SupplementType as SupplementTypeEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\DosageProfileType;

class SupplementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', TextareaType::class, [
                'required' => true,
            ])
            ->add('on_duration_days', IntegerType::class, [
                'required' => true,
            ])
            ->add('off_duration_days', IntegerType::class, [
                'required' => true,
            ])
            ->add('precautions', TextareaType::class, [
                'required' => true,
            ])
           ->add('male', DosageProfileType::class, [
                'mapped' => false,
                'required' => true,

                'label' => 'Homme',

            ])
            ->add('female', DosageProfileType::class, [
                'mapped' => false,
                'required' => true,
                'label' => 'Femme',
            ])
            ->add('general', DosageProfileType::class, [
                'mapped' => false,
                'required' => true, 
                'label' => 'Général',
            ])
            ->add('supplementbenefit', EntityType::class, [
                'class' => Benefit::class,
                'choice_label' => 'name', 
                'multiple' => true,
                'expanded' => true, 
                'required' => true,
            ])
            ->add('supplementtype', EntityType::class, [
                'class' => SupplementTypeEntity::class, 
                'choice_label' => 'name',
                'placeholder' => 'Choisir un type',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Supplement::class,
        ]);
    }
}