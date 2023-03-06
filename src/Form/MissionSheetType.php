<?php

namespace App\Form;

use App\Entity\FollowUp;
use App\Entity\MissionSheet;
use App\Entity\QuoteRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MissionSheetType extends AbstractType
{
    protected $options;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $this->options = $options;

        $builder
            ->add('mnemonicNumber', TextType::class)
            ->add('orderNumber', TextType::class)
            ->add('contractNumber', TextType::class)
            ->add('myPaprecAccess', ChoiceType::class, array(
                "choices" => array(
                    'NO' => 0,
                    'YES' => 1
                ),
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'General.' . $choiceValue;
                },
                "expanded" => true,
                "required" => true,
                'empty_data' => '0'
            ))
            ->add('wasteTrackingRegisterAccess', ChoiceType::class, array(
                "choices" => array(
                    'NO' => 0,
                    'YES' => 1
                ),
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'General.' . $choiceValue;
                },
                "expanded" => true,
                "required" => true,
                'empty_data' => '0'
            ))
            ->add('reportingAccess', ChoiceType::class, array(
                "choices" => array(
                    'NO' => 0,
                    'YES' => 1
                ),
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'General.' . $choiceValue;
                },
                "expanded" => true,
                "required" => true,
                'empty_data' => '0'
            ))
            ->add('contractType', ChoiceType::class, array(
                'choices' => array(
                    'CREATION' => 'CREATION',
                    'MODIFICATION' => 'MODIFICATION'
                ),
                'empty_data' => 'CREATION',
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'Commercial.MissionSheet.ContractType.' . ucfirst($choiceValue);
                }
            ))->add('billingType', ChoiceType::class, array(
                'choices' => array(
                    'GLOBAL' => 'GLOBAL',
                    'PER_SITE' => 'PER_SITE'
                ),
                'empty_data' => 'GLOBAL',
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'Commercial.MissionSheet.BillingType.' . ucfirst($choiceValue);
                }
            ))
            ->add('comment', TextareaType::class)
            ->add('secondUserInCharge', EntityType::class, array(
                'class' => User::class,
                'multiple' => false,
                'expanded' => false,
                'placeholder' => '',
                'empty_data' => null,
                'choice_label' => function (User $user) {
                    return $user->getFirstName() . ' ' . $user->getLastName();
                },
                'required' => false,
                'query_builder' => function (UserRepository $ur) {
                    return $ur->createQueryBuilder('u')
                        ->where('u.deleted IS NULL')
                        ->andWhere('u.roles LIKE \'%ROLE_COMMERCIAL%\'')
                        ->andWhere('u.enabled = 1')
                        ->orderBy('u.firstName');
                }
            ))
        ;

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => MissionSheet::class
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'paprec_catalogbundle_missionSheet';
    }


}
