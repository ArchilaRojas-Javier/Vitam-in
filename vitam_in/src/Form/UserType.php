<?php
// src/Form/UserType.php

namespace App\Form;

use App\Entity\User;
use App\Enum\Sex;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Votre prénom',
                    'autocomplete' => 'given-name',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Votre nom',
                    'autocomplete' => 'family-name',
                ],
            ])
            ->add('sex', EnumType::class, [
                'class' => Sex::class,
                'label' => 'Sexe',
                'required' => false,
                'placeholder' => 'Sélectionnez...',
                'choice_label' => fn (?Sex $choice) => $choice
                    ? ucfirst($choice->value)
                    : '',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'disabled' => true,   // ← solo lectura, no editable
                'attr' => [
                    'readonly' => true,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}