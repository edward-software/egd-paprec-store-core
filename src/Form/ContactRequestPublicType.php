<?php

namespace App\Form;

use App\Entity\QuoteRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\DataTransformer\PostalCodeToStringTransformer;

class ContactRequestPublicType extends AbstractType
{

    private $transformer;

    /**
     * QuoteRequestPublicType constructor.
     * @param $transformer
     */
    public function __construct(PostalCodeToStringTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('interest', ChoiceType::class, [
                'choices' => array(
                    'Offre packagée' => 'COLLECTE_BUREAU',
                    'Offre sur mesure' => 'COLLECTE_IMMEUBLE',
                    'Réseaux d\'agences' => 'RESEAUX_AGENCES',
                    'Personnel sur site' => 'GRANDS_ENSEMBLES',
                    'Destruction sécurisée' => 'DESTRUCTION_SECURISEE',
                    'Animation & sensibilisation' => 'ANIMATION_SENSIBILISATION',
                    'Audit & caractérisation' => 'AUDIT_CARACTERISATION'
                ),
                'data' => $options['interest'],
                'mapped' => false
            ])
            ->add('businessName')
            ->add('lastName', TextType::class)
            ->add('firstName', TextType::class)
            ->add('email', TextType::class)
            ->add('phone', TelType::class, array(
                'invalid_message' => 'Public.Contact.PhoneError',
            ))
            ->add('comment', TextareaType::class, [
                'required' => false
            ])
            ->add('postalCode', TextType::class, array(
                'invalid_message' => 'Public.Contact.PostalCodeError'
            ))
            ->add('recallDate', DateType::class, array(
                'widget' => 'single_text',
                'mapped' => false
            ))
            ->add('recallHour', TimeType::class, array(
                'widget' => 'single_text',
                'mapped' => false,
                'input' => 'string'
            ));

        $builder->get('postalCode')
            ->addModelTransformer($this->transformer);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => QuoteRequest::class,
            'validation_groups' => function (FormInterface $form) {
                return;
            },
            'locale' => null,
            'interest' => null
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'paprec_catalogbundle_quote_request_public';
    }


}
