<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\QuoteRequestLine;
use App\Repository\ProductRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteRequestLineAddType extends AbstractType
{


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('quantity')
            ->add('product', EntityType::class, array(
                'class' => Product::class,
                'query_builder' => function (ProductRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->select(array('p', 'pL'))
                        ->leftJoin('p.productLabels', 'pL')
                        ->where('p.deleted IS NULL')
                        ->andWhere('pL.language = :language')
                        ->orderBy('p.position', 'ASC')
                        ->setParameter('language', 'FR');
                },
                'choice_label' => 'productLabels[0].name',
                'placeholder' => '',
                'empty_data' => null,
            ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => QuoteRequestLine::class,
        ));
    }
}
