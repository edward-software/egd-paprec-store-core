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
use App\Entity\MissionSheetProductLabel;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MissionSheetProductLabelManager
{

    private $em;
    private $container;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function get($missionSheetProductLabel)
    {
        $id = $missionSheetProductLabel;
        if ($missionSheetProductLabel instanceof MissionSheetProductLabel) {
            $id = $missionSheetProductLabel->getId();
        }
        try {

            $missionSheetProductLabel = $this->em->getRepository('App:MissionSheetProductLabel')->find($id);

            /**
             * Vérification que le produitLabel existe ou ne soit pas supprimé
             */
            if ($missionSheetProductLabel === null || $this->isDeleted($missionSheetProductLabel)) {
                throw new EntityNotFoundException('missionSheetProductLabelNotFound');
            }


            return $missionSheetProductLabel;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Vérifie qu'à ce jour, le produitLabel ce soit pas supprimé
     *
     * @param MissionSheetProductLabel $missionSheetProductLabel
     * @param bool $throwException
     * @return bool
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function isDeleted(MissionSheetProductLabel $missionSheetProductLabel, $throwException = false)
    {
        $now = new \DateTime();

        if ($missionSheetProductLabel->getDeleted() !== null && $missionSheetProductLabel->getDeleted() instanceof \DateTime && $missionSheetProductLabel->getDeleted() < $now) {

            if ($throwException) {
                throw new EntityNotFoundException('missionSheetProductLabelNotFound');
            }

            return true;

        }
        return false;
    }


}
