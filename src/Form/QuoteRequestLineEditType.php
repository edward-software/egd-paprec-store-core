<?php

namespace App\Form;

use App\Entity\QuoteRequestLine;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteRequestLineEditType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('quantity', IntegerType::class, array(
                "required" => true
            ))
            ->add('editableRentalUnitPrice', TextType::class)
            ->add('editableTransportUnitPrice', TextType::class)
            ->add('editableTraceabilityUnitPrice', TextType::class)
            ->add('editableTreatmentUnitPrice', TextType::class)
            ->add('frequency', ChoiceType::class, array(
                'choices' => array(
                    'REGULAR' => 'REGULAR',
                    'PONCTUAL' => 'PONCTUAL',
                    'MATERIAL' => 'MATERIAL'
                ),
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'Commercial.QuoteRequest.' . $choiceValue;
                },
                'expanded' => true
            ))
            ->add('frequencyTimes', ChoiceType::class, array(
                'choices' => array(
                    '0' => '0',
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '8',
                    '9' => '9',
                    '10' => '10',
                    '11' => '11',
                    '12' => '12'
                ),
                'expanded' => false,
                'multiple' => false
            ))
            ->add('frequencyInterval', ChoiceType::class, array(
                'choices' => array(
                    'WEEK' => 'WEEK',
                    'MONTH' => 'MONTH',
                    'BIMESTRE' => 'BIMESTRE',
                    'QUARTER' => 'QUARTER'
                ),
                "choice_label" => function ($choiceValue, $key, $value) {
                    return ($choiceValue) ? 'Public.Catalog.' . $choiceValue : '';
                },
                'expanded' => false,
                'multiple' => false
            ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => QuoteRequestLine::class
        ));
    }
}
