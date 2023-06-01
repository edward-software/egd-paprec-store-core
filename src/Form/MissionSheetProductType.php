<?php

namespace App\Form;

use App\Entity\BillingUnit;
use App\Entity\MissionSheetProduct;
use App\Entity\Product;
use App\Entity\Range;
use App\Entity\Setting;
use App\Repository\BillingUnitRepository;
use App\Repository\RangeRepository;
use App\Repository\SettingRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class MissionSheetProductType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('capacity')
            ->add('capacityUnit')
            ->add('dimensions', TextareaType::class)
            ->add('wasteClassification', TextType::class)
            ->add('code', TextType::class)
            ->add('comment', TextareaType::class)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => MissionSheetProduct::class
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'paprec_catalogbundle_mission_sheet_product';
    }


}
