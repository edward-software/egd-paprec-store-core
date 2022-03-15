<?php

namespace App\Form;

use App\Entity\Agency;
use App\Entity\PostalCode;
use App\Repository\AgencyRepository;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostalCodeType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', TextType::class, array(
                "required" => true
            ))
            ->add('city', TextType::class, array(
                "required" => true
            ))
            ->add('rentalRate', TextType::class, array(
                "required" => true
            ))
            ->add('bomTransportRate', TextType::class, array(
                "required" => true
            ))
            ->add('treatmentRate', TextType::class, array(
                "required" => true
            ))
            ->add('traceabilityRate', TextType::class, array(
                "required" => true
            ))
            ->add('cbrRegTransportRate', TextType::class, array(
                "required" => true
            ))
            ->add('cbrPonctTransportRate', TextType::class, array(
                "required" => true
            ))
            ->add('vlPlCfsRegTransportRate', TextType::class, array(
                "required" => true
            ))
            ->add('vlPlCfsPonctTransportRate', TextType::class, array(
                "required" => true
            ))
            ->add('vlPlTransportRate', TextType::class, array(
                "required" => true
            ))
            ->add('plPonctTransportRate', TextType::class, array(
                "required" => true
            ))
//            ->add('cBroyeurTransportRate', TextType::class, array(
//                "required" => true
//            ))
//            ->add('fourgonPLTransportRate', TextType::class, array(
//                "required" => true
//            ))
//            ->add('fourgonVLTransportRate', TextType::class, array(
//                "required" => true
//            ))
//            ->add('amplirollTransportRate', TextType::class, array(
//                "required" => true
//            ))
//            ->add('livraisonTransportRate', TextType::class, array(
//                "required" => true
//            ))

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
                        ->where('a.deleted IS NULL')
                        ->orderBy('a.name');
                }
            ))
            ->add('userInCharge', EntityType::class, array(
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
            ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => PostalCode::class,
        ));
    }
}
