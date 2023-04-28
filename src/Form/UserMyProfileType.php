<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\Agency;
use App\Entity\User;
use App\Repository\AgencyRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserMyProfileType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) : void
    {
        $builder
            ->add('username', TextType::class, [
                    'required' => true]
            )
            ->add('companyName', TextType::class)
            ->add('civility', ChoiceType::class, array(
                'choices' => array(
                    'M',
                    'MME'
                ),
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'General.' . $choiceValue;
                },
                'data' => $options['civility'],
                'expanded' => true,
                'required' => true
            ))
            ->add('lastName', TextType::class)
            ->add('firstName', TextType::class)
            ->add('nickname', TextType::class)
            ->add('email', TextType::class, [
                'required' => true
            ])
            ->add('lang', ChoiceType::class, [
                'choices' => $options['languages']
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => false,
                'first_options'  => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat Password'],
            ])
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
            ))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) : void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'civility' => null,
            'languages' => null
        ]);
    }
}
