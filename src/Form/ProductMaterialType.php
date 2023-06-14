<?php

namespace App\Form;

use App\Entity\BillingUnit;
use App\Entity\Product;
use App\Entity\Range;
use App\Entity\Setting;
use App\Repository\BillingUnitRepository;
use App\Repository\RangeRepository;
use App\Repository\SettingRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProductMaterialType extends AbstractType
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
            ->add('isEnabled', ChoiceType::class, array(
                "choices" => array(
                    0,
                    1
                ),
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'General.' . $choiceValue;
                },
                "expanded" => true,
            ))
            ->add('hideFrequency', ChoiceType::class, array(
                "choices" => array(
                    0,
                    1
                ),
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'General.' . $choiceValue;
                },
                "expanded" => true,
            ))
            ->add('hideCapacity', ChoiceType::class, array(
                "choices" => array(
                    0,
                    1
                ),
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'General.' . $choiceValue;
                },
                "expanded" => true,
            ))
            ->add('hideDimension', ChoiceType::class, array(
                "choices" => array(
                    0,
                    1
                ),
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'General.' . $choiceValue;
                },
                "expanded" => true,
            ))
            ->add('position')
            ->add('range', EntityType::class, array(
                'class' => Range::class,
                'query_builder' => function (RangeRepository $er) {
                    return $er->createQueryBuilder('r')
                        ->select(array('r', 'rL'))
                        ->leftJoin('r.rangeLabels', 'rL')
                        ->where('r.deleted IS NULL')
                        ->andWhere('rL.language = :language')
                        ->orderBy('r.position', 'ASC')
                        ->setParameter('language', 'FR')
                        ->andWhere('r.catalog = :catalog')
                        ->setParameter('catalog', 'MATERIAL');
                },
                'choice_label' => 'rangeLabels[0].name',
                'placeholder' => '',
                'empty_data' => null,
            ))
            ->add('billingUnit', EntityType::class, array(
                'class' => BillingUnit::class,
                'query_builder' => function (BillingUnitRepository $er) {
                    return $er->createQueryBuilder('bU')
                        ->select(array('bU'))
                        ->where('bU.deleted IS NULL');
                },
                'choice_label' => function ($billingUnit) {
                    return $billingUnit->getCode() . ' - ' .$billingUnit->getName();
                },
                'placeholder' => '',
                'empty_data' => null,
            ))
            ->add('wasteClassification', TextType::class)
            ->add('code', TextType::class)
            ->add('materialUnitPrice', TextType::class)
            ->add('mercurial', EntityType::class, array(
                'class' => Setting::class,
                'query_builder' => function (SettingRepository $er) {
                    return $er->createQueryBuilder('m')
                        ->select(array('m'))
                        ->where('m.deleted IS NULL')
                        ->andWhere('m.keyName = :keyName')
                        ->setParameter('keyName', 'PRODUCT_MERCURIAL');
                },
                'choice_label' => 'value',
                'placeholder' => '',
                'empty_data' => null
            ))
            ->add('comment', TextareaType::class)
            ->add('referenceDate', TextType::class, [
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Product::class,
            'transportTypes' => null,
            'calculationFormulas' => null,
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
