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
use App\Entity\ProductLabel;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProductLabelManager
{

    private $em;
    private $container;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function get($productLabel)
    {
        $id = $productLabel;
        if ($productLabel instanceof ProductLabel) {
            $id = $productLabel->getId();
        }
        try {

            $productLabel = $this->em->getRepository('App:ProductLabel')->find($id);

            /**
             * Vérification que le produitLabel existe ou ne soit pas supprimé
             */
            if ($productLabel === null || $this->isDeleted($productLabel)) {
                throw new EntityNotFoundException('productLabelNotFound');
            }


            return $productLabel;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Vérifie qu'à ce jour, le produitLabel ce soit pas supprimé
     *
     * @param ProductLabel $productLabel
     * @param bool $throwException
     * @return bool
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function isDeleted(ProductLabel $productLabel, $throwException = false)
    {
        $now = new \DateTime();

        if ($productLabel->getDeleted() !== null && $productLabel->getDeleted() instanceof \DateTime && $productLabel->getDeleted() < $now) {

            if ($throwException) {
                throw new EntityNotFoundException('productLabelNotFound');
            }

            return true;

        }
        return false;
    }


}
