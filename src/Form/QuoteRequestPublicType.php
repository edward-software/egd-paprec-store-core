<?php

namespace App\Form;

use App\Entity\QuoteRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\DataTransformer\PostalCodeToStringTransformer;

class QuoteRequestPublicType extends AbstractType
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
            ->add('businessName')
            ->add('civility', ChoiceType::class, array(
                'choices' => array(
                    'M',
                    'Mme'
                ),
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'General.' . $choiceValue;
                },
                'data' => 'M',
                'expanded' => true
            ))
            ->add('access', ChoiceType::class, array(
                "choices" => $options['access'],
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'Commercial.AccessList.' . $choiceValue;
                },
                'data' => 'ground',
                'required' => true
            ))
            ->add('floorNumber', ChoiceType::class, array(
                "choices" => $options['floorNumber'],
                'data' => '0',
                'required' => false
            ))
            ->add('staff', ChoiceType::class, array(
                "choices" => $options['staff'],
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'Commercial.StaffList.' . $choiceValue;
                },
                'data' => '120',
                'required' => true
            ))

            ->add('lastName', TextType::class)
            ->add('firstName', TextType::class)
            ->add('email', TextType::class)
            ->add('phone', TelType::class, array(
                'invalid_message' => 'Public.Contact.PhoneError',
            ))
            ->add('isMultisite', ChoiceType::class, array(
                "choices" => array(0, 1),
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'General.' . $choiceValue;
                },
                "data" => 0,
                "expanded" => true,
            ))
            ->add('isSameSignatory', ChoiceType::class, array(
                "choices" => array(0, 1),
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'General.' . $choiceValue;
                },
                "data" => 0,
                "expanded" => true,
            ))
            ->add('address', TextType::class)
            ->add('postalCode', TextType::class, array(
                'invalid_message' => 'Public.Contact.PostalCodeError'
            ))
            ->add('city', TextType::class)
            ->add('comment', TextareaType::class)
        ->add('signatoryFirstName1', TextType::class)
        ->add('signatoryLastName1', TextType::class)
        ->add('signatoryTitle1', TextType::class);

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
                $groups = ['public'];
                $data = $form->getData();
                if ($data->getIsMultisite() === 0) {
                    $groups[] = 'public_multisite';
                }
                if ($data->getIsSameSignatory() === 0) {
                    $groups[] = 'public_same_signatory';
                }
                return $groups;
            },
            'access' => null,
            'staff' => null,
            'destructionType' => null,
            'floorNumber' => null,
            'locale' => null
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
