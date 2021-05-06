<?php

namespace App\Form;

use App\Entity\CustomArea;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomAreaType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('leftContent', CKEditorType::class, array(
                'config_name' => 'custom_config',
                'config' => array(
                    'language' => $options['language']
                ),
                'required' => true
            ))
            ->add('rightContent', CKEditorType::class, array(
                'config_name' => 'custom_config',
                'config' => array(
                    'language' => $options['language']
                ),
                'required' => true
            ))
            ->add('isDisplayed', ChoiceType::class, array(
                "choices" => array(
                    0,
                    1
                ),
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'General.' . $choiceValue;
                },
                'data' => '1',
                "expanded" => true,
            ))
            ->add('code', ChoiceType::class, array(
                "choices" => $options['codes'],
                "multiple" => false,
                "expanded" => false,
            ))
            ->add('language', ChoiceType::class, array(
                'choices' => $options['languages'],
                'data' => $options['language']
            ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => CustomArea::class,
            'codes' => null,
            'languages' => null,
            'language' => null
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'paprec_catalogbundle_customarea';
    }
}
