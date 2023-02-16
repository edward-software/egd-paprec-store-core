<?php
/**
 * Created by PhpStorm.
 * User: frede
 * Date: 30/11/2018
 * Time: 16:42
 */

namespace App\Service;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use App\Entity\FollowUp;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FollowUpManager
{

    private $em;
    private $container;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function get($followUp)
    {
        $id = $followUp;
        if ($followUp instanceof FollowUp) {
            $id = $followUp->getId();
        }
        try {

            $followUp = $this->em->getRepository('App:FollowUp')->find($id);

            if ($followUp === null || $this->isDeleted($followUp)) {
                throw new EntityNotFoundException('followUpNotFound');
            }

            return $followUp;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function getByName(
        $name,
        $returnException = true
    ) {
        try {

            $followUp = $this->em->getRepository(FollowUp::class)->findOneBy([
                'name' => $name,
            ]);

            if ($followUp === null || $this->isDeleted($followUp)) {
                if ($returnException) {
                    throw new EntityNotFoundException('followUpNotFound');
                }
                return null;
            }

            return $followUp;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }

    }

    /**
     * Vérification qu'à ce jour le followUp n'est pas supprimé
     *
     * @param FollowUp $followUp
     * @param bool $throwException
     * @return bool
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function isDeleted(FollowUp $followUp, $throwException = false)
    {
        $now = new \DateTime();

        if ($followUp->getDeleted() !== null && $followUp->getDeleted() instanceof \DateTime && $followUp->getDeleted() < $now) {

            if ($throwException) {
                throw new EntityNotFoundException('followUpNotFound');
            }
            return true;
        }
        return false;
    }

}
