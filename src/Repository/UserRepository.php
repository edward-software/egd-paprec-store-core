<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * Class UserRepository
 *
 * @package App\Repository
 */
class UserRepository extends EntityRepository
{
    /**
     * @param $params
     *
     * @return User[]|array
     */
    public function isMailUnique($params)
    {
        return $this->findBy([
            'email' => $params['email'],
            'deleted' => null
        ]);
    }
    
    /**
     * @param $params
     *
     * @return User[]|array
     */
    public function isUsernameUnique($params)
    {
        return $this->findBy([
            'username' => $params['username'],
            'deleted' => null
        ]);
    }
}
