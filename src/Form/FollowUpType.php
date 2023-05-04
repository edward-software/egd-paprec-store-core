<?php

namespace App\Form;

use App\Entity\FollowUp;
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
                'choices' => $this->options['status'],
                'empty_data' => 'PENDING',
                "choice_label" => function ($choiceValue, $key, $value) {
                    return 'Commercial.FollowUp.Status.' . $choiceValue;
                },
                'required' => true,
                'expanded' => true
            ))
            ->add('content', TextareaType::class)
        ;

        if (!$this->options['quoteRequestId']) {
            $builder->add('quoteRequest', EntityType::class, array(
                    'class' => QuoteRequest::class,
                    'query_builder' => function (EntityRepository $er) {

                        $qb = $er->createQueryBuilder('q')
                            ->select('q')
                            ->where('q.deleted is NULL');

                        if ($this->options['quoteRequestId']) {
                            $qb
                                ->andWhere('q.id = :quoteRequestId')
                                ->setParameter('quoteRequestId', $this->options['quoteRequestId']);
                        }

                        /**
                         * Si l'utilisateur est commercial multisite, on récupère uniquement les quoteRequests multisites
                         */
                        if ($this->options['isCommercialMultiSite']) {
                            $qb
                                ->andWhere('q.isMultisite = true');
                        }
                        /**
                         * Si l'utilisateur est manager, on récupère uniquement les quoteRequest liés à ses subordonnés
                         */
                        if ($this->options['isManager']) {
                            $commercials = $this->userManager->getCommercialsFromManager($this->options['userId']);
                            $commercialIds = array();
                            if ($commercials && count($commercials)) {
                                foreach ($commercials as $commercial) {
                                    $commercialIds[] = $commercial->getId();
                                }
                            }
                            $qb
                                ->andWhere('q.userInCharge IN (:commercialIds)')
                                ->setParameter('commercialIds', $commercialIds);
                        }
                        /**
                         * Si l'utilisateur est commercial, o,n récupère uniquement les quoteRequests qui lui sont associés
                         */
                        if ($this->options['isCommercial']) {
                            $qb
                                ->andWhere('q.userInCharge = :userInChargeId')
                                ->setParameter('userInChargeId', $this->options['userId']);
                        }


                        return $qb;
                    },
                    'choice_label' => function (QuoteRequest $quoteRequest) {
                        return $quoteRequest->getOrigin() . ' - ' . $quoteRequest->getNumber();
                    },
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
            'quoteRequestId' => null,
            'isCommercialMultiSite' => null,
            'isManager' => null,
            'isCommercial' => null,
            'commercialIds' => null,
            'userId' => null,
            'status' => null
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
