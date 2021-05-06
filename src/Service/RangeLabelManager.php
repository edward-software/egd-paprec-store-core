<?php
/**
 * Created by PhpStorm.
 * User: agb
 * Date: 13/11/2018
 * Time: 11:38
 */

namespace App\Service;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use App\Entity\RangeLabel;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RangeLabelManager
{

    private $em;
    private $container;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function get($rangeLabel)
    {
        $id = $rangeLabel;
        if ($rangeLabel instanceof RangeLabel) {
            $id = $rangeLabel->getId();
        }
        try {

            $rangeLabel = $this->em->getRepository('App:RangeLabel')->find($id);

            /**
             * Vérification que le produitLabel existe ou ne soit pas supprimé
             */
            if ($rangeLabel === null || $this->isDeleted($rangeLabel)) {
                throw new EntityNotFoundException('rangeLabelNotFound');
            }


            return $rangeLabel;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Vérifie qu'à ce jour, le produitLabel ce soit pas supprimé
     *
     * @param RangeLabel $rangeLabel
     * @param bool $throwException
     * @return bool
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function isDeleted(RangeLabel $rangeLabel, $throwException = false)
    {
        $now = new \DateTime();

        if ($rangeLabel->getDeleted() !== null && $rangeLabel->getDeleted() instanceof \DateTime && $rangeLabel->getDeleted() < $now) {

            if ($throwException) {
                throw new EntityNotFoundException('rangeLabelNotFound');
            }

            return true;

        }
        return false;
    }


}
