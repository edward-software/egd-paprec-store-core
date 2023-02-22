<?php

namespace App\Form;

use App\Entity\FollowUp;
use App\Entity\MissionSheet;
use App\Entity\QuoteRequest;
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
                    'No' => 0,
                    'Yes' => 1
                ),
                "expanded" => true,
                "required" => true,
                'empty_data' => '0'
            ))
            ->add('wasteTrackingRegisterAccess', ChoiceType::class, array(
                "choices" => array(
                    'No' => 0,
                    'Yes' => 1
                ),
                "expanded" => true,
                "required" => true,
                'empty_data' => '0'
            ))
            ->add('reportingAccess', ChoiceType::class, array(
                "choices" => array(
                    'No' => 0,
                    'Yes' => 1
                ),
                "expanded" => true,
                "required" => true,
                'empty_data' => '0'
            ))
            ->add('contractType', ChoiceType::class, array(
                'choices' => array(
                    'Creation' => 'creation',
                    'Modification' => 'modification'
                ),
                'empty_data' => 'creation',
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'Commercial.MissionSheet.ContractType.' . ucfirst($choiceValue);
                }
            ))->add('billingType', ChoiceType::class, array(
                'choices' => array(
                    'Global' => 'global',
                    'Per_site' => 'per_site'
                ),
                'empty_data' => 'global',
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'Commercial.MissionSheet.BillingType.' . ucfirst($choiceValue);
                }
            ))
            ->add('comment', TextareaType::class)
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
