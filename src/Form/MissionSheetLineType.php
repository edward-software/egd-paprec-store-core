<?php

namespace App\Form;

use App\Entity\Agency;
use App\Entity\MissionSheetLine;
use App\Entity\MissionSheetProduct;
use App\Repository\AgencyRepository;
use App\Repository\MissionSheetProductRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MissionSheetLineType extends AbstractType
{


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('quantity')
            ->add('missionSheetProduct', EntityType::class, array(
                'class' => MissionSheetProduct::class,
                'query_builder' => function (MissionSheetProductRepository $er) {
                    return $er->createQueryBuilder('mSP')
                        ->select(array('mSP', 'mSPL'))
                        ->leftJoin('mSP.missionSheetProductLabels', 'mSPL')
                        ->where('mSP.deleted IS NULL')
                        ->andWhere('mSPL.language = :language')
                        ->setParameter('language', 'FR');
                },
                'choice_label' => 'missionSheetProductLabels[0].name',
                'placeholder' => '',
                'empty_data' => null,
            ))
            ->add('agency', EntityType::class, array(
                'class' => Agency::class,
                'multiple' => false,
                'expanded' => false,
                'placeholder' => '',
                'empty_data' => null,
                'choice_label' => function (Agency $agency) {
                    return $agency->getName();
                },
                'required' => false,
                'query_builder' => function (AgencyRepository $ar) {
                    return $ar->createQueryBuilder('a')
                        ->where('a.deleted IS NULL');
                }
            ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => MissionSheetLine::class,
        ));
    }
}
