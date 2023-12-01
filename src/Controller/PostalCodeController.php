<?php

namespace App\Controller;

use App\Entity\PostalCode;
use App\Form\PostalCodeType;
use App\Service\NumberManager;
use App\Service\PostalCodeManager;
use App\Service\ProductLabelManager;
use App\Service\ProductManager;
use App\Tools\DataTable;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class PostalCodeController extends AbstractController
{

    private $em;
    private $numberManager;
    private $postalCodeManager;
    private $translator;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        NumberManager $numberManager,
        PostalCodeManager $postalCodeManager
    ) {
        $this->em = $em;
        $this->numberManager = $numberManager;
        $this->postalCodeManager = $postalCodeManager;
        $this->translator = $translator;
    }


    /**
     *@Route("", name="paprec_postalCode_index")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction()
    {
        return $this->render('postalCode/index.html.twig');
    }

    /**
     *@Route("/loadList", name="paprec_postalCode_loadList")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function loadListAction(Request $request, DataTable $dataTable, PaginatorInterface $paginator)
    {
        $return = [];

        $filters = $request->get('filters');
        $pageSize = $request->get('length');
        $start = $request->get('start');
        $orders = $request->get('order');
        $search = $request->get('search');
        $columns = $request->get('columns');
        $rowPrefix = $request->get('rowPrefix');

        $cols['id'] = array('label' => 'id', 'id' => 'pC.id', 'method' => array('getId'));
        $cols['code'] = array('label' => 'code', 'id' => 'pC.code', 'method' => array('getCode'));
        $cols['city'] = array('label' => 'city', 'id' => 'pC.city', 'method' => array('getCity'));
        $cols['agency'] = array('label' => 'agency', 'id' => 'pC.agency', 'method' => array('getAgency', 'getName'));

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

        $dt = $dataTable->generateTable($cols, $queryBuilder, $pageSize, $start, $orders, $columns, $filters,
            $paginator, $rowPrefix);


        $return['recordsTotal'] = $dt['recordsTotal'];
        $return['recordsFiltered'] = $dt['recordsTotal'];
        $return['data'] = $dt['data'];
        $return['resultCode'] = 1;
        $return['resultDescription'] = "success";

        return new JsonResponse($return);

    }

    /**
     *@Route("/export", name="paprec_postalCode_export")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function exportAction(Request $request)
    {
        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(PostalCode::class)->createQueryBuilder('pC');

        $queryBuilder->select(array('pC'))
            ->addSelect('a')
            ->leftJoin('pC.agency', 'a')
            ->addSelect('u')
            ->leftJoin('pC.userInCharge', 'u')
            ->where('pC.deleted IS NULL');

        $postalCodes = $queryBuilder->getQuery()->getResult();

        /** @var Spreadsheet $spreadsheet */
        $spreadsheet = new Spreadsheet();

        $spreadsheet
            ->getProperties()->setCreator("EasyRecyclageShop")
            ->setLastModifiedBy("EasyRecyclageShop")
            ->setTitle("EasyRecyclageShop - Postal codes")
            ->setSubject("Extract");

        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $sheet->setTitle('Postal Codes');

        $sheet
            ->setCellValue('A1', $this->translator->trans('Catalog.PostalCode.Name'))
            ->setCellValue('B1', $this->translator->trans('Catalog.PostalCode.City'))
            ->setCellValue('C1', $this->translator->trans('Catalog.PostalCode.Agency'))
            ->setCellValue('D1', $this->translator->trans('Catalog.PostalCode.UserInCharge'))
            ->setCellValue('E1', $this->translator->trans('Catalog.PostalCode.RentalRate'))
            ->setCellValue('F1', $this->translator->trans('Catalog.PostalCode.CbrRegTransportRate'))
            ->setCellValue('G1', $this->translator->trans('Catalog.PostalCode.CbrPonctTransportRate'))
            ->setCellValue('H1', $this->translator->trans('Catalog.PostalCode.VlPlCfsRegTransportRate'))
            ->setCellValue('I1', $this->translator->trans('Catalog.PostalCode.VlPlCfsPonctTransportRate'))
            ->setCellValue('J1', $this->translator->trans('Catalog.PostalCode.VlPlTransportRate'))
            ->setCellValue('K1', $this->translator->trans('Catalog.PostalCode.BomTransportRate'))
            ->setCellValue('L1', $this->translator->trans('Catalog.PostalCode.PlPonctTransportRate'))
            ->setCellValue('M1', $this->translator->trans('Catalog.PostalCode.TreatmentRate'))
            ->setCellValue('N1', $this->translator->trans('Catalog.PostalCode.TraceabilityRate'));

        $i = 2;
        foreach ($postalCodes as $postalCode) {

            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue('A' . $i, $postalCode->getCode())
                ->setCellValue('B' . $i, $postalCode->getCity())
                ->setCellValue('C' . $i, $postalCode->getAgency()->getName())
                ->setCellValue('D' . $i, ($postalCode->getUserInCharge()) ? $postalCode->getUserInCharge()->getEmail() : '')
                ->setCellValue('E' . $i, $postalCode->getZone())
                ->setCellValue('F' . $i, $this->numberManager->denormalize15($postalCode->getCbrRegTransportRate()))
                ->setCellValue('G' . $i, $this->numberManager->denormalize15($postalCode->getCbrPonctTransportRate()))
                ->setCellValue('H' . $i, $this->numberManager->denormalize15($postalCode->getVlPlCfsRegTransportRate()))
                ->setCellValue('I' . $i, $this->numberManager->denormalize15($postalCode->getVlPlCfsPonctTransportRate()))
                ->setCellValue('J' . $i, $this->numberManager->denormalize15($postalCode->getVlPlTransportRate()))
                ->setCellValue('K' . $i, $this->numberManager->denormalize15($postalCode->getBomTransportRate()))
                ->setCellValue('L' . $i, $this->numberManager->denormalize15($postalCode->getPlPonctTransportRate()))
                ->setCellValue('M' . $i, $this->numberManager->denormalize15($postalCode->getTreatmentRate()))
                ->setCellValue('N' . $i, $this->numberManager->denormalize15($postalCode->getTraceabilityRate()));
            $i++;
        }


        $fileName = 'EasyRecyclageShop-Extract-Postal-Codes-' . date('Y-m-d') . '.xlsx';

        $streamedResponse = new StreamedResponse();
        $streamedResponse->setCallback(function () use ($spreadsheet) {
            $writer =  new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $streamedResponse->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $streamedResponse->headers->set('Pragma', 'public');
        $streamedResponse->headers->set('Cache-Control', 'maxage=1');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename=' . $fileName);

        return $streamedResponse->send();

    }

    /**
     * @Route("/import", name="paprec_postalCode_import")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function importAction(Request $request)
    {
        try {
            $phpPath = $this->getParameter('paprec.php_path');
            $projectPath = $this->getParameter('paprec.project_path');
            $commandName = 'egd:update-postal-code';
            $file = $request->files->get('file');
            $filePath = $file->getPathName();

//            dd(shell_exec($phpPath . ' ' . $projectPath . 'bin/console' . ' ' . $commandName . ' ' . $filePath . ' 2>&1'));
            shell_exec($phpPath . ' ' . $projectPath . 'bin/console' . ' ' . $commandName . ' ' . $filePath);

//            return new JsonResponse([
//                'resultCode' => 1,
//                'resultMessage' => 'Success'
//            ]);

            return $this->redirectToRoute('paprec_postalCode_index');

        } catch (\Exception $e) {
            return new JsonResponse([
                'resultCode' => 0,
                'resultMessage' => $e->getCode() . ' : ' . $e->getMessage()
            ]);
        }

    }

    /**
     *@Route("/view/{id}", name="paprec_postalCode_view")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function viewAction(Request $request, PostalCode $postalCode)
    {
        $this->postalCodeManager->isDeleted($postalCode, true);

        return $this->render('postalCode/view.html.twig', array(
            'postalCode' => $postalCode
        ));
    }

    /**
     *@Route("/add", name="paprec_postalCode_add")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addAction(Request $request)
    {
        $user = $this->getUser();

        $postalCode = new PostalCode();

        $form = $this->createForm(PostalCodeType::class, $postalCode);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            $postalCode = $form->getData();

            $postalCode->setRentalRate($this->numberManager->normalize15($postalCode->getRentalRate()));

            $postalCode->setCbrRegTransportRate($this->numberManager->normalize15($postalCode->getCbrRegTransportRate()));
            $postalCode->setCbrPonctTransportRate($this->numberManager->normalize15($postalCode->getCbrPonctTransportRate()));
            $postalCode->setVlPlCfsRegTransportRate($this->numberManager->normalize15($postalCode->getVlPlCfsRegTransportRate()));
            $postalCode->setVlPlCfsPonctTransportRate($this->numberManager->normalize15($postalCode->getVlPlCfsPonctTransportRate()));
            $postalCode->setVlPlTransportRate($this->numberManager->normalize15($postalCode->getVlPlTransportRate()));
            $postalCode->setBomTransportRate($this->numberManager->normalize15($postalCode->getBomTransportRate()));
            $postalCode->setPlPonctTransportRate($this->numberManager->normalize15($postalCode->getPlPonctTransportRate()));
            $postalCode->setZone(1);

//            $postalCode->setCBroyeurTransportRate($this->numberManager->normalize15($postalCode->getCBroyeurTransportRate()));
//            $postalCode->setFourgonPLTransportRate($this->numberManager->normalize15($postalCode->getFourgonPLTransportRate()));
//            $postalCode->setFourgonVLTransportRate($this->numberManager->normalize15($postalCode->getFourgonVLTransportRate()));
//            $postalCode->setAmplirollTransportRate($this->numberManager->normalize15($postalCode->getAmplirollTransportRate()));
//            $postalCode->setBomTransportRate($this->numberManager->normalize15($postalCode->getBomTransportRate()));
//            $postalCode->setLivraisonTransportRate($this->numberManager->normalize15($postalCode->getLivraisonTransportRate()));

            $postalCode->setTreatmentRate($this->numberManager->normalize15($postalCode->getTreatmentRate()));
            $postalCode->setTraceabilityRate($this->numberManager->normalize15($postalCode->getTraceabilityRate()));

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
     *@Route("/edit/{id}", name="paprec_postalCode_edit")
     * @Security("has_role('ROLE_ADMIN')")
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function editAction(Request $request, PostalCode $postalCode)
    {
        $user = $this->getUser();

        $this->postalCodeManager->isDeleted($postalCode, true);

        $postalCode->setRentalRate($this->numberManager->denormalize15($postalCode->getRentalRate()));
        $postalCode->setBomTransportRate($this->numberManager->denormalize15($postalCode->getBomTransportRate()));
        $postalCode->setTreatmentRate($this->numberManager->denormalize15($postalCode->getTreatmentRate()));
        $postalCode->setTraceabilityRate($this->numberManager->denormalize15($postalCode->getTraceabilityRate()));

        $postalCode->setCbrRegTransportRate($this->numberManager->denormalize15($postalCode->getCbrRegTransportRate()));
        $postalCode->setCbrPonctTransportRate($this->numberManager->denormalize15($postalCode->getCbrPonctTransportRate()));
        $postalCode->setVlPlCfsRegTransportRate($this->numberManager->denormalize15($postalCode->getVlPlCfsRegTransportRate()));
        $postalCode->setVlPlCfsPonctTransportRate($this->numberManager->denormalize15($postalCode->getVlPlCfsPonctTransportRate()));
        $postalCode->setVlPlTransportRate($this->numberManager->denormalize15($postalCode->getVlPlTransportRate()));
        $postalCode->setPlPonctTransportRate($this->numberManager->denormalize15($postalCode->getPlPonctTransportRate()));

        $form = $this->createForm(PostalCodeType::class, $postalCode);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $postalCode = $form->getData();

            $postalCode->setRentalRate($this->numberManager->normalize15($postalCode->getRentalRate()));
            $postalCode->setTreatmentRate($this->numberManager->normalize15($postalCode->getTreatmentRate()));
            $postalCode->setTraceabilityRate($this->numberManager->normalize15($postalCode->getTraceabilityRate()));

            $postalCode->setCbrRegTransportRate($this->numberManager->normalize15($postalCode->getCbrRegTransportRate()));
            $postalCode->setCbrPonctTransportRate($this->numberManager->normalize15($postalCode->getCbrPonctTransportRate()));
            $postalCode->setVlPlCfsRegTransportRate($this->numberManager->normalize15($postalCode->getVlPlCfsRegTransportRate()));
            $postalCode->setVlPlCfsPonctTransportRate($this->numberManager->normalize15($postalCode->getVlPlCfsPonctTransportRate()));
            $postalCode->setVlPlTransportRate($this->numberManager->normalize15($postalCode->getVlPlTransportRate()));
            $postalCode->setBomTransportRate($this->numberManager->normalize15($postalCode->getBomTransportRate()));
            $postalCode->setPlPonctTransportRate($this->numberManager->normalize15($postalCode->getPlPonctTransportRate()));

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
     *@Route("/remove/{id}", name="paprec_postalCode_remove")
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
     *@Route("/removeMany/{ids}", name="paprec_postalCode_removeMany")
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

        return new JsonResponse([
            'resultCode' => 1
        ]);
    }

    /**
     *@Route("/autocomplete", name="paprec_postalCode_autocomplete")
     * @throws \Exception
     */
    public function autocompleteAction(Request $request)
    {
        $codes = array();
        $code = trim(strip_tags($request->get('term')));

        $entities = $this->postalCodeManager->getActivesFromCode($code);

        foreach ($entities as $entity) {
            $codes[] = $entity->getCode() . ' - ' . $entity->getCity();
        }

        $response = new JsonResponse();
        $response->setData($codes);

        return $response;
    }

}
