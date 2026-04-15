<?php

namespace App\Form\Type;

use App\Document\User;
use App\Document\UserQuestions;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Bundle\SecurityBundle\Security;

class UserType extends AbstractType
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options):void
    {
        $isEdit = $options['data'] && $options['data']->getId();

        $builder
            ->add('nombre', TextType::class, [
                'label' => 'Nombre y Apellido',
            ])
            ->add('username', TextType::class, [
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ingrese nombre de usuario',
                ],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[a-zA-Z0-9_-]+$/',
                        'message' => 'El nombre de usuario solo puede contener letras, números, guiones y guiones bajos'
                    ]),
                ],
            ])
            ->add('cedula', TextType::class, [
                'label' => 'Cédula',
                'attr' => [
                    'placeholder' => 'Ingrese el número de cédula',
                ],
            ])
            ->add('telefono', TextType::class, [
                'label' => 'Teléfono',
                'attr' => [
                    'placeholder' => 'Ingrese el número de teléfono',
                ],
            ])
            ->add('unidad', TextType::class, [
                'label' => 'Unidad de Adscripción',
                'attr' => [
                    'placeholder' => 'Ingrese la unidad',
                ],
            ])
            ->add('email', EmailType::class, [
                'required' => true,  // ⭐ ASEGURAR QUE SEA REQUERIDO
                'disabled' => !$this->security->isGranted('ROLE_PERFIL_EDITAR_EMAIL'),
                'attr' => [
                    'placeholder' => 'Ingrese correo electrónico',
                ],
                'constraints' => [
                    new Email([
                        'message' => 'Por favor ingrese un correo electrónico válido',
                    ])
                ],
                'empty_data' => '',  // ⭐ VALOR POR DEFECTO SI ESTÁ VACÍO
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => !$isEdit,
                'mapped' => false,
                'options' => ['translation_domain' => 'NucleosUserBundle'],
                'first_options' => [
                    'label' => 'form.password',
                    'attr' => [
                        'placeholder' => $isEdit ? 'Dejar vacío para mantener contraseña actual' : 'form.password'
                    ]
                ],
                'second_options' => [
                    'label' => 'form.password_confirmation',
                    'attr' => [
                        'placeholder' => $isEdit ? 'Confirmar nueva contraseña' : 'form.password_confirmation'
                    ]
                ],
                'invalid_message' => 'Las contraseñas no coinciden',
            ])
            ->add('questions', DocumentType::class, [

                'class' => UserQuestions::class,
                'choice_label' => 'description',
                'multiple' => false,
                'expanded' => false,
                'mapped' => false,
                'label' => 'Pregunta de Seguridad',
                'placeholder' => 'Seleccione una pregunta para el usuario',

            ])
            ->add('answer', TextType::class, [
                'mapped' => false,
                'label' => 'Respuesta a la Pregunta de Seguridad',
                'attr' => [

                    'placeholder' => 'Ingrese la respuesta a la pregunta de seguridad',
                ]
            ]);

        if ($isEdit) {
            $builder
                ->add('answer', HiddenType::class, [
                    'mapped' => false,
                ])->add('questions', HiddenType::class, [
                    'mapped' => false,
                ]);
        } else {
            $builder->add('questions', DocumentType::class, [
                'class' => UserQuestions::class,
                'choice_label' => 'description',
                'multiple' => false,
                'expanded' => false,
                'mapped' => false,
                'label' => 'Pregunta de Seguridad',
                'placeholder' => 'Seleccione una pregunta para el usuario',
            ])
                ->add('answer', TextType::class, [
                    'mapped' => false,
                    'label' => 'Respuesta a la Pregunta de Seguridad',
                    'attr' => [
                        'placeholder' => 'Ingrese la respuesta a la pregunta de seguridad',
                    ]
                ]);
        }

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($isEdit) {
            $user = $event->getData();
            $form = $event->getForm();
            $plainPassword = $form->get('plainPassword')->getData();

            if (!empty($plainPassword)) {
                $user->setPlainPassword($plainPassword);
            } elseif (!$isEdit) {
                $user->setPlainPassword(null);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }

    public function getName()
    {
        return 'app_user_type';
    }
}
