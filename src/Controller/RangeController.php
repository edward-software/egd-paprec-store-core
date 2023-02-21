<?php

namespace App\Controller;

use App\Entity\Picture;
use App\Entity\Range;
use App\Entity\RangeLabel;
use App\Form\PictureRangeType;
use App\Form\RangeLabelType;
use App\Form\RangeType;
use App\Service\NumberManager;
use App\Service\PictureManager;
use App\Service\RangeLabelManager;
use App\Service\RangeManager;
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

class RangeController extends AbstractController
{

    private $em;
    private $rangeManager;
    private $rangeLabelManager;
    private $pictureManager;
    private $translator;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        RangeManager $rangeManager,
        PictureManager $pictureManager,
        RangeLabelManager $rangeLabelManager
    ) {
        $this->em = $em;
        $this->rangeManager = $rangeManager;
        $this->rangeLabelManager = $rangeLabelManager;
        $this->pictureManager = $pictureManager;
        $this->translator = $translator;
    }

    /**
     * @Route("", name="paprec_range_index")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction()
    {
        return $this->render('range/index.html.twig');
    }

    /**
     * @Route("/loadList", name="paprec_range_loadList")
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

        $cols['id'] = array('label' => 'id', 'id' => 'p.id', 'method' => array('getId'));
        $cols['name'] = array(
            'label' => 'name',
            'id' => 'pL.name',
            'method' => array(array('getRangeLabels', 0), 'getName')
        );
        $cols['isEnabled'] = array('label' => 'isEnabled', 'id' => 'p.isEnabled', 'method' => array('getIsEnabled'));


        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(Range::class)->createQueryBuilder('p');

        $queryBuilder->select(array('p', 'pL'))
            ->leftJoin('p.rangeLabels', 'pL')
            ->where('p.deleted IS NULL')
            ->andWhere('pL.language = :language')
            ->orderBy('p.position', 'ASC')
            ->setParameter('language', 'FR');

        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            if (substr($search['value'], 0, 1) === '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('p.id', '?1')
                ))->setParameter(1, substr($search['value'], 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('pL.name', '?1'),
                    $queryBuilder->expr()->like('p.isEnabled', '?1')
                ))->setParameter(1, '%' . $search['value'] . '%');
            }
        }

        $dt = $dataTable->generateTable($cols, $queryBuilder, $pageSize, $start, $orders, $columns, $filters,
            $paginator, $rowPrefix);

        // Reformatage de certaines données
        $tmp = array();
        foreach ($dt['data'] as $data) {
            $line = $data;
            $line['isEnabled'] = $data['isEnabled'] ? $this->translator->trans('General.1') : $this->translator->trans('General.0');
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
     * @Route("/export",  name="paprec_range_export")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function exportAction(Request $request)
    {
        $language = $request->getLocale();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder->select(array('p'))
            ->from('App:Range', 'p')
            ->where('p.deleted IS NULL');

        $ranges = $queryBuilder->getQuery()->getResult();

        /** @var Spreadsheet $spreadsheet */
        $spreadsheet = new Spreadsheet();

        $spreadsheet
            ->getProperties()->setCreator("EasyRecyclageShop")
            ->setLastModifiedBy("EasyRecyclageShop")
            ->setTitle("EasyRecyclageShop - Ranges")
            ->setSubject("Extract");

        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $sheet->setTitle('Ranges');

        // Labels
        $sheetLabels = [
            'P. ID',
            'Creation date',
            'Update date',
            'Deleted',
            'is Enabled',
            'Position',
            'User creation ID',
            'User update ID',
            'PL. ID',
            'Name',
            'Short desc.',
            'Language'
        ];

        $xAxe = 'A';
        foreach ($sheetLabels as $label) {
            $sheet->setCellValue($xAxe . 1, $label);
            $xAxe++;
        }

        $yAxe = 2;

        /** @var Range $range */
        foreach ($ranges as $range) {

            /** @var RangeLabel $rangeLabel */
            $rangeLabel = $this->rangeManager->getRangeLabelByRangeAndLocale($range, strtoupper($language));

            // Getters
            $getters = [
                $range->getId(),
                $range->getDateCreation()->format('Y-m-d'),
                ($range->getDateUpdate() == null) ? null : $range->getDateUpdate()->format('Y-m-d'),
                $range->getDeleted() ? 'true' : 'false',
                $range->getIsEnabled(),
                $range->getPosition(),
                $range->getUserCreation(),
                $range->getUserUpdate(),
                $rangeLabel->getId(),
                $rangeLabel->getName(),
                $rangeLabel->getShortDescription(),
                $rangeLabel->getLanguage()
            ];

            $xAxe = 'A';
            foreach ($getters as $getter) {
                $sheet->setCellValue($xAxe . $yAxe, (string)$getter);
                $xAxe++;
            }
            $yAxe++;
        }


        // Resize columns
        for ($i = 'A'; $i <= $sheet->getHighestDataColumn(); $i++) {
            $sheet->getColumnDimension($i)->setAutoSize(true);
        }

        $fileName = 'EasyRecyclageShop-Extraction-Ranges-' . date('Y-m-d') . '.xlsx';

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
     * @Route("/view/{id}",  name="paprec_range_view")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function viewAction(Request $request, Range $range)
    {

        $this->rangeManager->isDeleted($range, true);

        $language = $request->getLocale();
        $rangeLabel = $this->rangeManager->getRangeLabelByRangeAndLocale($range, strtoupper($language));

        $otherRangeLabels = $this->rangeManager->getRangeLabels($range);

        $tmp = array();
        foreach ($otherRangeLabels as $pL) {
            if ($pL->getId() != $rangeLabel->getId()) {
                $tmp[] = $pL;
            }
        }
        $otherRangeLabels = $tmp;


        foreach ($this->getParameter('paprec_types_picture') as $type) {
            $types[$type] = $type;
        }

        $picture = new Picture();

        $formAddPicture = $this->createForm(PictureRangeType::class, $picture, array(
            'types' => $types
        ));

        $formEditPicture = $this->createForm(PictureRangeType::class, $picture, array(
            'types' => $types
        ));


        return $this->render('range/view.html.twig', array(
            'range' => $range,
            'rangeLabel' => $rangeLabel,
            'formAddPicture' => $formAddPicture->createView(),
            'formEditPicture' => $formEditPicture->createView(),
            'otherRangeLabels' => $otherRangeLabels
        ));
    }

    /**
     * @Route("/add",  name="paprec_range_add")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addAction(Request $request)
    {
        $user = $this->getUser();

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }

        $range = new Range();
        $rangeLabel = new RangeLabel();

        $form1 = $this->createForm(RangeType::class, $range);
        $form2 = $this->createForm(RangeLabelType::class, $rangeLabel, array(
            'languages' => $languages,
            'language' => 'FR'
        ));

        $form1->handleRequest($request);
        $form2->handleRequest($request);

        if ($form1->isSubmitted() && $form1->isValid() && $form2->isSubmitted() && $form2->isValid()) {

            $range = $form1->getData();

            $range->setDateCreation(new \DateTime);
            $range->setUserCreation($user);

            $this->em->persist($range);
            $this->em->flush();

            $rangeLabel = $form2->getData();
            $rangeLabel->setDateCreation(new \DateTime);
            $rangeLabel->setUserCreation($user);
            $rangeLabel->setRange($range);

            $this->em->persist($rangeLabel);
            $this->em->flush();

            return $this->redirectToRoute('paprec_range_view', array(
                'id' => $range->getId()
            ));

        }

        return $this->render('range/add.html.twig', array(
            'form1' => $form1->createView(),
            'form2' => $form2->createView()
        ));
    }

    /**
     * @Route("/edit/{id}",  name="paprec_range_edit")
     * @Security("has_role('ROLE_ADMIN')")
     * @throws \Doctrine\ORM\EntityNotFoundException
     * @throws \Exception
     */
    public function editAction(Request $request, Range $range)
    {
        $this->rangeManager->isDeleted($range, true);

        $user = $this->getUser();

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }


        $language = $request->getLocale();
        $rangeLabel = $this->rangeManager->getRangeLabelByRangeAndLocale($range, strtoupper($language));


        $form1 = $this->createForm(RangeType::class, $range);
        $form2 = $this->createForm(RangeLabelType::class, $rangeLabel, array(
            'languages' => $languages,
            'language' => $rangeLabel->getLanguage()
        ));

        $form1->handleRequest($request);
        $form2->handleRequest($request);

        if ($form1->isSubmitted() && $form1->isValid() && $form2->isSubmitted() && $form2->isValid()) {

            $range = $form1->getData();

            $range->setDateUpdate(new \DateTime);
            $range->setUserUpdate($user);
            $this->em->flush();

            $rangeLabel = $form2->getData();
            $rangeLabel->setDateUpdate(new \DateTime);
            $rangeLabel->setUserUpdate($user);
            $rangeLabel->setRange($range);

            $this->em->flush();

            return $this->redirectToRoute('paprec_range_view', array(
                'id' => $range->getId()
            ));
        }
        return $this->render('range/edit.html.twig', array(
            'form1' => $form1->createView(),
            'form2' => $form2->createView(),
            'range' => $range,
            'rangeLabel' => $rangeLabel
        ));
    }

    /**
     * @Route("/remove/{id}", name="paprec_range_remove")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removeAction(Request $request, Range $range)
    {
        /*
         * Suppression des images
         */
        foreach ($range->getPictures() as $picture) {
            $this->removeFile($this->getParameter('paprec.range.picto_path') . '/' . $picture->getPath());
            $range->removePicture($picture);
        }

        $range->setDeleted(new \DateTime);
        $range->setIsEnabled(false);
        $this->em->flush();

        return $this->redirectToRoute('paprec_range_index');
    }

    /**
     * @Route("/removeMany/{ids}", name="paprec_range_removeMany")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removeManyAction(Request $request)
    {
        $ids = $request->get('ids');

        if (!$ids) {
            throw new NotFoundHttpException();
        }

        $ids = explode(',', $ids);

        if (is_array($ids) && count($ids)) {
            $ranges = $this->em->getRepository('App:Range')->findById($ids);
            foreach ($ranges as $range) {
                foreach ($range->getPictures() as $picture) {
                    $this->removeFile($this->getParameter('paprec.range.picto_path') . '/' . $picture->getPath());
                    $range->removePicture($picture);
                }

                $range->setDeleted(new \DateTime());
                $range->setIsEnabled(false);
            }
            $this->em->flush();
        }

        return new JsonResponse([
            'resultCode' => 1
        ]);
    }

    /**
     * @Route("/enableMany/{ids}", name="paprec_range_enableMany")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function enableManyAction(Request $request)
    {
        $ids = $request->get('ids');

        if (!$ids) {
            throw new NotFoundHttpException();
        }

        $ids = explode(',', $ids);

        if (is_array($ids) && count($ids)) {
            $ranges = $this->em->getRepository('App:Range')->findById($ids);
            foreach ($ranges as $range) {
                $range->setIsEnabled(true);
            }
            $this->em->flush();
        }
        return $this->redirectToRoute('paprec_range_index');
    }

    /**
     * @Route("/disableMany/{ids}", name="paprec_range_disableMany")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function disableManyAction(Request $request)
    {
        $ids = $request->get('ids');

        if (!$ids) {
            throw new NotFoundHttpException();
        }

        $ids = explode(',', $ids);

        if (is_array($ids) && count($ids)) {
            $ranges = $this->em->getRepository('App:Range')->findById($ids);
            foreach ($ranges as $range) {
                $range->setIsEnabled(false);
            }
            $this->em->flush();
        }
        return $this->redirectToRoute('paprec_range_index');
    }

    /**
     * @Route("/{id}/addRangeLabel",  name="paprec_range_addRangeLabel")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addRangeLabelAction(Request $request, Range $range)
    {
        $user = $this->getUser();

        $this->rangeManager->isDeleted($range, true);

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }
        $rangeLabel = new RangeLabel();

        $form = $this->createForm(RangeLabelType::class, $rangeLabel, array(
            'languages' => $languages,
            'language' => strtoupper($request->getLocale())
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $rangeLabel = $form->getData();
            $rangeLabel->setDateCreation(new \DateTime);
            $rangeLabel->setUserCreation($user);
            $rangeLabel->setRange($range);

            $this->em->persist($rangeLabel);
            $this->em->flush();

            return $this->redirectToRoute('paprec_range_view', array(
                'id' => $range->getId()
            ));

        }

        return $this->render('range/rangeLabel/add.html.twig', array(
            'form' => $form->createView(),
            'range' => $range,
        ));
    }

    /**
     * @Route("/{id}/editRangeLabel/{rangeLabelId}",  name="paprec_range_editRangeLabel")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @param Range $range
     * @param $rangeLabelId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function editRangeLabelAction(Request $request, Range $range, $rangeLabelId)
    {
        $user = $this->getUser();


        $this->rangeManager->isDeleted($range, true);

        $rangeLabel = $this->rangeLabelManager->get($rangeLabelId);

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }

        $form = $this->createForm(RangeLabelType::class, $rangeLabel, array(
            'languages' => $languages,
            'language' => $rangeLabel->getLanguage()
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $rangeLabel = $form->getData();
            $rangeLabel->setDateUpdate(new \DateTime);
            $rangeLabel->setUserUpdate($user);

//            $this->em->merge($rangeLabel);
            $this->em->flush();

            return $this->redirectToRoute('paprec_range_view', array(
                'id' => $range->getId()
            ));

        }

        return $this->render('range/rangeLabel/edit.html.twig', array(
            'form' => $form->createView(),
            'range' => $range
        ));
    }

    /**
     * @Route("/{id}/removeRangeLabel/{rangeLabelId}",  name="paprec_range_removeRangeLabel")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @param Range $range
     * @param $rangeLabelId
     */
    public function removeRangeLabelAction(Request $request, Range $range, $rangeLabelId)
    {
        $this->rangeManager->isDeleted($range, true);

        $rangeLabel = $this->rangeLabelManager->get($rangeLabelId);
        $this->em->remove($rangeLabel);

        $this->em->flush();

        return $this->redirectToRoute('paprec_range_view', array(
            'id' => $range->getId()
        ));
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
     * @Route("/addPicture/{id}/{type}", name="paprec_range_addPicture")
     * @Method("POST")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addPictureAction(Request $request, Range $range)
    {
        $picture = new Picture();
        foreach ($this->getParameter('paprec_types_picture') as $type) {
            $types[$type] = $type;
        }

        $form = $this->createForm(PictureRangeType::class, $picture, array(
            'types' => $types
        ));

        $form->handleRequest($request);
        if ($form->isValid()) {
            $range->setDateUpdate(new \DateTime());
            $picture = $form->getData();

            if ($picture->getPath() instanceof UploadedFile) {
                $pic = $picture->getPath();
                $pictoFileName = md5(uniqid('', true)) . '.' . $pic->guessExtension();

                $pic->move($this->getParameter('paprec.range.picto_path'), $pictoFileName);

                $picture->setPath($pictoFileName);
                $picture->setType($request->get('type'));
                $picture->setRange($range);
                $range->addPicture($picture);
                $this->em->persist($picture);
                $this->em->flush();
            }

            return $this->redirectToRoute('paprec_range_view', array(
                'id' => $range->getId()
            ));
        }
        return $this->render('range/view.html.twig', array(
            'range' => $range,
            'formAddPicture' => $form->createView()
        ));
    }

    /**
     * @Route("/editPicture/{id}/{pictureID}", name="paprec_range_editPicture")
     * @Method("POST")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function editPictureAction(Request $request, Range $range)
    {

        $pictureID = $request->get('pictureID');
        $picture = $this->pictureManager->get($pictureID);
        $oldPath = $picture->getPath();

        foreach ($this->getParameter('paprec_types_picture') as $type) {
            $types[$type] = $type;
        }

        $form = $this->createForm(PictureRangeType::class, $picture, array(
            'types' => $types
        ));


        $form->handleRequest($request);
        if ($form->isValid()) {
            $range->setDateUpdate(new \DateTime());
            $picture = $form->getData();

            if ($picture->getPath() instanceof UploadedFile) {
                $pic = $picture->getPath();
                $pictoFileName = md5(uniqid('', true)) . '.' . $pic->guessExtension();

                $pic->move($this->getParameter('paprec.range.picto_path'), $pictoFileName);

                $picture->setPath($pictoFileName);
                $this->removeFile($this->getParameter('paprec.range.picto_path') . '/' . $oldPath);
                $this->em->flush();
            }

            return $this->redirectToRoute('paprec_range_view', array(
                'id' => $range->getId()
            ));
        }
        return $this->render('range/view.html.twig', array(
            'range' => $range,
            'formEditPicture' => $form->createView()
        ));
    }


    /**
     * @Route("/removePicture/{id}/{pictureID}", name="paprec_range_removePicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removePictureAction(Request $request, Range $range)
    {

        $pictureID = $request->get('pictureID');

        $pictures = $range->getPictures();
        foreach ($pictures as $picture) {
            if ($picture->getId() == $pictureID) {
                $range->setDateUpdate(new \DateTime());
                $this->removeFile($this->getParameter('paprec.range.picto_path') . '/' . $picture->getPath());
                $this->em->remove($picture);
                continue;
            }
        }
        $this->em->flush();

        return $this->redirectToRoute('paprec_range_view', array(
            'id' => $range->getId()
        ));
    }

    /**
     * @Route("/setPilotPicture/{id}/{pictureID}", name="paprec_range_setPilotPicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function setPilotPictureAction(Request $request, Range $range)
    {

        $pictureID = $request->get('pictureID');
        $pictures = $range->getPictures();
        foreach ($pictures as $picture) {
            if ($picture->getId() == $pictureID) {
                $range->setDateUpdate(new \DateTime());
                $picture->setType('PILOTPICTURE');
                continue;
            }
        }
        $this->em->flush();

        return $this->redirectToRoute('paprec_range_view', array(
            'id' => $range->getId()
        ));
    }

    /**
     * @Route("/setPicture/{id}/{pictureID}", name="paprec_range_setPicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function setPictureAction(Request $request, Range $range)
    {

        $pictureID = $request->get('pictureID');
        $pictures = $range->getPictures();
        foreach ($pictures as $picture) {
            if ($picture->getId() == $pictureID) {
                $range->setDateUpdate(new \DateTime());
                $picture->setType('PICTURE');
                continue;
            }
        }
        $this->em->flush();

        return $this->redirectToRoute('paprec_range_view', array(
            'id' => $range->getId()
        ));
    }

}
