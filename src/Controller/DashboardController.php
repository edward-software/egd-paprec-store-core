<?php

namespace App\Controller;

use App\Entity\QuoteRequest;
use App\Entity\QuoteRequestLine;
use App\Entity\Setting;
use App\Entity\User;
use App\Service\NumberManager;
use App\Service\ProductManager;
use App\Tools\DataTable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardController extends AbstractController
{

    private $em;
    private $translator;
    private $numberManager;
    private $productManager;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        NumberManager $numberManager,
        ProductManager $productManager
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->numberManager = $numberManager;
        $this->productManager = $productManager;
    }

    /**
     * @Route("/activity", name="paprec_dashboard_activity_index")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function activityIndexAction(Request $request)
    {
        $user = $this->getUser();
        $userIds = [];
        $selectedCatalog = $request->get('selectedCatalog');
        $session = $this->get('session');

        if (!$selectedCatalog) {
            $selectedCatalog = $session->get('activityDashboardFilterSelectedCatalog');
        } else {
            $session->set('activityDashboardFilterSelectedCatalog', $selectedCatalog);
        }

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
        if ($this->isGranted('ROLE_ADMIN')) {

            $datas[0]['user_id'] = null;
            $datas[0]['name'] = 'Équipe';
            $datas[0]['team_user_ids'] = '';
            foreach ($lines as $line) {
                $datas[0][$line] = [];
                foreach ($columns as $column) {
                    $datas[0][$line][$column] = 0;
                }
            }

            $queryBuilder = $this->em->getRepository(User::class)->createQueryBuilder('u');

            $queryBuilder->select(['u'])
                ->where('u.deleted IS NULL');

            $users = $queryBuilder->getQuery()->getResult();

            if ($users && count($users)) {

                /**
                 * On met les utilisateurs par manager
                 */
                $usersByManager = [];
                $usersById = [];
                foreach ($users as $user) {
                    $userIds[] = $user->getId();
                    $usersById[$user->getId()] = $user;
                    $roles = $user->getRoles();

                    /**
                     * On récupère tous les users par team, cela nous sert pour filtrer la liste des devis
                     */
                    if ($datas[0]['team_user_ids'] !== '') {
                        $datas[0]['team_user_ids'] .= ',';
                    }
                    $datas[0]['team_user_ids'] .= $user->getId();

                    if (in_array('ROLE_ADMIN', $roles)) {

                    } elseif (in_array('ROLE_MANAGER_COMMERCIAL', $roles)) {

                        if (!array_key_exists($user->getId(), $usersByManager)) {
                            $usersByManager[$user->getId()] = [];
                        }
                        $usersByManager[$user->getId()][] = $user;

                    } elseif (in_array('ROLE_COMMERCIAL', $roles) || in_array('ROLE_COMMERCIAL_MULTISITES', $roles)) {
                        if ($user->getManager()) {
                            if (!array_key_exists($user->getManager()->getId(), $usersByManager)) {
                                $usersByManager[$user->getManager()->getId()] = [];
                            }
                            $usersByManager[$user->getManager()->getId()][] = $user;
                        }
                    }
                }

                $count = 1;
                foreach ($usersByManager as $managerId => $uByManager) {
                    $managerKey = $count;
                    $u = $usersById[$managerId];
                    $datas[$count]['user_id'] = null;
                    $datas[$count]['team_user_ids'] = '';
                    $datas[$count]['name'] = 'Équipe : ' . $u->getFirstName() . ' ' . $u->getLastName();
                    foreach ($lines as $line) {
                        $datas[$count][$line] = [];
                        foreach ($columns as $column) {
                            $datas[$count][$line][$column] = 0;
                        }
                    }
                    $count++;

                    foreach ($uByManager as $user) {

                        $datas[$count]['teams'] = [
                            0, // Équipe Globale
                            $managerKey
                        ];

                        /**
                         * On récupère tous les users par team, cela nous sert pour filtrer la liste des devis
                         */
                        if ($datas[$managerKey]['team_user_ids'] !== '') {
                            $datas[$managerKey]['team_user_ids'] .= ',';
                        }
                        $datas[$managerKey]['team_user_ids'] .= $user->getId();

                        $datas[$count]['user_id'] = $user->getId();
                        $datas[$count]['team_user_ids'] = $user->getId();
                        $datas[$count]['name'] = $user->getFirstName() . ' ' . $user->getLastName();
                        foreach ($lines as $line) {
                            $datas[$count][$line] = [];
                            foreach ($columns as $column) {
                                $datas[$count][$line][$column] = 0;
                            }
                        }
                        $count++;

                    }
                }
            }

        } elseif ($this->isGranted('ROLE_MANAGER_COMMERCIAL')) {

            $datas[0]['user_id'] = null;
            $datas[0]['team_user_ids'] = '';
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
                ->andWhere($queryBuilder->expr()->orx(
                    ('u.manager = :userId'),
                    ('u.id = :userId')
                ))
                ->setParameter('userId', $user->getId());

            $users = $queryBuilder->getQuery()->getResult();

            if ($users && count($users)) {
                $count = 1;
                foreach ($users as $u) {
                    $userIds[] = $u->getId();
                    $datas[$count]['teams'] = [
                        0 // Équipe Globale
                    ];

                    /**
                     * On récupère tous les users par team, cela nous sert pour filtrer la liste des devis
                     */
                    if ($datas[0]['team_user_ids'] !== '') {
                        $datas[0]['team_user_ids'] .= ',';
                    }
                    $datas[0]['team_user_ids'] .= $u->getId();


                    $datas[$count]['user_id'] = $u->getId();
                    $datas[$count]['team_user_ids'] = $u->getId();
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
            $datas[0]['team_user_ids'] = $user->getId();
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
        $startDateM12 = $startDateM12->sub(new \DateInterval('P11M'));
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

        if ($selectedCatalog && $selectedCatalog !== 'ALL') {
            $queryBuilder
                ->andWhere('qR.catalog = :catalog')
                ->setParameter('catalog', $selectedCatalog);
        }

        $quoteRequests = $queryBuilder->getQuery()->getResult();

        if ($quoteRequests && count($quoteRequests)) {
            foreach ($quoteRequests as $quoteRequest) {

                $userInChargeId = $quoteRequest->getUserInCharge();

                if ($userInChargeId) {
                    $userInChargeId = $userInChargeId->getId();
                    $key = array_search($userInChargeId, array_column($datas, 'user_id'));
                    $keysTeam = null;

                    if (
                        (($this->isGranted('ROLE_COMMERCIAL') || $this->isGranted('ROLE_COMMERCIAL_MULTISITES')) && $key === 0) ||
                        ($key != false && $key >= 0)
                    ) {

                        if (array_key_exists('teams', $datas[$key])) {
                            $keysTeam = $datas[$key]['teams'];
                        }

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
                        if ($keysTeam && count($keysTeam) >= 0) {
                            foreach ($keysTeam as $keyTeam) {
                                $datas[$keyTeam]['qr_number_total'][$columnKeyName]++;
                                $datas[$keyTeam]['qr_number_total']['TOTAL']++;
                            }
                        }

                        /**
                         * CA nouvelles affaires
                         */
                        $datas[$key]['qr_turnover_total'][$columnKeyName] += $totalAmount;
                        $datas[$key]['qr_turnover_total']['TOTAL'] += $totalAmount;
                        if ($keysTeam && count($keysTeam) >= 0) {
                            foreach ($keysTeam as $keyTeam) {
                                $datas[$keyTeam]['qr_turnover_total'][$columnKeyName] += $totalAmount;
                                $datas[$keyTeam]['qr_turnover_total']['TOTAL'] += $totalAmount;
                            }
                        }

                        if ($quoteRequest->getQuoteStatus() === 'CONTRACT_SIGNED') {
                            /**
                             * cloturées gagnées
                             */
                            $datas[$key]['qr_number_closed_won'][$columnKeyName]++;
                            $datas[$key]['qr_number_closed_won']['TOTAL']++;
                            if ($keysTeam && count($keysTeam) >= 0) {
                                foreach ($keysTeam as $keyTeam) {
                                    $datas[$keyTeam]['qr_number_closed_won'][$columnKeyName]++;
                                    $datas[$keyTeam]['qr_number_closed_won']['TOTAL']++;
                                }
                            }

                            /**
                             * Taux signature
                             */
                            $datas[$key]['qr_number_signature'][$columnKeyName] = round(($datas[$key]['qr_number_closed_won'][$columnKeyName] * 100) / $datas[$key]['qr_number_total'][$columnKeyName],
                                1);
                            $datas[$key]['qr_number_signature']['TOTAL'] = round(($datas[$key]['qr_number_closed_won']['TOTAL'] * 100) / $datas[$key]['qr_number_total']['TOTAL'],
                                1);

                            /**
                             * CA cloturées gagnées
                             */
                            $datas[$key]['qr_turnover_closed_won'][$columnKeyName] += $totalAmount;
                            $datas[$key]['qr_turnover_closed_won']['TOTAL'] += $totalAmount;
                            if ($keysTeam && count($keysTeam) >= 0) {
                                foreach ($keysTeam as $keyTeam) {
                                    $datas[$keyTeam]['qr_turnover_closed_won'][$columnKeyName] += $totalAmount;
                                    $datas[$keyTeam]['qr_turnover_closed_won']['TOTAL'] += $totalAmount;
                                }
                            }

                            /**
                             * Taux signature CA
                             */
                            if ($datas[$key]['qr_turnover_total'][$columnKeyName] > 0) {
                                $datas[$key]['qr_turnover_signature'][$columnKeyName] = round(($datas[$key]['qr_turnover_closed_won'][$columnKeyName] * 100) / $datas[$key]['qr_turnover_total'][$columnKeyName],
                                    1);
                                $datas[$key]['qr_turnover_signature']['TOTAL'] = round(($datas[$key]['qr_turnover_closed_won']['TOTAL'] * 100) / $datas[$key]['qr_turnover_total']['TOTAL'],
                                    1);
                            }

                        } elseif ($quoteRequest->getQuoteStatus() === 'CONTRACT_LOST') {
                            /**
                             * cloturées non gagnées
                             */
                            $datas[$key]['qr_number_closed_not_won'][$columnKeyName]++;
                            $datas[$key]['qr_number_closed_not_won']['TOTAL']++;
                            if ($keysTeam && count($keysTeam) >= 0) {
                                foreach ($keysTeam as $keyTeam) {
                                    $datas[$keyTeam]['qr_number_closed_not_won'][$columnKeyName]++;
                                    $datas[$keyTeam]['qr_number_closed_not_won']['TOTAL']++;
                                }
                            }

                            /**
                             * CA cloturées non gagnées
                             */
                            $datas[$key]['qr_turnover_closed_not_won'][$columnKeyName] += $totalAmount;
                            $datas[$key]['qr_turnover_closed_not_won']['TOTAL'] += $totalAmount;
                            if ($keysTeam && count($keysTeam) >= 0) {
                                foreach ($keysTeam as $keyTeam) {
                                    $datas[$keyTeam]['qr_turnover_closed_not_won'][$columnKeyName] += $totalAmount;
                                    $datas[$keyTeam]['qr_turnover_closed_not_won']['TOTAL'] += $totalAmount;
                                }
                            }

                        } else {
                            /**
                             * affaire ouverte
                             */
                            $datas[$key]['qr_number_opened'][$columnKeyName]++;
                            $datas[$key]['qr_number_opened']['TOTAL']++;
                            if ($keysTeam && count($keysTeam) >= 0) {
                                foreach ($keysTeam as $keyTeam) {
                                    $datas[$keyTeam]['qr_number_opened'][$columnKeyName]++;
                                    $datas[$keyTeam]['qr_number_opened']['TOTAL']++;
                                }
                            }

                            /**
                             * Nombre de nouvelles affaires
                             */
                            $datas[$key]['qr_number'][$columnKeyName]++;
                            $datas[$key]['qr_number']['TOTAL']++;
                            if ($keysTeam && count($keysTeam) >= 0) {
                                foreach ($keysTeam as $keyTeam) {
                                    $datas[$keyTeam]['qr_number'][$columnKeyName]++;
                                    $datas[$keyTeam]['qr_number']['TOTAL']++;
                                }
                            }

                            /**
                             * CA nouvelles affaires
                             */
                            $datas[$key]['qr_turnover'][$columnKeyName] += $totalAmount;
                            $datas[$key]['qr_turnover']['TOTAL'] += $totalAmount;
                            if ($keysTeam && count($keysTeam) >= 0) {
                                foreach ($keysTeam as $keyTeam) {
                                    $datas[$keyTeam]['qr_turnover'][$columnKeyName] += $totalAmount;
                                    $datas[$keyTeam]['qr_turnover']['TOTAL'] += $totalAmount;
                                }
                            }

                            /**
                             * CA affaires ouvertes
                             */
                            $datas[$key]['qr_turnover_opened'][$columnKeyName] += $totalAmount;
                            $datas[$key]['qr_turnover_opened']['TOTAL'] += $totalAmount;
                            if ($keysTeam && count($keysTeam) >= 0) {
                                foreach ($keysTeam as $keyTeam) {
                                    $datas[$keyTeam]['qr_turnover_opened'][$columnKeyName] += $totalAmount;
                                    $datas[$keyTeam]['qr_turnover_opened']['TOTAL'] += $totalAmount;
                                }
                            }
                        }

                        if ($keysTeam && count($keysTeam) >= 0) {
                            foreach ($keysTeam as $keyTeam) {
                                if ($datas[$keyTeam]['qr_number_total'][$columnKeyName] > 0) {
                                    $datas[$keyTeam]['qr_number_signature'][$columnKeyName] = round(($datas[$keyTeam]['qr_number_closed_won'][$columnKeyName] * 100) / $datas[$keyTeam]['qr_number_total'][$columnKeyName],
                                        1);
                                }
                                if ($datas[$keyTeam]['qr_number_total']['TOTAL'] > 0) {
                                    $datas[$keyTeam]['qr_number_signature']['TOTAL'] = round(($datas[$keyTeam]['qr_number_closed_won']['TOTAL'] * 100) / $datas[$keyTeam]['qr_number_total']['TOTAL'],
                                        1);
                                }
                                if ($datas[$keyTeam]['qr_turnover_total'][$columnKeyName] > 0) {
                                    $datas[$keyTeam]['qr_turnover_signature'][$columnKeyName] = round(($datas[$keyTeam]['qr_turnover_closed_won'][$columnKeyName] * 100) / $datas[$keyTeam]['qr_turnover_total'][$columnKeyName],
                                        1);
                                }
                                if ($datas[$keyTeam]['qr_turnover_total']['TOTAL'] > 0) {
                                    $datas[$keyTeam]['qr_turnover_signature']['TOTAL'] = round(($datas[$keyTeam]['qr_turnover_closed_won']['TOTAL'] * 100) / $datas[$keyTeam]['qr_turnover_total']['TOTAL'],
                                        1);
                                }
                            }
                        }

                    }
                }
            }
        }

        return $this->render('dashboard/activity/index.html.twig', [
            'quoteRequests' => $quoteRequests,
            'user' => $user,
            'datas' => $datas,
            'catalogs' => [
                'ALL',
                'REGULAR',
                'PONCTUAL',
                'MATERIAL'
            ],
            'startDateM12' => $startDateM12,
            'endDateM4' => $endDateM4,
            'startDateM3' => $startDateM3,
            'endDateM3' => $endDateM3,
            'startDateM2' => $startDateM2,
            'endDateM2' => $endDateM2,
            'startDateM1' => $startDateM1,
            'endDateM1' => $endDateM1,
            'startDateM' => $startDateM,
            'endDateM' => $endDateM,
            'selectedCatalog' => $selectedCatalog
        ]);
    }

    /**
     * @Route("/activity/exportQuoteRequest", name="paprec_dashboard_activity_quoteRequest_export")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function exportAction(Request $request)
    {
        $catalog = $request->get('selectedCatalog');
        $status = $request->get('selectedStatus');
        $periodStartDate = $request->get('periodStartDate');
        $periodEndDate = $request->get('periodEndDate');
        $userIds = $request->get('userIds');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder->select(array('q'))
            ->from('App:QuoteRequest', 'q')
            ->where('q.deleted IS NULL');
        if ($status !== null && $status !== '#') {

            $status = explode(',', $status);

            $queryBuilder
                ->andWhere('q.quoteStatus IN (:status)')
                ->setParameter('status', $status);
        }

        if ($catalog) {
            $queryBuilder
                ->andWhere('q.catalog = :catalog')
                ->setParameter('catalog', $catalog);
        }

        if ($periodStartDate && $periodEndDate) {
            $periodStartDate = new \DateTime($periodStartDate);
            $periodEndDate = new \DateTime($periodEndDate);

            $queryBuilder
                ->andWhere('q.dateCreation >= :start')
                ->andWhere('q.dateCreation < :end')
                ->setParameter('start', $periodStartDate->format('Y-m-d') . ' 00:00:00')
                ->setParameter('end', $periodEndDate->format('Y-m-d') . ' 23:59:59');
        }

        if (!empty($userIds)) {
            $userIds = explode(',', $userIds);

            $queryBuilder
                ->andWhere('q.userInCharge IN (:userIds)')
                ->setParameter('userIds', $userIds);
        }

        /** @var QuoteRequest[] $quoteRequests */
        $quoteRequests = $queryBuilder->getQuery()->getResult();

        /** @var Spreadsheet $spreadsheet */
        $spreadsheet = new Spreadsheet();

        $spreadsheet
            ->getProperties()->setCreator("Paprec Easy Recyclage")
            ->setLastModifiedBy("EasyRecyclageShop")
            ->setTitle("Paprec Easy Recyclage - Devis")
            ->setSubject("Extraction");

        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $sheet->setTitle('Devis');

        // Labels
        $sheetLabels = [
            'ID',
            'Date créa.',
            'Dernière modification',
            'Langue',
            'Numéro offre du devis',
            'Nom société',
            'Civilité',
            'Nom',
            'Prénom',
            'Email',
            'Téléphone',
            'Prestation multi-sites',
            'Staff',
            'Accès',
            'Adresse',
            'Ville',
            'Remarques',
            'Statut',
            'Montant total (€)',
//            'Ajustement prix (+/- en %)',
            'Commentaire commercial',
            'Budget annuel',
            'Numéro client',
            'Référence de l\'offre',
            'Commercial en charge',
            'Code postal',
            'Date de fin de la prestation'
        ];

        $xAxe = 'A';
        foreach ($sheetLabels as $label) {
            $sheet->setCellValue($xAxe . 1, $label);
            $xAxe++;
        }

        $yAxe = 2;
        foreach ($quoteRequests as $quoteRequest) {

            $getters = [
                $quoteRequest->getId(),
                $quoteRequest->getDateCreation()->format('Y-m-d'),
                $quoteRequest->getDateUpdate() ? $quoteRequest->getDateUpdate()->format('Y-m-d') : '',
                $quoteRequest->getLocale(),
                $quoteRequest->getNumber(),
                $quoteRequest->getBusinessName(),
                $quoteRequest->getCivility(),
                $quoteRequest->getLastName(),
                $quoteRequest->getFirstName(),
                $quoteRequest->getEmail(),
                $quoteRequest->getPhone(),
                $quoteRequest->getIsMultisite() ? 'true' : 'false',
                $this->translator->trans('Commercial.StaffList.' . $quoteRequest->getStaff()),
                $quoteRequest->getAccess(),
                $quoteRequest->getAddress(),
                $quoteRequest->getCity(),
                $quoteRequest->getComment(),
                $quoteRequest->getQuoteStatus(),
                $this->numberManager->denormalize($quoteRequest->getTotalAmount()),
//                $this->numberManager->denormalize($quoteRequest->getOverallDiscount()) . '%',
                $quoteRequest->getSalesmanComment(),
                $this->numberManager->denormalize($quoteRequest->getAnnualBudget()),
//                $quoteRequest->getFrequency(),
//                $quoteRequest->getFrequencyTimes(),
//                $quoteRequest->getFrequencyInterval(),
                $quoteRequest->getCustomerId(),
                $quoteRequest->getReference(),
                $quoteRequest->getUserInCharge() ? $quoteRequest->getUserInCharge()->getFirstName() . " " . $quoteRequest->getUserInCharge()->getLastName() : '',
                $quoteRequest->getPostalCode() ? $quoteRequest->getPostalCode()->getCode() : '',
                $quoteRequest->getServiceEndDate() ? $quoteRequest->getServiceEndDate()->format('Y-m-d') : '',
            ];

            $xAxe = 'A';
            foreach ($getters as $getter) {
                $sheet->setCellValue($xAxe . $yAxe, (string)$getter);
                $xAxe++;
            }
            $yAxe++;
        }


        // Resize columns
        for ($i = 'A'; $i != $sheet->getHighestDataColumn(); $i++) {
            $sheet->getColumnDimension($i)->setAutoSize(true);
        }

        $fileName = 'EasyRecyclageShop-Extraction-Devis--' . date('Y-m-d') . '.xlsx';

        $streamedResponse = new StreamedResponse();
        $streamedResponse->setCallback(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $streamedResponse->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $streamedResponse->headers->set('Pragma', 'public');
        $streamedResponse->headers->set('Cache-Control', 'maxage=1');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename=' . $fileName);

        return $streamedResponse->send();
    }

    /**
     * @Route("/followUp", name="paprec_dashboard_follow_up_index")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function followUpIndexAction(Request $request)
    {
        $user = $this->getUser();
        $session = $this->get('session');
        $selectedCatalog = $session->get('followUpDashboardFilterSelectedCatalog');
        $selectedPrice1 = $session->get('followUpDashboardFilterSelectedPrice1');
        $selectedPrice2 = $session->get('followUpDashboardFilterSelectedPrice2');
        $selectedStatus = $session->get('followUpDashboardFilterSelectedStatus');

        $query = $this->em
            ->getRepository(Setting::class)
            ->createQueryBuilder('s')
            ->select('s')
            ->where('s.deleted IS NULL')
            ->andWhere('s.keyName = :key')
            ->setParameter('key', 'DASHBOARD_FOLLOW_UP_FILTER_PRICE')
            ->orderBy('s.value + 0', 'ASC');

        $prices = $query->getQuery()->getResult();

        $tmp = [];
        $tmp[] = null;
        if ($prices && count($prices)) {
            foreach ($prices as $p) {
                $tmp[] = $p->getValue();
            }
            $prices = $tmp;
        }

        $status = [];
        $status['ALL'] = 'ALL';
        foreach ($this->getParameter('paprec_quote_status') as $s) {
            $status[$s] = $s;
        }

        return $this->render('dashboard/followUp/index.html.twig', [
            'user' => $user,
            'selectedCatalog' => $selectedCatalog,
            'selectedPrice1' => $selectedPrice1,
            'selectedPrice2' => $selectedPrice2,
            'selectedStatus' => $selectedStatus,
            'catalogs' => [
                'ALL',
                'REGULAR',
                'PONCTUAL',
                'MATERIAL'
            ],
            'prices' => $prices,
            'status' => $status
        ]);
    }

    /**
     * @Route("/loadList", name="paprec_dashboard_follow_up_loadList")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function followUpLoadListAction(Request $request, DataTable $dataTable, PaginatorInterface $paginator)
    {
        $systemUser = $this->getUser();
        $session = $this->get('session');
        $userIds = [];
        $selectedCatalog = $request->get('selectedCatalog');
        $selectedPrice1 = $request->get('selectedPrice1');
        $selectedPrice2 = $request->get('selectedPrice2');
        $selectedStatus = $request->get('selectedStatus');

        if (!$selectedCatalog) {
            $selectedCatalog = $session->get('followUpDashboardFilterSelectedCatalog');
        } else {
            if ($selectedCatalog === 'ALL') {
                $selectedCatalog = null;
            }
            $session->set('followUpDashboardFilterSelectedCatalog', $selectedCatalog);
        }

        if (!$selectedPrice1) {
            $selectedPrice1 = $session->get('followUpDashboardFilterSelectedPrice1');
        } else {
            if ($selectedPrice1 === '#') {
                $selectedPrice1 = null;
            }
            $session->set('followUpDashboardFilterSelectedPrice1', $selectedPrice1);
        }

        if (!$selectedPrice2) {
            $selectedPrice2 = $session->get('followUpDashboardFilterSelectedPrice2');
        } else {
            if ($selectedPrice2 === '#') {
                $selectedPrice2 = null;
            }
            $session->set('followUpDashboardFilterSelectedPrice2', $selectedPrice2);
        }

        if (!$selectedStatus) {
            $selectedStatus = $session->get('followUpDashboardFilterSelectedStatus');
        } else {
            if ($selectedStatus === 'ALL') {
                $selectedStatus = null;
            }
            $session->set('followUpDashboardFilterSelectedStatus', $selectedStatus);
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            $queryBuilder = $this->em->getRepository(User::class)->createQueryBuilder('u');

            $queryBuilder->select(['u'])
                ->where('u.deleted IS NULL');

            $users = $queryBuilder->getQuery()->getResult();

            if ($users && count($users)) {
                foreach ($users as $u) {
                    $userIds[] = $u->getId();
                }
            }

        } elseif ($this->isGranted('ROLE_MANAGER_COMMERCIAL')) {
            $queryBuilder = $this->em->getRepository(User::class)->createQueryBuilder('u');

            $queryBuilder->select(['u'])
                ->where('u.deleted IS NULL')
                ->andWhere('u.manager = :userId')
                ->setParameter('userId', $systemUser->getId());

            $users = $queryBuilder->getQuery()->getResult();

            if ($users && count($users)) {
                foreach ($users as $u) {
                    $userIds[] = $u->getId();
                }
            }

        } elseif ($this->isGranted('ROLE_COMMERCIAL') || $this->isGranted('ROLE_COMMERCIAL_MULTISITES')) {
            $userIds[] = $systemUser->getId();
        }

        $return = [];

        $filters = $request->get('filters');
        $pageSize = $request->get('length');
        $start = $request->get('start');
        $orders = $request->get('order');
        $search = $request->get('search');
        $columns = $request->get('columns');
        $rowPrefix = $request->get('rowPrefix');

        $cols['number'] = array(
            'label' => 'number',
            'id' => 'q.number',
            'method' => array('getNumber')
        );
        $cols['userInCharge'] = array(
            'label' => 'userInCharge',
            'id' => 'q.userInCharge',
            'method' => array('getUserInCharge')
        );
        $cols['customer'] = array(
            'label' => 'customer',
            'id' => 'q.firstName',
            'method' => array('getFirstName')
        );
        $cols['businessName'] = array(
            'label' => 'businessName',
            'id' => 'q.businessName',
            'method' => array('getBusinessName')
        );
        $cols['dateCreation'] = array(
            'label' => 'dateCreation',
            'id' => 'q.dateCreation',
            'method' => array('getDateCreation'),
            'filter' => array(array('name' => 'format', 'args' => array('d/m/Y')))
        );
        $cols['totalAmount'] = array(
            'label' => 'totalAmount',
            'id' => 'q.totalAmount',
            'method' => array('getTotalAmount')
        );
        $cols['followUpDate'] = array(
            'label' => 'followUpDate',
            'id' => 'q.followUps',
            'method' => [['getFollowUps', 0]]
        );
        $cols['followUpLastDate'] = array(
            'label' => 'followUpLastDate',
            'id' => 'q.followUps',
            'method' => [['getFollowUps', 0]]
        );
        $cols['followUpContent'] = array(
            'label' => 'followUpContent',
            'id' => 'q.followUps',
            'method' => [['getFollowUps', 0]]
        );
        $cols['followUps'] = array(
            'label' => 'followUps',
            'id' => 'q.followUps',
            'method' => [['getFollowUps', 0]]
        );
        $cols['customerLastName'] = array(
            'label' => 'customerLastName',
            'id' => 'q.lastName',
            'method' => array('getLastName')
        );
        $cols['id'] = array('label' => 'id', 'id' => 'q.id', 'method' => array('getId'));

        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder->select(array('q'))
            ->from('App:QuoteRequest', 'q')
            ->addSelect('fU')
            ->leftJoin('q.followUps', 'fU')
            ->where('q.deleted IS NULL')
            ->andWhere('q.userInCharge IN (:userIds)')
            ->setParameter('userIds', $userIds)
            ->andWhere('fU.status != :status')
            ->setParameter('status', 'CLOSED');

        if ($selectedCatalog) {
            $queryBuilder
                ->andWhere('q.catalog = :catalog')
                ->setParameter('catalog', $selectedCatalog);
        }

        if ($selectedStatus) {
            $queryBuilder
                ->andWhere('q.quoteStatus = :quoteStatus')
                ->setParameter('quoteStatus', $selectedStatus);
        }

        if ($selectedPrice1 !== null && $selectedPrice1 >= 0) {
            $selectedPrice1 = $this->numberManager->normalize((int)$selectedPrice1);

            $queryBuilder
                ->andWhere('q.totalAmount >= :price1')
                ->setParameter('price1', $selectedPrice1);
        }

        if ($selectedPrice2 !== null && $selectedPrice2 >= 0) {
            $selectedPrice2 = $this->numberManager->normalize((int)$selectedPrice2);

            $queryBuilder
                ->andWhere('q.totalAmount <= :price2')
                ->setParameter('price2', $selectedPrice2);
        }


        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            if (substr($search['value'], 0, 1) === '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('q.id', '?1')
                ))->setParameter(1, substr($search['value'], 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('q.id', '?1'),
                    $queryBuilder->expr()->like('q.number', '?1'),
                    $queryBuilder->expr()->like('q.businessName', '?1'),
                    $queryBuilder->expr()->like('q.totalAmount', '?1'),
                    $queryBuilder->expr()->like('q.dateCreation', '?1')
                ))->setParameter(1, '%' . $search['value'] . '%');
            }
        }

        $dt = $dataTable->generateTable($cols, $queryBuilder, $pageSize, $start, $orders, $columns, $filters,
            $paginator, $rowPrefix);

        // Reformatage de certaines données
        $tmp = [];
        foreach ($dt['data'] as $data) {
            $line = $data;

            $line['totalAmount'] = $this->numberManager->formatAmount($data['totalAmount'], null,
                $request->getLocale());

            if($line['userInCharge']){
                $line['userInCharge'] = $line['userInCharge']->getFirstName() . ' ' . $line['userInCharge']->getLastName();
            }

            $line['customer'] .= ' ' . $line['customerLastName'];

            if ($line['followUps']) {
                $line['followUpDate'] = null;
                $line['followUpLastDate'] = null;
                if ($line['followUps']->getDate()) {
                    $line['followUpDate'] = $line['followUps']->getDate()->format('d/m/Y');
                }
                if ($line['followUps']->getDateUpdate()) {
                    $line['followUpLastDate'] = $line['followUps']->getDateUpdate()->format('d/m/Y');
                }
                $line['followUpContent'] = $line['followUps']->getContent();
            }

            $tmp[] = $line;
        }
        $dt['data'] = $tmp;

        $return['recordsTotal'] = $dt['recordsTotal'];
        $return['recordsFiltered'] = $dt['recordsTotal'];
        $return['data'] = $dt['data'];
        $return['resultCode'] = 1;
        $return['resultDescription'] = "success";

        return new JsonResponse($return);

    }

    /**
     * @Route("/marketingReport/export", name="paprec_dashboard_marketing_report_export")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function exportMarketingReportAction(Request $request)
    {

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder->select(['qRL', 'q'])
            ->from('App:QuoteRequestLine', 'qRL')
            ->join('qRL.quoteRequest', 'q')
            ->where('q.deleted IS NULL')
            ->andWhere('qRL.deleted IS NULL');

        /** @var QuoteRequestLine[] $quoteRequestLines */
        $quoteRequestLines = $queryBuilder->getQuery()->getResult();

        /** @var Spreadsheet $spreadsheet */
        $spreadsheet = new Spreadsheet();

        $spreadsheet
            ->getProperties()->setCreator("Paprec Easy Recyclage")
            ->setTitle("Paprec Easy Recyclage - Rapport Marketing")
            ->setSubject("Extraction");

        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $sheet->setTitle('Devis');

        // Labels
        $sheetLabels = [
            'ID',
            'Agence',
            'Catalogue',
            'Année création',
            'Mois création',
            'Jour création',
            'Année dernière création',
            'Mois dernière création',
            'Jour dernière création',
            'Langue',
            'Commercial en charge',
            'Staff',
            'Numéro offre du devis',
            'Référence de l\'offre',
            'Statut',
            'Numéro client',
            'Nom société',
            'Adresse',
            'Code postal',
            'Ville',
            'Civilité',
            'Nom',
            'Prénom',
            'Email',
            'Téléphone',
            'Prestation multi-sites',
            'Accès',
            'Remarques',
            'Commentaire commercial',
            'Produit',
            'Quantité',
            'Transport/passage',
            'Fréquence de passage',
            'PU Location',
            'PU Collecte',
            'PU Traitement',
            'Budget mensuel Produit',
            'Frais de Gestion',
            'Date de fin de la prestation'
        ];

        $xAxe = 'A';
        foreach ($sheetLabels as $label) {
            $sheet->setCellValue($xAxe . 1, $label);
            $xAxe++;
        }

        $yAxe = 2;
        foreach ($quoteRequestLines as $quoteRequestLine) {

            $quoteRequest = $quoteRequestLine->getQuoteRequest();

            if ($quoteRequest) {
                $productName = '';
                if ($quoteRequestLine->getProduct()->getProductLabels() && is_iterable($quoteRequestLine->getProduct()->getProductLabels()) && count($quoteRequestLine->getProduct()->getProductLabels())) {
                    $productName = $quoteRequestLine->getProduct()->getProductLabels()[0]->getName();
                }

                $frequency = $this->translator->trans('General.Frequency.' . $quoteRequestLine->getFrequencyInterval());
                if ($quoteRequestLine->getFrequency() !== 'REGULAR') {
                    $frequency = $this->translator->trans('General.Frequency.' . $quoteRequestLine->getFrequency());
                }

                $isMultisite = 'Non';
                if ($quoteRequest->getIsMultisite() == 1) {
                    $isMultisite = 'Oui';
                }

                $userInCharge = '';
                $manager = '';
                $agency = '';
                if ($quoteRequest->getUserInCharge()) {
                    $userInCharge = $quoteRequest->getUserInCharge()->getFirstName() . " " . $quoteRequest->getUserInCharge()->getLastName();
                    if ($quoteRequest->getUserInCharge()->getAgency()) {
                        $agency = $quoteRequest->getUserInCharge()->getAgency()->getName();
                    }

                    if ($quoteRequest->getUserInCharge()->getManager()) {
                        $manager = $quoteRequest->getUserInCharge()->getManager()->getFirstName() . " " . $quoteRequest->getUserInCharge()->getManager()->getLastName();
                    }
                }

                $getters = [
                    $quoteRequest->getId(),
                    $agency,
                    $this->translator->trans('Commercial.QuoteRequest.Catalog.' . $quoteRequest->getCatalog()),
                    0 + $quoteRequest->getDateCreation()->format('Y'),
                    0 + $quoteRequest->getDateCreation()->format('m'),
                    0 + $quoteRequest->getDateCreation()->format('d'),
                    ($quoteRequest->getDateUpdate() ? 0 + $quoteRequest->getDateUpdate()->format('Y') : ''),
                    ($quoteRequest->getDateUpdate() ? 0 + $quoteRequest->getDateUpdate()->format('m') : ''),
                    ($quoteRequest->getDateUpdate() ? 0 + $quoteRequest->getDateUpdate()->format('d') : ''),
                    $quoteRequest->getLocale(),
                    $userInCharge,
                    $manager,
                    $quoteRequest->getNumber(),
                    $quoteRequest->getReference(),
                    $this->translator->trans('Commercial.QuoteStatusList.' . $quoteRequest->getQuoteStatus()),
                    $quoteRequest->getCustomerId(),
                    $quoteRequest->getBusinessName(),
                    $quoteRequest->getAddress(),
                    $quoteRequest->getPostalCode() ? $quoteRequest->getPostalCode()->getCode() : '',
                    $quoteRequest->getCity(),
                    $quoteRequest->getCivility(),
                    $quoteRequest->getLastName(),
                    $quoteRequest->getFirstName(),
                    $quoteRequest->getEmail(),
                    $quoteRequest->getPhone(),
                    $isMultisite,
                    $this->translator->trans('Commercial.AccessList.' . $quoteRequest->getAccess()),
                    $quoteRequest->getComment(),
                    $quoteRequest->getSalesmanComment(),
                    $quoteRequestLine->getProduct()->getCode() . ' - ' . $productName,
                    $quoteRequestLine->getQuantity(),
                    $quoteRequestLine->getFrequencyTimes(),
                    $frequency,
                    $this->productManager->calculatePriceByFieldName($quoteRequestLine, 'editableRentalUnitPrice'),
                    $this->productManager->calculatePriceByFieldName($quoteRequestLine, 'treatmentCollectPrice'),
                    $this->productManager->calculatePriceByFieldName($quoteRequestLine, 'editableTreatmentUnitPrice'),
//                    $this->productManager->calculatePriceByFieldName($quoteRequestLine, 'totalAmount'),
                    $this->numberManager->denormalize($quoteRequestLine->getTotalAmount()),
                    '',
                    $quoteRequest->getServiceEndDate() ? $quoteRequest->getServiceEndDate()->format('Y-m-d') : ''
                ];

                $xAxe = 'A';
                foreach ($getters as $getter) {
                    $sheet->setCellValue($xAxe . $yAxe, (string)$getter);
                    $xAxe++;
                }
                $yAxe++;
            }
        }


        // Resize columns
        for ($i = 'A'; $i != $sheet->getHighestDataColumn(); $i++) {
            $sheet->getColumnDimension($i)->setAutoSize(true);
        }

        $fileName = 'EasyRecyclageShop-Extraction-Rapport-Marketing-' . date('Y-m-d') . '.xlsx';

        $streamedResponse = new StreamedResponse();
        $streamedResponse->setCallback(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $streamedResponse->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $streamedResponse->headers->set('Pragma', 'public');
        $streamedResponse->headers->set('Cache-Control', 'maxage=1');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename=' . $fileName);

        return $streamedResponse->send();
    }

}
