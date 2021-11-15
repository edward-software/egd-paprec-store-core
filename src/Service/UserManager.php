<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\PostalCode;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserManager
{

    private $em;
    private $container;


    /**
     * UserManager constructor.
     *
     * @param EntityManagerInterface $em
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }


    /**
     * Retourne un User en passant son Id ou un object USer
     * @param $user
     * @return null|object|User
     * @throws Exception
     */
    public function get($user)
    {
        $id = $user;

        if ($user instanceof User) {
            $id = $user->getId();
        }

        try {

            /** @var User $user */
            $user = $this->em->getRepository(User::class)->find($id);

            /**
             * Vérification que le user existe ou ne soit pas supprimé
             */
            if ($user === null || $this->isDeleted($user)) {
                throw new EntityNotFoundException('userNotFound');
            }


            return $user;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Vérifie qu'à ce jour, le user ce soit pas supprimé
     * @param User $user
     * @param bool $throwException
     * @return bool
     * @throws EntityNotFoundException
     */
    public function isDeleted(User $user, $throwException = false)
    {
        $now = new DateTime();
        $deleted = $user->getDeleted();

        if ($user->getDeleted() !== null && $deleted instanceof DateTime && $deleted < $now) {

            if ($throwException) {
                throw new EntityNotFoundException('userNotFound');
            }

            return true;
        }

        return false;
    }

    /**
     * Retourne le commercial en charge du code postal passé en param
     *
     * @param $postalCode
     * @return object|User|null
     * @throws Exception
     */
    public function getUserInChargeByPostalCode($pc)
    {
        try {
            if (!$pc) {
                return null;
            }

            /** @var PostalCode $postalCode */
            $postalCode = $this->em->getRepository(PostalCode::class)->findOneBy([
                'code' => $pc->getCode()
            ]);

            $user = null;
            if ($postalCode != null) {
                $user = $postalCode->getUserInCharge();
            }

            return $user;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function getByFirstNameAndLastName(
        $firstName,
        $lastName,
        $returnException = true
    ) {
        try {

            $user = $this->em->getRepository(User::class)->findOneBy([
                'firstName' => $firstName,
                'lastName' => $lastName
            ]);

            if ($user === null || $this->isDeleted($user)) {
                if ($returnException) {
                    throw new EntityNotFoundException('userNotFound');
                }
                return null;
            }

            return $user;

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }

    }


    /**
     * Retourne les Commercials dont le manager est l'user passé en param
     *
     * @param User $systemUser
     */
    public function getCommercialsFromManager($managerId)
    {
        try {

            return $this->em->getRepository(User::class)->createQueryBuilder('u')
                ->where('u.deleted is NULL')
                ->andWhere('u.manager = :manager')
                ->setParameter('manager', $managerId)
                ->getQuery()
                ->getResult();

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Retourne le premier commercial multisite trouvé
     * null si aucun trouvé
     */
    public function getRandomCommercialMultiSite()
    {
        try {

            return $this->em->getRepository(User::class)->createQueryBuilder('u')
                ->where('u.deleted is NULL')
                ->andWhere("u.roles LIKE :role")
                ->setParameter('role', '%ROLE_COMMERCIAL_MULTISITES%')
                ->setMaxresults(1)
                ->getQuery()
                ->getOneOrNullResult();

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
}
