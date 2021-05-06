<?php

namespace App\Controller;

use App\Entity\PostalCode;
use App\Form\PostalCodeType;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PostalCodeController extends Controller
{

    /**
     * @Route("/postalCode", name="paprec_postalCode_index")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction()
    {
        return $this->render('postalCode/index.html.twig');
    }

    /**
     * @Route("/postalCode/loadList", name="paprec_postalCode_loadList")
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

        $cols['id'] = array('label' => 'id', 'id' => 'pC.id', 'method' => array('getId'));
        $cols['code'] = array('label' => 'code', 'id' => 'pC.code', 'method' => array('getCode'));
        $cols['city'] = array('label' => 'city', 'id' => 'pC.city', 'method' => array('getCity'));
        $cols['agency'] = array('label' => 'agency', 'id' => 'pC.agency', 'method' => array('getAgency', 'getName'));
        $cols['zone'] = array('label' => 'zone', 'id' => 'pC.zone', 'method' => array('getZone'));

        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(PostalCode::class)->createQueryBuilder('pC');


        $queryBuilder->select(array('pC'))
            ->where('pC.deleted IS NULL');

        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            if (substr($search['value'], 0, 1) == '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('pC.id', '?1')
                ))->setParameter(1, substr($search['value'], 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('pC.code', '?1'),
                    $queryBuilder->expr()->like('pC.zone', '?1'),
                    $queryBuilder->expr()->like('pC.city', '?1')
                ))->setParameter(1, '%' . $search['value'] . '%');
            }
        }

        $datatable = $this->get('goondi_tools.datatable')->generateTable($cols, $queryBuilder, $pageSize, $start, $orders, $columns, $filters);


        $return['recordsTotal'] = $datatable['recordsTotal'];
        $return['recordsFiltered'] = $datatable['recordsTotal'];
        $return['data'] = $datatable['data'];
        $return['resultCode'] = 1;
        $return['resultDescription'] = "success";

        return new JsonResponse($return);

    }

    /**
     * @Route("/postalCode/export", name="paprec_postalCode_export")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function exportAction(Request $request)
    {
        $numberManager = $this->get('paprec_catalog.number_manager');

        $phpExcelObject = $this->container->get('phpexcel')->createPHPExcelObject();

        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(PostalCode::class)->createQueryBuilder('pC');

        $queryBuilder->select(array('pC'))
            ->where('pC.deleted IS NULL');

        $postalCodes = $queryBuilder->getQuery()->getResult();

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
        foreach ($postalCodes as $postalCode) {

            $phpExcelObject->setActiveSheetIndex(0)
                ->setCellValue('A' . $i, $postalCode->getId())
                ->setCellValue('B' . $i, $postalCode->getCode())
                ->setCellValue('C' . $i, $postalCode->getCity())
                ->setCellValue('D' . $i, $postalCode->getZone())
                ->setCellValue('E' . $i, $numberManager->denormalize15($postalCode->getRentalRate()))
                ->setCellValue('F' . $i, $numberManager->denormalize15($postalCode->getTransportRate()))
                ->setCellValue('G' . $i, $numberManager->denormalize15($postalCode->getTreatmentRate()))
                ->setCellValue('H' . $i, $numberManager->denormalize15($postalCode->getTraceabilityRate()))
                ->setCellValue('I' . $i, ($postalCode->getUserInCharge()) ? $postalCode->getUserInCharge()->getEmail() : '');
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
     * @Route("/postalCode/view/{id}", name="paprec_postalCode_view")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function viewAction(Request $request, PostalCode $postalCode)
    {
        $postalCodeManager = $this->get('paprec_catalog.postal_code_manager');
        $postalCodeManager->isDeleted($postalCode, true);

        return $this->render('postalCode/view.html.twig', array(
            'postalCode' => $postalCode
        ));
    }

    /**
     * @Route("/postalCode/add", name="paprec_postalCode_add")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addAction(Request $request)
    {
        $user = $this->getUser();

        $numberManager = $this->get('paprec_catalog.number_manager');

        $postalCode = new PostalCode();


        $form = $this->createForm(PostalCodeType::class, $postalCode);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $postalCode = $form->getData();
            $postalCode->setRentalRate($numberManager->normalize15($postalCode->getRentalRate()));
            $postalCode->setTransportRate($numberManager->normalize15($postalCode->getTransportRate()));
            $postalCode->setTreatmentRate($numberManager->normalize15($postalCode->getTreatmentRate()));
            $postalCode->setTraceabilityRate($numberManager->normalize15($postalCode->getTraceabilityRate()));

            $postalCode->setDateCreation(new \DateTime);
            $postalCode->setUserCreation($user);

            $em = $this->getDoctrine()->getManager();
            $em->persist($postalCode);
            $em->flush();

            return $this->redirectToRoute('paprec_postalCode_view', array(
                'id' => $postalCode->getId()
            ));

        }

        return $this->render('postalCode/add.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/postalCode/edit/{id}", name="paprec_postalCode_edit")
     * @Security("has_role('ROLE_ADMIN')")
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function editAction(Request $request, PostalCode $postalCode)
    {
        $user = $this->getUser();

        $numberManager = $this->get('paprec_catalog.number_manager');
        $postalCodeManager = $this->get('paprec_catalog.postal_code_manager');
        $postalCodeManager->isDeleted($postalCode, true);

        $postalCode->setRentalRate($numberManager->denormalize15($postalCode->getRentalRate()));
        $postalCode->setTransportRate($numberManager->denormalize15($postalCode->getTransportRate()));
        $postalCode->setTreatmentRate($numberManager->denormalize15($postalCode->getTreatmentRate()));
        $postalCode->setTraceabilityRate($numberManager->denormalize15($postalCode->getTraceabilityRate()));

        $form = $this->createForm(PostalCodeType::class, $postalCode);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $postalCode = $form->getData();

            $postalCode->setRentalRate($numberManager->normalize15($postalCode->getRentalRate()));
            $postalCode->setTransportRate($numberManager->normalize15($postalCode->getTransportRate()));
            $postalCode->setTreatmentRate($numberManager->normalize15($postalCode->getTreatmentRate()));
            $postalCode->setTraceabilityRate($numberManager->normalize15($postalCode->getTraceabilityRate()));

            $postalCode->setDateUpdate(new \DateTime);
            $postalCode->setUserUpdate($user);

            $em = $this->getDoctrine()->getManager();
            $em->flush();
            
            return $this->redirectToRoute('paprec_postalCode_view', array(
                'id' => $postalCode->getId()
            ));

        }
        
        return $this->render('postalCode/edit.html.twig', array(
            'form' => $form->createView(),
            'postalCode' => $postalCode
        ));
    }

    /**
     * @Route("/postalCode/remove/{id}", name="paprec_postalCode_remove")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removeAction(Request $request, PostalCode $postalCode)
    {
        $em = $this->getDoctrine()->getManager();

        $postalCode->setDeleted(new \DateTime());
        $em->flush();

        return $this->redirectToRoute('paprec_postalCode_index');
    }

    /**
     * @Route("/postalCode/removeMany/{ids}", name="paprec_postalCode_removeMany")
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
            $postalCodes = $em->getRepository('App:PostalCode')->findById($ids);
            foreach ($postalCodes as $postalCode) {
                $postalCode->setDeleted(new \DateTime);
            }
            $em->flush();
        }

        return $this->redirectToRoute('paprec_postalCode_index');
    }

    /**
     * @Route("/postalCode/autocomplete", name="paprec_postalCode_autocomplete")
     * @throws \Exception
     */
    public function autocompleteAction(Request $request)
    {
        $codes = array();
        $code = trim(strip_tags($request->get('term')));

        $postalCodeManager = $this->get('paprec_catalog.postal_code_manager');

        $entities = $postalCodeManager->getActivesFromCode($code);

        foreach ($entities as $entity) {
            $codes[] = $entity->getCode() . ' - ' . $entity->getCity();
        }

        $response = new JsonResponse();
        $response->setData($codes);

        return $response;
    }

}
