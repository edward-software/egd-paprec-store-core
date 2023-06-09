<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\QuoteRequestLine;
use App\Repository\ProductRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuoteRequestLineAddType extends AbstractType
{


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $this->options = $options;

        $builder
            ->add('quantity')
            ->add('product', EntityType::class, array(
                'class' => Product::class,
                'query_builder' => function (ProductRepository $er) {

                    $qb = $er->createQueryBuilder('p')
                        ->select(array('p', 'pL', 'r', 'rL'))
                        ->leftJoin('p.productLabels', 'pL')
                        ->leftJoin('p.range', 'r')
                        ->leftJoin('r.rangeLabels', 'rL')
                        ->where('p.deleted IS NULL')
                        ->andWhere('pL.language = :language')
                        ->orderBy('p.position', 'ASC')
                        ->setParameter('language', 'FR');

                    if ($this->options['quoteRequestCatalog'] && $this->options['quoteRequestCatalog'] !== 'UNKNOWN') {
                        $qb->andWhere('p.catalog = :catalog')
                            ->setParameter('catalog', $this->options['quoteRequestCatalog']);
                    }

                    return $qb;
                },
                'choice_label' => function ($product) {

                    $name = $product->getProductLabels()[0]->getName();
                    if ($product->getRange() && is_iterable($product->getRange()->getRangeLabels())) {
                        $name .= ' - ' . $product->getRange()->getRangeLabels()[0]->getName();
                    }
//                    if ($product->getCatalog()) {
//
//                        $catalog = 'Régulier';
//                        if(strtoupper($product->getCatalog()) === 'PONCTUAL'){
//                            $catalog = 'Ponctuel';
//                        }elseif(strtoupper($product->getCatalog()) === 'MATERIAL'){
//                            $catalog = 'Matériel';
//                        }
//
//                        $name .= ' - ' . $catalog;
//                    }

                    return $name;
                },
                'placeholder' => '',
                'empty_data' => null,
            ))
            ->add('frequency', ChoiceType::class, array(
                'choices' => array(
                    'REGULAR' => 'REGULAR',
                    'PONCTUAL' => 'PONCTUAL',
                    'MATERIAL' => 'MATERIAL'
                ),
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'General.Frequency.' . $choiceValue;
                },
                'required' => true,
                'expanded' => true
            ))
            ->add('frequencyTimes', ChoiceType::class, array(
                'choices' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '8',
                    '9' => '9',
                    '10' => '10',
                    '11' => '11',
                    '12' => '12'
                ),
                'expanded' => false,
                'multiple' => false
            ))
            ->add('frequencyInterval', ChoiceType::class, array(
                'choices' => array(
                    'WEEK' => 'WEEK',
                    'MONTH' => 'MONTH',
                    'BIMESTRE' => 'BIMESTRE',
                    'QUARTER' => 'QUARTER'
                ),
                "choice_label" => function ($choiceValue, $key, $value) {
                    return ($choiceValue) ? 'General.Frequency.' . $choiceValue : '';
                },
                'expanded' => false,
                'multiple' => false
            ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => QuoteRequestLine::class,
            'quoteRequestCatalog' => null
        ));
    }
}
