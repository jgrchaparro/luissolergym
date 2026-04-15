<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class UserRecoveryPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('plainPassword',
            RepeatedType::class,
            array(
                'type' => PasswordType::class,
                'options' => array('translation_domain' => 'NucleosUserBundle'),
                'first_options' => array(
                    'label' => 'Nueva contraseña',
                    'attr' => array(
                        'placeholder' => 'form.password',
                        'title' => 'La contraseña debe contener al menos una mayúscula, una minúscula, un número y un carácter especial',
                    ),
                    'constraints' => [
                        new Length([
                            'min' => 8,
                            'max' => 30,
                            'minMessage' => 'La contraseña debe tener al menos 8 caracteres',
                            'maxMessage' => 'La contraseña no puede exceder los 30 caracteres'
                        ]),
                        new Regex([
                            'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`]{8,}$/',
                            'message' => 'La contraseña debe contener al menos una mayúscula, una minúscula, un número y un carácter especial'
                        ])
                    ],
                ),
                'second_options' => array(
                    'label' => 'form.password_confirmation',
                    'attr' => array(
                        'placeholder' => 'form.password_confirmation'
                    )
                ),
                'invalid_message' => 'Las contraseñas no coinciden',
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }

    public function getName()
    {
        return 'app_user_recovery_type';
    }
}