<?php

namespace App\Controller;

use App\Entity\Agency;
use App\Form\AgencyType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AgencyController extends Controller
{

    /**
     * @Route("/agency", name="paprec_agency_index")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction()
    {
        return $this->render('agency/index.html.twig');
    }

    /**
     * @Route("/agency/loadList", name="paprec_agency_loadList")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function loadListAction(Request $request)
    {
        $numberManager = $this->get('paprec_catalog.number_manager');

        $return = array();

        $filters = $request->get('filters');
        $pageSize = $request->get('length');
        $start = $request->get('start');
        $orders = $request->get('order');
        $search = $request->get('search');
        $columns = $request->get('columns');

        $cols['id'] = array('label' => 'id', 'id' => 'a.id', 'method' => array('getId'));
        $cols['name'] = array('label' => 'name', 'id' => 'a.code', 'method' => array('getName'));
        $cols['salesman'] = array('label' => 'salesman', 'id' => 'a.name', 'method' => array('getSalesman', 'getFullName'));
        $cols['assistant'] = array('label' => 'assistant', 'id' => 'a.name', 'method' => array('getAssistant', 'getFullName'));

        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(Agency::class)->createQueryBuilder('a');


        $queryBuilder->select(array('a'))
            ->where('a.deleted IS NULL');

        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            if (substr($search['value'], 0, 1) == '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('a.id', '?1')
                ))->setParameter(1, substr($search['value'], 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('a.name', '?1')
                ))->setParameter(1, '%' . $search['value'] . '%');
            }
        }

        $datatable = $this->get('goondi_tools.datatable')->generateTable($cols, $queryBuilder, $pageSize, $start,
            $orders, $columns, $filters);


        $return['recordsTotal'] = $datatable['recordsTotal'];
        $return['recordsFiltered'] = $datatable['recordsTotal'];
        $return['data'] = $datatable['data'];
        $return['resultCode'] = 1;
        $return['resultDescription'] = "success";

        return new JsonResponse($return);

    }

    /**
     * @Route("/agency/export", name="paprec_agency_export")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function exportAction(Request $request)
    {
        $numberManager = $this->get('paprec_catalog.number_manager');

        $phpExcelObject = $this->container->get('phpexcel')->createPHPExcelObject();

        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(Agency::class)->createQueryBuilder('a');

        $queryBuilder->select(array('a'))
            ->where('a.deleted IS NULL');

        $agencies = $queryBuilder->getQuery()->getResult();

        $phpExcelObject->getProperties()->setCreator("Privacia Shop")
            ->setLastModifiedBy("Privacia Shop")
            ->setTitle("Privacia Shop - Postal codes")
            ->setSubject("Extract");

        $phpExcelObject->setActiveSheetIndex(0)
            ->setCellValue('A1', 'ID')
            ->setCellValue('B1', 'Code')
            ->setCellValue('C1', 'Commune')
            ->setCellValue('D1', 'Tariff zone')
            ->setCellValue('E1', 'Rental rate')
            ->setCellValue('F1', 'Transport rate')
            ->setCellValue('G1', 'Treatment rate')
            ->setCellValue('H1', 'Treacability rate')
            ->setCellValue('I1', 'Salesman in charge');

        $phpExcelObject->getActiveSheet()->setTitle('Postal codes');
        $phpExcelObject->setActiveSheetIndex(0);

        $i = 2;
        foreach ($agencies as $agency) {

            $phpExcelObject->setActiveSheetIndex(0)
                ->setCellValue('A' . $i, $agency->getId())
                ->setCellValue('B' . $i, $agency->getCode())
                ->setCellValue('C' . $i, $agency->getCity())
                ->setCellValue('D' . $i, $agency->getZone())
                ->setCellValue('E' . $i, $numberManager->denormalize15($agency->getRentalRate()))
                ->setCellValue('F' . $i, $numberManager->denormalize15($agency->getTransportRate()))
                ->setCellValue('G' . $i, $numberManager->denormalize15($agency->getTreatmentRate()))
                ->setCellValue('H' . $i, $numberManager->denormalize15($agency->getTraceabilityRate()))
                ->setCellValue('I' . $i, ($agency->getUserInCharge()) ? $agency->getUserInCharge()->getEmail() : '');
            $i++;
        }

        $writer = $this->container->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');

        $fileName = 'PrivaciaShop-Extract-Postal-Codes-' . date('Y-m-d') . '.xlsx';

        // create the response
        $response = $this->container->get('phpexcel')->createStreamedResponse($writer);

        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    /**
     * @Route("/agency/view/{id}", name="paprec_agency_view")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function viewAction(Request $request, Agency $agency)
    {
        $agencyManager = $this->get('paprec_catalog.agency_manager');
        $agencyManager->isDeleted($agency, true);

        return $this->render('agency/view.html.twig', array(
            'agency' => $agency
        ));
    }

    /**
     * @Route("/agency/add", name="paprec_agency_add")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addAction(Request $request)
    {
        $user = $this->getUser();

        $agency = new Agency();

        $form = $this->createForm(AgencyType::class, $agency);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $agency = $form->getData();

            $agency->setDateCreation(new \DateTime);
            $agency->setUserCreation($user);

            $em = $this->getDoctrine()->getManager();
            $em->persist($agency);
            $em->flush();

            return $this->redirectToRoute('paprec_agency_view', array(
                'id' => $agency->getId()
            ));

        }

        return $this->render('agency/add.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/agency/edit/{id}", name="paprec_agency_edit")
     * @Security("has_role('ROLE_ADMIN')")
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function editAction(Request $request, Agency $agency)
    {
        $user = $this->getUser();

        $agencyManager = $this->get('paprec_catalog.agency_manager');
        $agencyManager->isDeleted($agency, true);

        $form = $this->createForm(AgencyType::class, $agency);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $agency = $form->getData();

            $agency->setDateUpdate(new \DateTime);
            $agency->setUserUpdate($user);

            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->redirectToRoute('paprec_agency_view', array(
                'id' => $agency->getId()
            ));

        }

        return $this->render('agency/edit.html.twig', array(
            'form' => $form->createView(),
            'agency' => $agency
        ));
    }

    /**
     * @Route("/agency/remove/{id}", name="paprec_agency_remove")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removeAction(Request $request, Agency $agency)
    {
        $em = $this->getDoctrine()->getManager();

        $agency->setDeleted(new \DateTime());
        $em->flush();

        return $this->redirectToRoute('paprec_agency_index');
    }

    /**
     * @Route("/agency/removeMany/{ids}", name="paprec_agency_removeMany")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removeManyAction(Request $request)
    {
        $ids = $request->get('ids');

        if (!$ids) {
            throw new NotFoundHttpException();
        }

        $em = $this->getDoctrine()->getManager();

        $ids = explode(',', $ids);

        if (is_array($ids) && count($ids)) {
            $agencies = $em->getRepository('App:Agency')->findById($ids);
            foreach ($agencies as $agency) {
                $agency->setDeleted(new \DateTime);
            }
            $em->flush();
        }

        return $this->redirectToRoute('paprec_agency_index');
    }

}
