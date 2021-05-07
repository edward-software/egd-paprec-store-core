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
use App\Entity\Agency;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AgencyManager
{

    private $em;
    private $container;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function get($agency)
    {
        $id = $agency;
        if ($agency instanceof Agency) {
            $id = $agency->getId();
        }
        try {

            $agency = $this->em->getRepository('App:Agency')->find($id);

            if ($agency === null || $this->isDeleted($agency)) {
                throw new EntityNotFoundException('agencyNotFound');
            }

            return $agency;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Vérification qu'à ce jour le agency n'est pas supprimé
     *
     * @param Agency $agency
     * @param bool $throwException
     * @return bool
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function isDeleted(Agency $agency, $throwException = false)
    {
        $now = new \DateTime();

        if ($agency->getDeleted() !== null && $agency->getDeleted() instanceof \DateTime && $agency->getDeleted() < $now) {

            if ($throwException) {
                throw new EntityNotFoundException('agencyNotFound');
            }
            return true;
        }
        return false;
    }

}
