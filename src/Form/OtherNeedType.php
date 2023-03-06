<?php

namespace App\Form;

use App\Entity\OtherNeed;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OtherNeedType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
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
            ->add('name')
            ->add('catalog', ChoiceType::class, array(
                'choices' => array(
                    'Regular' => 'regular',
                    'Ponctual' => 'ponctual',
                    'Material' => 'material',
                ),
                'empty_data' => 'ponctual',
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'Catalog.OtherNeed.Catalog.' . ucfirst($choiceValue);
                },
                'required' => true,
                'expanded' => true
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
            'data_class' => OtherNeed::class,
            'languages' => null,
            'language' => null
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'paprec_catalogbundle_other_need';
    }
}
