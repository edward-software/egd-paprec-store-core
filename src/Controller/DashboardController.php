<?php

namespace App\Controller;

use App\Entity\FollowUp;
use App\Entity\Picture;
use App\Entity\Product;
use App\Entity\ProductLabel;
use App\Entity\QuoteRequest;
use App\Entity\User;
use App\Form\PictureProductType;
use App\Form\ProductLabelType;
use App\Form\ProductMaterialType;
use App\Form\ProductType;
use App\Service\NumberManager;
use App\Service\PictureManager;
use App\Service\ProductLabelManager;
use App\Service\ProductManager;
use App\Tools\DataTable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardController extends AbstractController
{

    private $em;
    private $translator;
    private $numberManager;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        NumberManager $numberManager
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->numberManager = $numberManager;
    }

    /**
     * @Route("/activity", name="paprec_dashboard_activity_index")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function activityIndexAction(Request $request)
    {
        $user = $this->getUser();
        $userIds = [];

        $columns = [
            'M_4_12',
            'M_3',
            'M_2',
            'M_1',
            'M',
            'TOTAL'
        ];

        $lines = [
            'qr_number',
            'qr_number_opened',
            'qr_number_closed_won',
            'qr_number_closed_not_won',
            'qr_number_total',
            'qr_number_signature',
            'qr_turnover',
            'qr_turnover_opened',
            'qr_turnover_closed_won',
            'qr_turnover_closed_not_won',
            'qr_turnover_total',
            'qr_turnover_signature'
        ];

        $datas = [];
        if ($this->isGranted('ROLE_MANAGER_COMMERCIAL')) {

            $datas[0]['user_id'] = null;
            $datas[0]['name'] = 'Équipe';
            foreach ($lines as $line) {
                $datas[0][$line] = [];
                foreach ($columns as $column) {
                    $datas[0][$line][$column] = 0;
                }
            }

            $queryBuilder = $this->em->getRepository(User::class)->createQueryBuilder('u');

            $queryBuilder->select(['u'])
                ->where('u.deleted IS NULL')
                ->andWhere('u.manager = :userId')
                ->setParameter('userId', $user->getId());

            $users = $queryBuilder->getQuery()->getResult();

            if ($users && count($users)) {
                $count = 1;
                foreach ($users as $u) {
                    $userIds[] = $u->getId();
                    $datas[$count]['user_id'] = $u->getId();
                    $datas[$count]['name'] = $u->getFirstName() . ' ' . $u->getLastName();
                    foreach ($lines as $line) {
                        $datas[$count][$line] = [];
                        foreach ($columns as $column) {
                            $datas[$count][$line][$column] = 0;
                        }
                    }
                    $count++;
                }
            }

        } elseif ($this->isGranted('ROLE_COMMERCIAL') || $this->isGranted('ROLE_COMMERCIAL_MULTISITES')) {
            $userIds[] = $user->getId();
            $datas[0]['user_id'] = $user->getId();
            $datas[0]['name'] = $user->getFirstName() . ' ' . $user->getLastName();
            foreach ($lines as $line) {
                $datas[0][$line] = [];
                foreach ($columns as $column) {
                    $datas[0][$line][$column] = 0;
                }
            }
        }

        /**
         * Mois actuel
         */
        $startDateM = new \DateTime();
        $startDateM = $startDateM->modify('first day of this month');
        $startDateM = $startDateM->format('Y-m-d');
        $endDateM = new \DateTime();
        $endDateM = $endDateM->modify('last day of this month');
        $endDateM = $endDateM->format('Y-m-d');


        /**
         * Mois dernier
         */
        $startDateM1 = new \DateTime();
        $startDateM1 = $startDateM1->sub(new \DateInterval('P1M'));
        $startDateM1 = $startDateM1->modify('first day of this month');
        $startDateM1 = $startDateM1->format('Y-m-d');
        $endDateM1 = new \DateTime();
        $endDateM1 = $endDateM1->sub(new \DateInterval('P1M'));
        $endDateM1 = $endDateM1->modify('last day of this month');
        $endDateM1 = $endDateM1->format('Y-m-d');

        /**
         * Il y a 2 mois
         */
        $startDateM2 = new \DateTime();
        $startDateM2 = $startDateM2->sub(new \DateInterval('P2M'));
        $startDateM2 = $startDateM2->modify('first day of this month');
        $startDateM2 = $startDateM2->format('Y-m-d');
        $endDateM2 = new \DateTime();
        $endDateM2 = $endDateM2->sub(new \DateInterval('P2M'));
        $endDateM2 = $endDateM2->modify('last day of this month');
        $endDateM2 = $endDateM2->format('Y-m-d');

        /**
         * Il y a 3 mois
         */
        $startDateM3 = new \DateTime();
        $startDateM3 = $startDateM3->sub(new \DateInterval('P3M'));
        $startDateM3 = $startDateM3->modify('first day of this month');
        $startDateM3 = $startDateM3->format('Y-m-d');
        $endDateM3 = new \DateTime();
        $endDateM3 = $endDateM3->sub(new \DateInterval('P3M'));
        $endDateM3 = $endDateM3->modify('last day of this month');
        $endDateM3 = $endDateM3->format('Y-m-d');

        /**
         * Entre 4 et 12 mois
         */
        $startDateM12 = new \DateTime();
        $startDateM12 = $startDateM12->sub(new \DateInterval('P12M'));
        $startDateM12 = $startDateM12->modify('first day of this month');
        $startDateM12 = $startDateM12->format('Y-m-d');
        $endDateM4 = new \DateTime();
        $endDateM4 = $endDateM4->sub(new \DateInterval('P4M'));
        $endDateM4 = $endDateM4->modify('last day of this month');
        $endDateM4 = $endDateM4->format('Y-m-d');

        $queryBuilder = $this->em->getRepository(QuoteRequest::class)->createQueryBuilder('qR');

        $queryBuilder->select(array('qR'))
            ->where('qR.deleted IS NULL')
            ->andWhere('qR.userInCharge IN (:userIds)')
            ->setParameter('userIds', $userIds)
            ->andWhere('qR.dateCreation > :date')
            ->setParameter('date', $startDateM12);

        $quoteRequests = $queryBuilder->getQuery()->getResult();

        if ($quoteRequests && count($quoteRequests)) {
            foreach ($quoteRequests as $quoteRequest) {

                $userInChargeId = $quoteRequest->getUserInCharge();

                if ($userInChargeId) {
                    $userInChargeId = $userInChargeId->getId();
                    $key = array_search($userInChargeId, array_column($datas, 'user_id'));
                    $keyTeam = array_search('Équipe', array_column($datas, 'name'));

                    if ($key >= 0) {

                        $totalAmount = $this->numberManager->denormalize($quoteRequest->getTotalAmount());

                        $columnKeyName = '';
                        $dateCreation = $quoteRequest->getDateCreation();
                        $dateCreation = $dateCreation->format('Y-m-d');
                        if ($dateCreation >= $startDateM12 && $dateCreation <= $endDateM4) {
                            $columnKeyName = 'M_4_12';
                        }
                        if ($dateCreation >= $startDateM3 && $dateCreation <= $endDateM3) {
                            $columnKeyName = 'M_3';
                        }
                        if ($dateCreation >= $startDateM2 && $dateCreation <= $endDateM2) {
                            $columnKeyName = 'M_2';
                        }
                        if ($dateCreation >= $startDateM1 && $dateCreation <= $endDateM1) {
                            $columnKeyName = 'M_1';
                        }
                        if ($dateCreation >= $startDateM && $dateCreation <= $endDateM) {
                            $columnKeyName = 'M';
                        }

                        /**
                         * Nombre de nouvelles affaires
                         */
                        $datas[$key]['qr_number_total'][$columnKeyName]++;
                        $datas[$key]['qr_number_total']['TOTAL']++;
                        if ($keyTeam >= 0) {
                            $datas[$keyTeam]['qr_number_total'][$columnKeyName]++;
                            $datas[$keyTeam]['qr_number_total']['TOTAL']++;
                        }

                        /**
                         * CA nouvelles affaires
                         */
                        $datas[$key]['qr_turnover_total'][$columnKeyName] += $totalAmount;
                        $datas[$key]['qr_turnover_total']['TOTAL'] += $totalAmount;
                        if ($keyTeam >= 0) {
                            $datas[$keyTeam]['qr_turnover_total'][$columnKeyName] += $totalAmount;
                            $datas[$keyTeam]['qr_turnover_total']['TOTAL'] += $totalAmount;
                        }

                        if ($quoteRequest->getQuoteStatus() === 'CONTRACT_SIGNED') {
                            /**
                             * cloturées gagnées
                             */
                            $datas[$key]['qr_number_closed_won'][$columnKeyName]++;
                            $datas[$key]['qr_number_closed_won']['TOTAL']++;
                            if ($keyTeam >= 0) {
                                $datas[$keyTeam]['qr_number_closed_won'][$columnKeyName]++;
                                $datas[$keyTeam]['qr_number_closed_won']['TOTAL']++;
                            }

                            /**
                             * Taux signature
                             */
                            $datas[$key]['qr_number_signature'][$columnKeyName] = round(($datas[$key]['qr_number_closed_won'][$columnKeyName] * 100) / $datas[$key]['qr_number_total'][$columnKeyName], 1);
                            $datas[$key]['qr_number_signature']['TOTAL'] = round(($datas[$key]['qr_number_closed_won']['TOTAL'] * 100) / $datas[$key]['qr_number_total']['TOTAL'], 1);

                            /**
                             * CA cloturées gagnées
                             */
                            $datas[$key]['qr_turnover_closed_won'][$columnKeyName] += $totalAmount;
                            $datas[$key]['qr_turnover_closed_won']['TOTAL'] += $totalAmount;
                            if ($keyTeam >= 0) {
                                $datas[$keyTeam]['qr_turnover_closed_won'][$columnKeyName] += $totalAmount;
                                $datas[$keyTeam]['qr_turnover_closed_won']['TOTAL'] += $totalAmount;
                            }

                            /**
                             * Taux signature CA
                             */
                            if ($datas[$key]['qr_turnover_total'][$columnKeyName] > 0) {
                                $datas[$key]['qr_turnover_signature'][$columnKeyName] = round(($datas[$key]['qr_turnover_closed_won'][$columnKeyName] * 100) / $datas[$key]['qr_turnover_total'][$columnKeyName], 1);
                                $datas[$key]['qr_turnover_signature']['TOTAL'] = round(($datas[$key]['qr_turnover_closed_won']['TOTAL'] * 100) / $datas[$key]['qr_turnover_total']['TOTAL'], 1);
                            }

                        } elseif ($quoteRequest->getQuoteStatus() === 'CONTRACT_LOST') {
                            /**
                             * cloturées non gagnées
                             */
                            $datas[$key]['qr_number_closed_not_won'][$columnKeyName]++;
                            $datas[$key]['qr_number_closed_not_won']['TOTAL']++;
                            if ($keyTeam >= 0) {
                                $datas[$keyTeam]['qr_number_closed_not_won'][$columnKeyName]++;
                                $datas[$keyTeam]['qr_number_closed_not_won']['TOTAL']++;
                            }

                            /**
                             * CA cloturées non gagnées
                             */
                            $datas[$key]['qr_turnover_closed_not_won'][$columnKeyName] += $totalAmount;
                            $datas[$key]['qr_turnover_closed_not_won']['TOTAL'] += $totalAmount;
                            if ($keyTeam >= 0) {
                                $datas[$keyTeam]['qr_turnover_closed_not_won'][$columnKeyName] += $totalAmount;
                                $datas[$keyTeam]['qr_turnover_closed_not_won']['TOTAL'] += $totalAmount;
                            }

                        } else {
                            /**
                             * affaire ouverte
                             */
                            $datas[$key]['qr_number_opened'][$columnKeyName]++;
                            $datas[$key]['qr_number_opened']['TOTAL']++;
                            if ($keyTeam >= 0) {
                                $datas[$keyTeam]['qr_number_opened'][$columnKeyName]++;
                                $datas[$keyTeam]['qr_number_opened']['TOTAL']++;
                            }

                            /**
                             * Nombre de nouvelles affaires
                             */
                            $datas[$key]['qr_number'][$columnKeyName]++;
                            $datas[$key]['qr_number']['TOTAL']++;
                            if ($keyTeam >= 0) {
                                $datas[$keyTeam]['qr_number'][$columnKeyName]++;
                                $datas[$keyTeam]['qr_number']['TOTAL']++;
                            }

                            /**
                             * CA nouvelles affaires
                             */
                            $datas[$key]['qr_turnover'][$columnKeyName] += $totalAmount;
                            $datas[$key]['qr_turnover']['TOTAL'] += $totalAmount;
                            if ($keyTeam >= 0) {
                                $datas[$keyTeam]['qr_turnover'][$columnKeyName] += $totalAmount;
                                $datas[$keyTeam]['qr_turnover']['TOTAL'] += $totalAmount;
                            }

                            /**
                             * CA affaires ouvertes
                             */
                            $datas[$key]['qr_turnover_opened'][$columnKeyName] += $totalAmount;
                            $datas[$key]['qr_turnover_opened']['TOTAL'] += $totalAmount;
                            if ($keyTeam >= 0) {
                                $datas[$keyTeam]['qr_turnover_opened'][$columnKeyName] += $totalAmount;
                                $datas[$keyTeam]['qr_turnover_opened']['TOTAL'] += $totalAmount;
                            }
                        }

                        if ($keyTeam >= 0) {
                            if($datas[$keyTeam]['qr_number_total'][$columnKeyName] > 0){
                                $datas[$keyTeam]['qr_number_signature'][$columnKeyName] = round(($datas[$keyTeam]['qr_number_closed_won'][$columnKeyName] * 100) / $datas[$keyTeam]['qr_number_total'][$columnKeyName], 1);
                            }
                            if($datas[$keyTeam]['qr_number_total']['TOTAL'] > 0){
                                $datas[$keyTeam]['qr_number_signature']['TOTAL'] = round(($datas[$keyTeam]['qr_number_closed_won']['TOTAL'] * 100) / $datas[$keyTeam]['qr_number_total']['TOTAL'], 1);
                            }
                            if($datas[$keyTeam]['qr_turnover_total'][$columnKeyName] > 0){
                                $datas[$keyTeam]['qr_turnover_signature'][$columnKeyName] = round(($datas[$keyTeam]['qr_turnover_closed_won'][$columnKeyName] * 100) / $datas[$keyTeam]['qr_turnover_total'][$columnKeyName], 1);
                            }
                            if($datas[$keyTeam]['qr_turnover_total']['TOTAL'] > 0){
                                $datas[$keyTeam]['qr_turnover_signature']['TOTAL'] = round(($datas[$keyTeam]['qr_turnover_closed_won']['TOTAL'] * 100) / $datas[$keyTeam]['qr_turnover_total']['TOTAL'], 1);
                            }
                        }

                    }

                }

            }
        }
//        exit;
        return $this->render('dashboard/activity/index.html.twig', [
            'quoteRequests' => $quoteRequests,
            'user' => $user,
            'datas' => $datas
        ]);
    }
}
