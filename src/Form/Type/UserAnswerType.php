<?php

namespace App\Form\Type;

use App\Document\UserQuestions;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserAnswerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'question',
                DocumentType::class,
                [
                    'label' => false,
                    'class' => UserQuestions::class,
                    'placeholder' => 'Seleccione una pregunta',
                    'required' => true,
                    'choice_label' => function ($question) {
                        return $question->getDescription();
                    },
                ]
            )
            ->add(
                'answer',
                TextType::class,
                [
                    'label' => false,
                    'required' => true
                ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }
}