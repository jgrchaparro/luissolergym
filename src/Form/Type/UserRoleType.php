<?php

namespace App\Form\Type;

use App\Document\UserRole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserRoleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'role',
                TextType::class,
                array(
                    'label' => "Permiso"
                )
            )->add(
                'descripcion',
                TextType::class,
                array(
                    'label' => "Descripción"
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => UserRole::class,
                'label' => false
            )
        );
    }
}