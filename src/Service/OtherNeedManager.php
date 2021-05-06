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
use App\Entity\OtherNeed;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OtherNeedManager
{

    private $em;
    private $container;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function get($otherNeed)
    {
        $id = $otherNeed;
        if ($otherNeed instanceof OtherNeed) {
            $id = $otherNeed->getId();
        }
        try {

            $otherNeed = $this->em->getRepository('App:OtherNeed')->find($id);

            if ($otherNeed === null || $this->isDeleted($otherNeed)) {
                throw new EntityNotFoundException('otherNeedNotFound');
            }

            return $otherNeed;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Vérification qu'à ce jour le otherNeed  n'est pas supprimé
     *
     * @param OtherNeed $otherNeed
     * @param bool $throwException
     * @return bool
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function isDeleted(OtherNeed $otherNeed, $throwException = false)
    {
        $now = new \DateTime();

        if ($otherNeed->getDeleted() !== null && $otherNeed->getDeleted() instanceof \DateTime && $otherNeed->getDeleted() < $now) {

            if ($throwException) {
                throw new EntityNotFoundException('otherNeedNotFound');
            }
            return true;
        }
        return false;
    }

    /**
     * @return object|OtherNeed|null
     * @throws Exception
     */
    public function getByLocale($locale)
    {
        try {

            $customizableArea = $this->em->getRepository('App:OtherNeed')->findBy(array(
                'language' => $locale,
                'isDisplayed' => true,
                'deleted' => null
            ));

            if ($customizableArea === null) {
                return null;
            }

            return $customizableArea;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
}
