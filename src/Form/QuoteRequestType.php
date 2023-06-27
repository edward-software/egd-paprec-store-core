<?php

namespace App\Form;

use App\Entity\QuoteRequest;
use App\Entity\User;
use App\Form\DataTransformer\PostalCodeToStringTransformer;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuoteRequestType extends AbstractType
{

    private $transformer;
    private $translator;

    /**
     * QuoteRequestPublicType constructor.
     * @param $transformer
     */
    public function __construct(PostalCodeToStringTransformer $transformer, TranslatorInterface $translator)
    {
        $this->transformer = $transformer;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('locale', ChoiceType::class, array(
                'choices' => $options['locales']
            ))
            ->add('businessName')
            ->add('civility', ChoiceType::class, array(
                'choices' => array(
                    'M',
                    'MME'
                ),
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'General.' . $choiceValue;
                },
                'data' => $options['civility'],
                'expanded' => true
            ))
            ->add('access', ChoiceType::class, array(
                "choices" => $options['access'],
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'Commercial.AccessList.' . $choiceValue;
                },
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
            ))
            ->add('ponctualDate', DateType::class, array(
                'widget' => 'single_text'
            ))
//            ->add('ponctualDate', DateType::class, array(
//                'widget' => 'choice'
//            ))
            ->add('lastName', TextType::class)
            ->add('firstName', TextType::class)
            ->add('email', EmailType::class)
            ->add('phone', TextType::class)
            ->add('isMultisite', ChoiceType::class, array(
                "choices" => array(0, 1),
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'General.' . $choiceValue;
                },
                "expanded" => false,
            ))
            ->add('address', TextType::class)
            ->add('postalCode', TextType::class, array(
                'invalid_message' => 'Public.Contact.PostalCodeError',
                'required' => true
            ))
            ->add('city', HiddenType::class)
            ->add('billingAddress', TextType::class)
            ->add('billingPostalCode', TextType::class, array(
                'invalid_message' => 'Public.Contact.PostalCodeError',
                'required' => true
            ))
            ->add('billingCity', HiddenType::class)
            ->add('comment', TextareaType::class)
            ->add('quoteStatus', ChoiceType::class, array(
                "choices" => $options['status'],
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'Commercial.QuoteStatusList.' . $choiceValue;
                }
            ))
//            ->add('overallDiscount')
            ->add('salesmanComment', TextareaType::class)
            ->add('annualBudget')
            ->add('catalog', ChoiceType::class, array(
                'required' => true,
                'data' => $options['catalog'],
                'choices' => array(
                    'REGULAR' => 'REGULAR',
                    'PONCTUAL' => 'PONCTUAL',
                    'MATERIAL' => 'MATERIAL',
                ),
                "choice_label" => function ($choiceValue, $key, $value) {
                    $choiceValue = strtoupper($choiceValue);
                    return 'Commercial.QuoteRequest.Catalog.' . $choiceValue;
                },
                'expanded' => true
            ))
            ->add('reference')
            ->add('customerId')
            ->add('userInCharge', EntityType::class, array(
                'class' => User::class,
                'multiple' => false,
                'expanded' => false,
                'placeholder' => '',
                'empty_data' => null,
                'choice_label' => function (User $user) {
                    return $user->getFirstName() . ' ' . $user->getLastName();
                },
                'query_builder' => function (UserRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.deleted IS NULL')
                        ->andWhere('u.enabled = 1')
                        ->andWhere('u.roles LIKE \'%ROLE_COMMERCIAL%\'')
                        ->orderBy('u.firstName');
                }
            ))
            ->add('signatoryFirstName1')
            ->add('signatoryLastName1')
            ->add('signatoryTitle1')
            ->add('duration', ChoiceType::class, array(
                'choices' => array(
                    '12' => '12',
                    '24' => '24',
                    '36' => '36',
                    '48' => '48',
                    '60' => '60'
                ),
                'choice_label' => function ($choiceValue, $key, $value) {
                    return $choiceValue . ' ' . $this->translator->trans('Commercial.QuoteRequest.MONTH');
                },
                'expanded' => false,
                'multiple' => false
            ))
//            ->add('startDate', DateType::class, array(
//                'widget' => 'choice'
//            ))
            ->add('startDate', DateType::class, array(
                'widget' => 'single_text'
            ))
            ->add('depositDate', DateType::class, array(
                'widget' => 'single_text'
            ))
            ->add('resumptionDate', DateType::class, array(
                'widget' => 'single_text'
            ))
            ->add('serviceEndDate', DateType::class, array(
                'widget' => 'single_text'
            ))
            ->add('postalCodeString', HiddenType::class, [
                'data' => $options['postalCodeString'],
                'mapped' => false
            ])
            ->add('billingPostalCodeString', HiddenType::class, [
                'data' => $options['billingPostalCodeString'],
                'mapped' => false
            ]);
        $builder->get('postalCode')
            ->addModelTransformer($this->transformer);
//        $builder->get('billingPostalCode')
//            ->addModelTransformer($this->transformer);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => QuoteRequest::class,
            'validation_groups' => function (FormInterface $form) {
                $data = $form->getData();
                if ($data->getIsMultisite() === 1) {
                    return ['default', 'public'];
                }
                return ['default', 'public_multisite'];
            },
            'status' => null,
            'locales' => null,
            'staff' => null,
            'access' => null,
            'floorNumber' => null,
            'catalog' => null,
            'civility' => null,
            'postalCodeString' => null,
            'billingPostalCodeString' => null
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'paprec_catalogbundle_quote_request';
    }


}
