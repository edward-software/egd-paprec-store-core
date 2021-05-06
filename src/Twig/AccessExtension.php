<?php
declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AccessExtension extends AbstractExtension
{

    private $authorizationChecker;
    
    private $tokenStorage;
    
    
    /**
     * AccessExtension constructor.
     *
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, TokenStorageInterface $tokenStorage)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }
    
    
    /**
     * @return array|TwigFilter[]
     */
    public function getFunctions()
    {
        return [
            new TwigFilter('paprec_has_access', [$this, 'hasAccess']),
        ];
    }
    
    /**
     * @param $role
     * @param null $division
     *
     * @return bool
     */
    public function hasAccess($role, $division = null)
    {
        /** @var TokenInterface $token */
        $token = $this->tokenStorage->getToken();
        
        if ($token->isAuthenticated() && $token->getUser()) {
            if ($division && $division != null) {
                if(!$this->authorizationChecker->isGranted($role)) {
                    
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'paprec_has_access';
    }
}
