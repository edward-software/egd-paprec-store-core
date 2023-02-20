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
use Doctrine\ORM\ORMException;
use Exception;
use App\Entity\FollowUp;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FollowUpManager
{

    private $em;
    private $container;
    private $translator;

    public function __construct(
        EntityManagerInterface $em,
        ContainerInterface $container,
        TranslatorInterface $translator
    ) {
        $this->em = $em;
        $this->container = $container;
        $this->translator = $translator;
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


    public function sendRelaunchByEmail()
    {

        try {
            $from = $_ENV['PAPREC_EMAIL_SENDER'];

            $date = new \DateTime();
            $date->sub(new \DateInterval('P7D'));
            $query = $this->em
                ->getRepository(FollowUp::class)
                ->createQueryBuilder('fU')
                ->select('fU', 'qR', 'u')
                ->leftJoin('fU.quoteRequest', 'qR')
                ->leftJoin('qR.userInCharge', 'u')
                ->where('fU.deleted IS NULL')
                ->andWhere('qR.deleted IS NULL')
                ->andWhere('fU.dateUpdate < :date')
                ->setParameter('date', $date);

            $followUps = $query->getQuery()->getResult();

            $locale = 'FR';
            $quoteRequestByUser = [];
            $usersById = [];
            if (is_array($followUps) && count($followUps)) {
                foreach ($followUps as $followUp) {
                    if ($followUp && $followUp->getQuoteRequest() && $followUp->getQuoteRequest()->getUserInCharge()) {
                        $userInCharge = $followUp->getQuoteRequest()->getUserInCharge();
                        if (!array_key_exists($userInCharge->getId(), $usersById)) {
                            $usersById[$userInCharge->getId()] = $userInCharge;
                        }
                        if (!array_key_exists($userInCharge->getId(), $quoteRequestByUser)) {
                            $quoteRequestByUser[$userInCharge->getId()] = [];
                        }
                        $quoteRequestByUser[$userInCharge->getId()][] = $followUp->getQuoteRequest();
                    }
                }

                if (count($quoteRequestByUser)) {
                    foreach ($quoteRequestByUser as $userId => $quoteRequests) {

                        $message = new \Swift_Message();
                        $message
                            ->setSubject($this->translator->trans('Catalog.FollowUp.Email.Title',
                                array(), 'messages', strtolower($locale)))
                            ->setFrom($from)
                            ->setTo($usersById[$userId]->getEmail())
                            ->setBody(
                                $this->container->get('templating')->render(
                                    'public/emails/relaunchFollowUpEmail.html.twig',
                                    [
                                        'quoteRequests' => $quoteRequests,
                                        'user' => $usersById[$userId]
                                    ]
                                ),
                                'text/html'
                            );
                        $this->container->get('mailer')->send($message);

                    }
                }
            }


            exit;


            return false;

        } catch (ORMException $e) {
            throw new Exception('unableToSendConfirmQuoteRequest', 500);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

}
