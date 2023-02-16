<?php

namespace App\Form;

use App\Entity\FollowUp;
use App\Entity\Product;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FollowUpType extends AbstractType
{
    protected $options;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $this->options = $options;

        $builder
            ->add('date', DateType::class, array(
                'widget' => 'single_text'
            ))
            ->add('status', ChoiceType::class, array(
                'choices' => array(
                    'Pending' => 'pending'
                ),
                'empty_data' => 'pending',
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'Catalog.FollowUp.Status.' . ucfirst($choiceValue);
                },
                'required' => true,
                'expanded' => true
            ))
            ->add('content', TextareaType::class)
        ;

        if (!$this->options['productId']) {
            $builder->add('product', EntityType::class, array(
                    'class' => Product::class,
                    'query_builder' => function (EntityRepository $er) {

                        $qb = $er->createQueryBuilder('p')
                            ->select('p')
                            ->where('p.deleted is NULL');

                        if ($this->options['productId']) {
                            $qb
                                ->andWhere('p.id = :productId')
                                ->setParameter('productId', $this->options['productId']);
                        }

                        return $qb;
                    },
                    'choice_label' => 'code',
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => FollowUp::class,
            'productId' => null
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'paprec_catalogbundle_followUp';
    }


}
