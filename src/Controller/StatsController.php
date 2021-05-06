<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class StatsController extends Controller
{
    /**
     * @Route("/stats", name="paprec_stats_index")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL')")
     */
    public function indexAction(Request $request)
    {
        $totalQuoteStatus = array();
        $dateStart = $request->get('dateStart');
        $dateEnd = $request->get('dateEnd');
        $dateStartSql = null;
        $dateEndSql = null;

        if (!empty($dateStart)) {
            if ($dateStart !== '0') {
                $dateStartSql = join('-', array_reverse(explode('/', $dateStart)));
            }
        }
        if (!empty($dateEnd)) {
            if ($dateEnd !== '0') {
                $dateEndSql = join('-', array_reverse(explode('/', $dateEnd)));
            }
        }

        /**
         * Récupération des statuts possibles des devis et commandes
         */
        $quoteStatusList = $this->getParameter('paprec_quote_status');

        /**
         * Calcul des totaux de chaque tableau qu'importe le statut
         */
        $totalQuoteStatus['total'] = $this->getQuoteStats( $dateStart, $dateEnd);

        if (is_array($quoteStatusList) && count($quoteStatusList)) {
            foreach ($quoteStatusList as $status) {
                $totalQuoteStatus[$status] = $this->getQuoteStats( $dateStart, $dateEnd, $totalQuoteStatus['total'], $status);
            }
        }


        return $this->render('@PaprecCommercial/Stats/index.html.twig', array(
            'quoteStatusList' => $quoteStatusList,
            'totalQuoteStatus' => $totalQuoteStatus,
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
            'dateStartSql' => $dateStartSql,
            'dateEndSql' => $dateEndSql
        ));
    }


    /**
     * Fonction retournant dans un tableau les stats sur les devis en fonction de la division et du statut
     * Le tableau retourné correspond à une colonne d'un tableau dans le HTML
     * Si on ne renseigne pas de $total et de $status, alors la fonction retourne les stats totales sur les devis de la division qu'importe le statut du devis
     *
     * @param $division
     * @param null $total
     * @param null $status
     * @return array
     */
    private function getQuoteStats($dateStart = null, $dateEnd = null, $total = null, $status = null)
    {
        /**
         * On formate les dates qui sont au format jj/mm/aaaa pour pouvoir les utiliser en SQL
         */
        if ($dateStart && !empty($dateStart)) {
            $dateStart = join('-', array_reverse(explode('/', $dateStart)));
        }
        if ($dateEnd && !empty($dateEnd)) {
            $dateEnd = join('-', array_reverse(explode('/', $dateEnd)));
        }


        $numberManager = $this->get('paprec_catalog.number_manager');

        $quoteStats = array();
        /**
         * Nombre de devis
         */
        $sql = "SELECT COUNT(*) as nbQuote FROM quoteRequests q WHERE q.deleted IS NULL";
        if ($status != null) {
            $sql .= " AND q.quoteStatus = '" . $status . "'";
        }
        if ($dateStart && !empty($dateStart) && $dateEnd && !empty($dateEnd)) {
            $sql .= " AND q.dateCreation BETWEEN '" . $dateStart . "' AND '" . $dateEnd . "'";
        }
        $result = $this->executeSQL($sql);
        $quoteStats['nbQuote'] = $result[0]['nbQuote'];

        /**
         * Montant total
         */
        $sql = "SELECT SUM(COALESCE(q.totalAmount, 0)) as totalAmount FROM quoteRequests q WHERE q.deleted IS NULL";
        if ($status != null) {
            $sql .= " AND q.quoteStatus = '" . $status . "'";
        }
        if ($dateStart && !empty($dateStart) && $dateEnd && !empty($dateEnd)) {
            $sql .= " AND q.dateCreation BETWEEN '" . $dateStart . "' AND '" . $dateEnd . "'";
        }
        $result = $this->executeSQL($sql);
        $quoteStats['totalAmountFloat'] = $numberManager->denormalize($result[0]['totalAmount']);
        $quoteStats['totalAmount'] = number_format($numberManager->denormalize($result[0]['totalAmount']), 2, ',', ' ');

        /**
         * Montant total moyen
         */
        if ($quoteStats['nbQuote']) {
            $quoteStats['averageTotalAmount'] = number_format($quoteStats['totalAmountFloat'] / $quoteStats['nbQuote'], 2, ',', ' ');
        }

//        /**
//         * CA généré
//         */
//        $sql = "SELECT SUM(COALESCE(p.generatedTurnover, 0)) as generatedTurnover FROM product" . $division . "Quotes p WHERE p.deleted IS NULL";
//        if ($status != null) {
//            $sql .= " AND p.quoteStatus = '" . $status . "'";
//        }
//        if ($dateStart && !empty($dateStart) && $dateEnd && !empty($dateEnd)) {
//            $sql .= " AND p.dateCreation BETWEEN '" . $dateStart . "' AND '" . $dateEnd . "'";
//        }
//        $result = $this->executeSQL($sql);
//        $quoteStats['generatedTurnoverFloat'] = $numberManager->denormalize($result[0]['generatedTurnover']);
//        $quoteStats['generatedTurnover'] = number_format($numberManager->denormalize($result[0]['generatedTurnover']), 2, ',', ' ');
//
//        /**
//         * CA généré moyen
//         */
//        if ($quoteStats['nbQuote']) {
//            $quoteStats['averageGeneratedTurnover'] = number_format($quoteStats['generatedTurnoverFloat'] / $quoteStats['nbQuote'], 2, ',', ' ');
//        }
//
//        /**
//         * % en nombre
//         */
//        $quoteStats['percentByNumber'] = 0;
//        if ($quoteStats['nbQuote']) {
//            if ($total['nbQuote']) {
//                if ($total['nbQuote'] !== 0) {
//                    $quoteStats['percentByNumber'] = number_format(round($quoteStats['nbQuote'] * 100 / $total['nbQuote'], 2), 2, ',', ' ');
//                }
//            } else {
//                $quoteStats['percentByNumber'] = 100;
//            }
//        }
//
//        /**
//         * % en CA
//         */
//        $quoteStats['percentByTurnover'] = 0;
//        if ($quoteStats['generatedTurnoverFloat']) {
//            if ($total['generatedTurnoverFloat']) {
//                if ($total['generatedTurnoverFloat'] !== 0) {
//                    $quoteStats['percentByTurnover'] = number_format(round($quoteStats['generatedTurnoverFloat'] * 100 / $total['generatedTurnoverFloat'], 2), 2, ',', ' ');
//                }
//            } else {
//                $quoteStats['percentByTurnover'] = 100;
//            }
//        }

        return $quoteStats;
    }

    /**
     * Execute une requete SQL avec le connecteur PDO et retourne les résultats dans un tableau
     *
     * @param $sql
     * @return mixed
     */
    private function executeSQL($sql)
    {
        $em = $this->getDoctrine()->getManager();
        $conn = $em->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $result = $stmt->fetchAll();
    }

    /**
     *
     * Fonction retournant dans un tableau les stats sur les commandes en fonction de la division et du statut
     * Le tableau retourné correspond à une colonne d'un tableau dans le HTML
     * Si on ne renseigne pas de $total et de $status, alors la fonction retourne les stats totales sur les commandes de la division qu'importe le statut de celles-ci
     *
     * @param $division
     * @param null $total
     * @param null $status
     * @return array
     */
    private function getOrderStats($division, $dateStart = null, $dateEnd = null, $total = null, $status = null)
    {
        /**
         * On formate les dates qui sont au format jj/mm/aaaa pour pouvoir les utiliser en SQL
         */
        if ($dateStart && !empty($dateStart)) {
            $dateStart = join('-', array_reverse(explode('/', $dateStart)));
        }
        if ($dateEnd && !empty($dateEnd)) {
            $dateEnd = join('-', array_reverse(explode('/', $dateEnd)));
        }

        $numberManager = $this->get('paprec_catalog.number_manager');

        $orderStats = array();
        /**
         * Nombre de commandes
         */
        $sql = "SELECT COUNT(*) as nbOrder FROM product" . $division . "Orders p WHERE p.deleted IS NULL";
        if ($status != null) {
            $sql .= " AND p.orderStatus = '" . $status . "'";
        }
        if ($dateStart && !empty($dateStart) && $dateEnd && !empty($dateEnd)) {
            $sql .= " AND p.dateCreation BETWEEN '" . $dateStart . "' AND '" . $dateEnd . "'";
        }
        $result = $this->executeSQL($sql);
        $orderStats['nbOrder'] = $result[0]['nbOrder'];

        /**
         * Montant total
         */
        $sql = "SELECT SUM(COALESCE(p.totalAmount, 0)) as totalAmount FROM product" . $division . "Orders p WHERE p.deleted IS NULL";
        if ($status != null) {
            $sql .= " AND p.orderStatus = '" . $status . "'";
        }
        if ($dateStart && !empty($dateStart) && $dateEnd && !empty($dateEnd)) {
            $sql .= " AND p.dateCreation BETWEEN '" . $dateStart . "' AND '" . $dateEnd . "'";
        }
        $result = $this->executeSQL($sql);
        $orderStats['totalAmountFloat'] = $numberManager->denormalize($result[0]['totalAmount']);
        $orderStats['totalAmount'] = number_format($numberManager->denormalize($result[0]['totalAmount']), 2, ',', ' ');

        /**
         * Montant total moyen
         */
        if ($orderStats['nbOrder']) {
            $orderStats['averageTotalAmount'] = number_format($orderStats['totalAmountFloat'] / $orderStats['nbOrder'], 2, ',', ' ');
        }

        /**
         * % en nombre
         */
        $orderStats['percentByNumber'] = 0;
        if ($orderStats['nbOrder']) {
            if ($total['nbOrder']) {
                if ($total['nbOrder'] !== 0) {
                    $orderStats['percentByNumber'] = number_format(round($orderStats['nbOrder'] * 100 / $total['nbOrder'], 2), 2, ',', ' ');
                }
            } else {
                $orderStats['percentByNumber'] = 100;
            }
        }

        /**
         * % en CA
         */
        $orderStats['percentByTurnover'] = 0;
        if ($orderStats['totalAmountFloat']) {
            if ($total['totalAmountFloat']) {
                if ($total['totalAmountFloat'] !== 0) {
                    $orderStats['percentByTurnover'] = number_format(round($orderStats['totalAmountFloat'] * 100 / $total['totalAmountFloat'], 2), 2, ',', ' ');
                }
            } else {
                $orderStats['percentByTurnover'] = 100;
            }
        }

        return $orderStats;
    }

    /**
     * En fonction du type passsé, redirige vers la fonction exportAction() des controllers de devis ou de commande
     * Exporte uniquement les devis/commandes répondant aux critères de status et de dates si non nulls
     *
     * @Route("/stats/export/{type}/{status}/{dateStart}/{dateEnd}", defaults={"status"=null, "dateEnd"=null, "dateStart"=null}, name="paprec_stats_export")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_DIVISION')")
     */
    public function exportAction($type, $status, $dateStart, $dateEnd)
    {
        if (!empty($dateStart)) {
            if ($dateStart === '0') {
                $dateStart = null;
            } else {
                $dateStart = join('-', array_reverse(explode('/', $dateStart)));
            }
        }
        if (!empty($dateEnd)) {
            if ($dateEnd === '0') {
                $dateEnd = null;
            } else {
                $dateEnd = join('-', array_reverse(explode('/', $dateEnd)));
            }
        }

        return $this->redirectToRoute('paprec_' . $type . '_export', array(
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
            'status' => $status
        ));
    }

}
