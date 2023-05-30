<?php

namespace App\Form;

use App\Entity\QuoteRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteRequestManagementFeeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('managementFeeCode1', TextType::class)
            ->add('managementFeeDescription1', TextType::class)
            ->add('managementFeeFrequency1', ChoiceType::class, array(
                'choices' => array(
                    '' => '',
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
            ))
            ->add('managementFeeAmount1', IntegerType::class)
            ->add('managementFeeCode2', TextType::class)
            ->add('managementFeeDescription2', TextType::class)
            ->add('managementFeeFrequency2', ChoiceType::class, array(
                'choices' => array(
                    '' => '',
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
            ))
            ->add('managementFeeAmount2', IntegerType::class)
            ->add('managementFeeCode3', TextType::class)
            ->add('managementFeeDescription3', TextType::class)
            ->add('managementFeeFrequency3', ChoiceType::class, array(
                'choices' => array(
                    '' => '',
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
            ))
            ->add('managementFeeAmount3', IntegerType::class)
            ->add('managementFeeCode4', TextType::class)
            ->add('managementFeeDescription4', TextType::class)
            ->add('managementFeeFrequency4', ChoiceType::class, array(
                'choices' => array(
                    '' => '',
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
            ))
            ->add('managementFeeAmount4', IntegerType::class)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => QuoteRequest::class,
            'managementFeeCode1' => null,
            'managementFeeCode2' => null,
            'managementFeeCode3' => null,
            'managementFeeCode4' => null,
            'managementFeeDescription1' => null,
            'managementFeeDescription2' => null,
            'managementFeeDescription3' => null,
            'managementFeeDescription4' => null,
            'managementFeeFrequency1' => null,
            'managementFeeFrequency2' => null,
            'managementFeeFrequency3' => null,
            'managementFeeFrequency4' => null,
            'managementFeeAmount1' => null,
            'managementFeeAmount2' => null,
            'managementFeeAmount3' => null,
            'managementFeeAmount4' => null,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'paprec_catalogbundle_product';
    }


}
