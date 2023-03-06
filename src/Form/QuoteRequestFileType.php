<?php

namespace App\Form;

use App\Entity\Picture;
use App\Entity\QuoteRequestFile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteRequestFileType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('systemPath', FileType::class, array(
                'data_class' => null
            ))
            ->add('type', ChoiceType::class, array(
                'choices' => array(
                    'CONTRACT' => 'CONTRACT',
                    'OTHER' => 'OTHER',
                ),
                "choice_label" => function ($choiceValue, $key, $value) {
                    $choiceValue = strtolower($choiceValue);
                    return 'Commercial.QuoteRequestFile.Type.' . $choiceValue;
                },
                'data' => 'OTHER',
                'required' => true,
                'expanded' => true
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => QuoteRequestFile::class
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'paprec_catalogbundle_file';
    }


}
