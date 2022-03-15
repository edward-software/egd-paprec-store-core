<?php

namespace App\Controller;

use App\Entity\Agency;
use App\Entity\Picture;
use App\Form\AgencyType;
use App\Form\PictureAgencyType;
use App\Service\AgencyManager;
use App\Service\NumberManager;
use App\Service\PictureManager;
use App\Tools\DataTable;
use Doctrine\ORM\EntityManagerInterface;
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

class AgencyController extends AbstractController
{

    private $em;
    private $numberManager;
    private $pictureManager;
    private $agencyManager;
    private $translator;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        NumberManager $numberManager,
        PictureManager $pictureManager,
        AgencyManager $agencyManager
    ) {
        $this->em = $em;
        $this->numberManager = $numberManager;
        $this->pictureManager = $pictureManager;
        $this->agencyManager = $agencyManager;
        $this->translator = $translator;
    }

    /**
     * @Route("", name="paprec_agency_index")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction()
    {
        return $this->render('agency/index.html.twig');
    }

    /**
     * @Route("/loadList", name="paprec_agency_loadList")
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

        $cols['id'] = array('label' => 'id', 'id' => 'a.id', 'method' => array('getId'));
        $cols['name'] = array('label' => 'name', 'id' => 'a.name', 'method' => array('getName'));
        $cols['businessName'] = array('label' => 'businessName', 'id' => 'a.businessName', 'method' => array('getBusinessName'));
        $cols['businessId'] = array('label' => 'businessId', 'id' => 'a.businessId', 'method' => array('getBusinessId'));

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
     * @Route("/export", name="paprec_agency_export")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function exportAction(Request $request)
    {

        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(Agency::class)->createQueryBuilder('a');

        $queryBuilder->select(array('a'))
            ->where('a.deleted IS NULL');

        $agencies = $queryBuilder->getQuery()->getResult();

        /** @var Spreadsheet $spreadsheet */
        $spreadsheet = new Spreadsheet();

        $spreadsheet
            ->getProperties()->setCreator("Privacia Shop")
            ->setLastModifiedBy("Privacia Shop")
            ->setTitle("Privacia Shop - Agencies")
            ->setSubject("Extract");

        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $sheet->setTitle('Agences');

        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A1', 'ID')
            ->setCellValue('B1', 'Nom')
            ->setCellValue('C1', 'Raison sociale')
            ->setCellValue('D1', 'Tarif Identification société')
            ->setCellValue('E1', 'Adresse')
            ->setCellValue('F1', 'Ville')
            ->setCellValue('G1', 'Code postal');


        $i = 2;
        foreach ($agencies as $agency) {

            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue('A' . $i, $agency->getId())
                ->setCellValue('B' . $i, $agency->getName())
                ->setCellValue('C' . $i, $agency->getBusinessName())
                ->setCellValue('D' . $i, $agency->getBusinessId())
                ->setCellValue('E' . $i, $agency->getAddress())
                ->setCellValue('F' . $i, $agency->getCity())
                ->setCellValue('G' . $i, $agency->getPostalCode());
            $i++;
        }


        $fileName = 'PrivaciaShop-Extract-Agencies-' . date('Y-m-d') . '.xlsx';

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
     * @Route("/view/{id}", name="paprec_agency_view")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function viewAction(Request $request, Agency $agency)
    {
        $this->agencyManager->isDeleted($agency, true);


        foreach ($this->getParameter('paprec_types_picture') as $type) {
            $types[$type] = $type;
        }

        $picture = new Picture();

        $formAddPicture = $this->createForm(PictureAgencyType::class, $picture, array(
            'types' => $types
        ));

        $formEditPicture = $this->createForm(PictureAgencyType::class, $picture, array(
            'types' => $types,
        ));

        return $this->render('agency/view.html.twig', array(
            'agency' => $agency,
            'formAddPicture' => $formAddPicture->createView(),
            'formEditPicture' => $formEditPicture->createView(),
        ));
    }

    /**
     * @Route("/add", name="paprec_agency_add")
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
     * @Route("/edit/{id}", name="paprec_agency_edit")
     * @Security("has_role('ROLE_ADMIN')")
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function editAction(Request $request, Agency $agency)
    {
        $user = $this->getUser();

        $this->agencyManager->isDeleted($agency, true);

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
     * @Route("/remove/{id}", name="paprec_agency_remove")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removeAction(Request $request, Agency $agency)
    {
        $em = $this->getDoctrine()->getManager();

        /*
         * Suppression des images
         */
        foreach ($agency->getPictures() as $picture) {
            $this->removeFile($this->getParameter('paprec.agency.picto_path') . '/' . $picture->getPath());
            $agency->removePicture($picture);
        }
        
        $agency->setDeleted(new \DateTime());
        $em->flush();

        return $this->redirectToRoute('paprec_agency_index');
    }

    /**
     * @Route("/removeMany/{ids}", name="paprec_agency_removeMany")
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
                foreach ($agency->getPictures() as $picture) {
                    $this->removeFile($this->getParameter('paprec.agency.picto_path') . '/' . $picture->getPath());
                    $agency->removePicture($picture);
                }
                $agency->setDeleted(new \DateTime);
            }
            $em->flush();
        }

        return new JsonResponse([
            'resultCode' => 1
        ]);
    }

    /**
     * Supprimme un fichier du sytème de fichiers
     *
     * @param $path
     */
    public function removeFile($path)
    {
        $fs = new Filesystem();
        try {
            $fs->remove($path);
        } catch (IOException $e) {
            throw new Exception($e->getMessage());
        }
    }


    /**
     * @Route("/addPicture/{id}/{type}", name="paprec_agency_addPicture")
     * @Method("POST")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addPictureAction(Request $request, Agency $agency)
    {
        $picture = new Picture();
        foreach ($this->getParameter('paprec_types_picture') as $type) {
            $types[$type] = $type;
        }

        $form = $this->createForm(PictureAgencyType::class, $picture, array(
            'types' => $types
        ));

        $form->handleRequest($request);
        if ($form->isValid()) {
            $agency->setDateUpdate(new \DateTime());
            $picture = $form->getData();

            if ($picture->getPath() instanceof UploadedFile) {
                $pic = $picture->getPath();
                $pictoFileName = md5(uniqid('', true)) . '.' . $pic->guessExtension();

                $pic->move($this->getParameter('paprec.agency.picto_path'), $pictoFileName);

                $picture->setPath($pictoFileName);
                $picture->setType($request->get('type'));
                $picture->setAgency($agency);
                $agency->addPicture($picture);
                $this->em->persist($picture);
                $this->em->flush();
            }

            return $this->redirectToRoute('paprec_agency_view', array(
                'id' => $agency->getId()
            ));
        }
        return $this->render('agency/view.html.twig', array(
            'agency' => $agency,
            'formAddPicture' => $form->createView()
        ));
    }

    /**
     * @Route("/editPicture/{id}/{pictureID}", name="paprec_agency_editPicture")
     * @Method("POST")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function editPictureAction(Request $request, Agency $agency)
    {
        $pictureID = $request->get('pictureID');
        $picture = $this->pictureManager->get($pictureID);
        $oldPath = $picture->getPath();

        foreach ($this->getParameter('paprec_types_picture') as $type) {
            $types[$type] = $type;
        }

        $form = $this->createForm(PictureAgencyType::class, $picture, array(
            'types' => $types
        ));


        $form->handleRequest($request);
        if ($form->isValid()) {
            $agency->setDateUpdate(new \DateTime());
            $picture = $form->getData();

            if ($picture->getPath() instanceof UploadedFile) {
                $pic = $picture->getPath();
                $pictoFileName = md5(uniqid('', true)) . '.' . $pic->guessExtension();

                $pic->move($this->getParameter('paprec.agency.picto_path'), $pictoFileName);

                $picture->setPath($pictoFileName);
                $this->removeFile($this->getParameter('paprec.agency.picto_path') . '/' . $oldPath);
                $this->em->flush();
            }

            return $this->redirectToRoute('paprec_agency_view', array(
                'id' => $agency->getId()
            ));
        }
        return $this->render('agency/view.html.twig', array(
            'agency' => $agency,
            'formEditPicture' => $form->createView()
        ));
    }


    /**
     * @Route("/removePicture/{id}/{pictureID}", name="paprec_agency_removePicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removePictureAction(Request $request, Agency $agency)
    {

        $pictureID = $request->get('pictureID');

        $pictures = $agency->getPictures();
        foreach ($pictures as $picture) {
            if ($picture->getId() == $pictureID) {
                $agency->setDateUpdate(new \DateTime());
                $this->removeFile($this->getParameter('paprec.agency.picto_path') . '/' . $picture->getPath());
                $this->em->remove($picture);
                continue;
            }
        }
        $this->em->flush();

        return $this->redirectToRoute('paprec_agency_view', array(
            'id' => $agency->getId()
        ));
    }

    /**
     * @Route("/setPilotPicture/{id}/{pictureID}", name="paprec_agency_setPilotPicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function setPilotPictureAction(Request $request, Agency $agency)
    {

        $pictureID = $request->get('pictureID');
        $pictures = $agency->getPictures();
        foreach ($pictures as $picture) {
            if ($picture->getId() == $pictureID) {
                $agency->setDateUpdate(new \DateTime());
                $picture->setType('PILOTPICTURE');
                continue;
            }
        }
        $this->em->flush();

        return $this->redirectToRoute('paprec_agency_view', array(
            'id' => $agency->getId()
        ));
    }

    /**
     * @Route("/setPicture/{id}/{pictureID}", name="paprec_agency_setPicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function setPictureAction(Request $request, Agency $agency)
    {

        $pictureID = $request->get('pictureID');
        $pictures = $agency->getPictures();
        foreach ($pictures as $picture) {
            if ($picture->getId() == $pictureID) {
                $agency->setDateUpdate(new \DateTime());
                $picture->setType('PICTURE');
                continue;
            }
        }
        $this->em->flush();

        return $this->redirectToRoute('paprec_agency_view', array(
            'id' => $agency->getId()
        ));
    }

}
