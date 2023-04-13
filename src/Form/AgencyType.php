<?php

namespace App\Form;

use App\Entity\Agency;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AgencyType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('destinationEmailMission', TextType::class)
            ->add('legalInfoTemplate', TextareaType::class)
            ->add('entityName', TextType::class)
            ->add('phoneNumber', TextType::class)
            ->add('faxNumber', TextType::class)
            ->add('name', TextType::class, array(
                "required" => true
            ))
            ->add('businessName', TextType::class, array(
                "required" => true
            ))
            ->add('businessId', TextType::class, array(
                "required" => true
            ))
            ->add('address', TextType::class, array(
                "required" => true
            ))
            ->add('city', TextType::class, array(
                "required" => true
            ))
            ->add('postalCode', TextType::class, array(
                "required" => true
            ))
            ->add('template', ChoiceType::class, array(
                "choices" => array_flip($options['templates']),
                "multiple" => false,
                "expanded" => false,
            ))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Agency::class,
            'templates' => null
        ));
    }
}
