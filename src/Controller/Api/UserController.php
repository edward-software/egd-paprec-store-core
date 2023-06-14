<?php

namespace App\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends AbstractController
{
    private $em;
    private $translator;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator
    ) {
        $this->em = $em;
        $this->translator = $translator;
    }

    /**
     * Récupération des commerciales
     * @Route("/users", name="store_er2_api_user_list", methods={"GET"})
     */
    public function listAction(Request $request)
    {
        try {

            $requestParam = $request->query;
            $postalCodes = $requestParam->get('postal_codes') ?? null;

            $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

            $queryBuilder->select(array('u'))
                ->from('App:User', 'u')
                ->where('u.deleted IS NULL')
                ->andWhere('u.roles LIKE \'%ROLE_COMMERCIAL%\'')
                ->andWhere('u.enabled = 1');

            if ($postalCodes) {
                $postalCodes = explode(',', $postalCodes);

                $queryBuilder
                    ->addSelect('pC')
                    ->leftJoin('u.postalCodes', 'pC')
                    ->andWhere('pC.code IN (:codes)')
                    ->setParameter('codes', $postalCodes)
                ;
            }

            $users = $queryBuilder->getQuery()->getResult();

            $tmp = [];
            if (is_array($users) && count($users)) {
                foreach ($users as $user) {

                    $codes = [];
                    if(is_iterable($user->getPostalCodes()) && count($user->getPostalCodes())){
                        foreach ($user->getPostalCodes() as $pC){
                            $codes[] = $pC->getCode();
                        }
                    }

                    $tmp[] = [
                        'id' => $user->getId(),
                        'username' => $user->getUsername(),
                        'roles' => $user->getRoles(),
                        'civility' => $user->getCivility(),
                        'last_name' => $user->getLastName(),
                        'first_name' => $user->getFirstName(),
                        'phone_number' => $user->getPhoneNumber(),
                        'mobile_number' => $user->getMobileNumber(),
                        'job_title' => $user->getJobTitle(),
                        'nick_name' => $user->getNickName(),
                        'email' => $user->getEmail(),
                        'postal_codes' => $codes
                    ];
                }
                $users = $tmp;
            }

            return new JsonResponse($users);
        } catch (\Exception $e) {
            return new JsonResponse([
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ], $e->getCode());
        }
    }
}
