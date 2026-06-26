<?php

namespace App\Form\Type;

use App\Document\Settings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class SettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email_soporte', EmailType::class, [
                'label' => 'Email de Soporte',
                'required' => false,
                'constraints' => [
                    new Email(['message' => 'Ingrese un email válido']),
                ],
                'attr' => ['placeholder' => 'soporte@ejemplo.com'],
            ])
            ->add('whatsapp', TextType::class, [
                'label' => 'WhatsApp',
                'required' => false,
                'attr' => ['placeholder' => '+58 412 1234567'],
            ])
            ->add('instagram', TextType::class, [
                'label' => 'Instagram',
                'required' => false,
                'attr' => ['placeholder' => '@usuario'],
            ])
            ->add('facebook', TextType::class, [
                'label' => 'Facebook',
                'required' => false,
                'attr' => ['placeholder' => 'facebook.com/pagina'],
            ])
            ->add('monto_usd_mes', NumberType::class, [
                'label' => 'Monto USD Mensual',
                'required' => false,
                'scale' => 2,
                'constraints' => [
                    new GreaterThanOrEqual(['value' => 0, 'message' => 'El monto debe ser mayor o igual a 0']),
                ],
                'attr' => ['placeholder' => '0.00'],
            ])
            ->add('dias_mes', IntegerType::class, [
                'label' => 'Días por Mes (vigencia)',
                'required' => false,
                'constraints' => [
                    new GreaterThanOrEqual(['value' => 1, 'message' => 'Debe ser al menos 1 día']),
                ],
                'attr' => ['placeholder' => '30', 'min' => '1'],
            ])
            ->add('monto_usd_dia', NumberType::class, [
                'label' => 'Monto USD Día',
                'required' => false,
                'scale' => 2,
                'constraints' => [
                    new GreaterThanOrEqual(['value' => 0, 'message' => 'El monto debe ser mayor o igual a 0']),
                ],
                'attr' => ['placeholder' => '0.00'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Settings::class,
            'label' => false,
        ]);
    }
}
