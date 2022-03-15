<?php

namespace App\Controller;

use App\Entity\OtherNeed;
use App\Entity\Picture;
use App\Form\OtherNeedType;
use App\Form\PictureOtherNeedType;
use App\Form\PictureProductType;
use App\Service\OtherNeedManager;
use App\Service\PictureManager;
use App\Tools\DataTable;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class OtherNeedController extends AbstractController
{

    private $em;
    private $pictureManager;
    private $otherNeedManager;
    private $translator;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        PictureManager $pictureManager,
        OtherNeedManager $otherNeedManager
    ) {
        $this->em = $em;
        $this->pictureManager = $pictureManager;
        $this->otherNeedManager = $otherNeedManager;
        $this->translator = $translator;
    }

    /**
     * @Route("", name="paprec_other_need_index")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction()
    {
        return $this->render('otherNeed/index.html.twig');
    }

    /**
     * @Route("/loadList", name="paprec_other_need_loadList")
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

        $cols['id'] = array('label' => 'id', 'id' => 'o.id', 'method' => array('getId'));
        $cols['name'] = array('label' => 'name', 'id' => 'o.name', 'method' => array('getName'));
        $cols['catalog'] = array('label' => 'catalog', 'id' => 'o.catalog', 'method' => array('getCatalog'));
        $cols['isDisplayed'] = array('label' => 'isDisplayed', 'id' => 'o.isDisplayed', 'method' => array('getIsDisplayed'));
        $cols['language'] = array('label' => 'language', 'id' => 'o.language', 'method' => array('getLanguage'));

        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(OtherNeed::class)->createQueryBuilder('o');


        $queryBuilder->select(array('o'))
            ->where('o.deleted IS NULL');

        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            if (substr($search['value'], 0, 1) == '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('o.id', '?1')
                ))->setParameter(1, substr($search['value'], 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('o.name', '?1'),
                    $queryBuilder->expr()->like('o.catalog', '?1'),
                    $queryBuilder->expr()->like('o.isDisplayed', '?1'),
                    $queryBuilder->expr()->like('o.language', '?1')
                ))->setParameter(1, '%' . $search['value'] . '%');
            }
        }
        
        $dt = $dataTable->generateTable($cols, $queryBuilder, $pageSize, $start, $orders, $columns, $filters,
            $paginator, $rowPrefix);
        
        // Reformatage de certaines données
        $tmp = array();
        foreach ($dt['data'] as $data) {
            $line = $data;
            $line['catalog'] = $data['catalog'] ? $this->translator->trans('Catalog.OtherNeed.Catalog.' . ucfirst($data['catalog'])) : '/';
            $line['isDisplayed'] = $data['isDisplayed'] ? $this->translator->trans('General.1') : $this->translator->trans('General.0');
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
     * @Route("/view/{id}", name="paprec_other_need_view")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @param OtherNeed $otherNeed
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function viewAction(Request $request, OtherNeed $otherNeed)
    {
        $this->otherNeedManager->isDeleted($otherNeed, true);

        foreach ($this->getParameter('paprec_other_need_types_picture') as $type) {
            $types[$type] = $type;
        }

        $picture = new Picture();

        $formAddPicture = $this->createForm(PictureOtherNeedType::class, $picture, array(
            'types' => $types
        ));

        $formEditPicture = $this->createForm(PictureOtherNeedType::class, $picture, array(
            'types' => $types
        ));

        return $this->render('otherNeed/view.html.twig', array(
            'otherNeed' => $otherNeed,
            'formAddPicture' => $formAddPicture->createView(),
            'formEditPicture' => $formEditPicture->createView()
        ));
    }

    /**
     * @Route("/add", name="paprec_other_need_add")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addAction(Request $request)
    {
        $user = $this->getUser();

        $otherNeed = new OtherNeed();

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }

        $form = $this->createForm(OtherNeedType::class, $otherNeed, array(
            'languages' => $languages,
            'language' => strtoupper($request->getLocale())
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $otherNeed = $form->getData();

            $otherNeed->setDateCreation(new \DateTime);
            $otherNeed->setUserCreation($user);

            $this->em->persist($otherNeed);
            $this->em->flush();

            return $this->redirectToRoute('paprec_other_need_view', array(
                'id' => $otherNeed->getId()
            ));

        }

        return $this->render('otherNeed/add.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/edit/{id}", name="paprec_other_need_edit")
     * @Security("has_role('ROLE_ADMIN')")
     * @param Request $request
     * @param OtherNeed $otherNeed
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function editAction(Request $request, OtherNeed $otherNeed)
    {
        $user = $this->getUser();

        $this->otherNeedManager->isDeleted($otherNeed, true);

        $languages = array();
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }

        $form = $this->createForm(OtherNeedType::class, $otherNeed, array(
            'languages' => $languages,
            'language' => strtoupper($otherNeed->getLanguage())
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $otherNeed = $form->getData();

            $otherNeed->setDateUpdate(new \DateTime);
            $otherNeed->setUserUpdate($user);

            $this->em->flush();

            return $this->redirectToRoute('paprec_other_need_view', array(
                'id' => $otherNeed->getId()
            ));

        }

        return $this->render('otherNeed/edit.html.twig', array(
            'form' => $form->createView(),
            'otherNeed' => $otherNeed
        ));
    }

    /**
     * @Route("/remove/{id}", name="paprec_other_need_remove")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removeAction(Request $request, OtherNeed $otherNeed)
    {
        $otherNeed->setDeleted(new \DateTime());
        /*
        * Suppression des images
         */
        foreach ($otherNeed->getPictures() as $picture) {
            $this->removeFile($this->getParameter('paprec.other_need.picto_path') . '/' . $picture->getPath());
            $otherNeed->removePicture($picture);
        }
        $this->em->flush();

        return $this->redirectToRoute('paprec_other_need_index');
    }

    /**
     * @Route("/removeMany/{ids}", name="paprec_other_need_removeMany")
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
            $otherNeeds = $this->em->getRepository('App:OtherNeed')->findById($ids);
            foreach ($otherNeeds as $otherNeed) {
                foreach ($otherNeed->getPictures() as $picture) {
                    $this->removeFile($this->getParameter('paprec.other_need.picto_path') . '/' . $picture->getPath());
                    $otherNeed->removePicture($picture);
                }

                $otherNeed->setDeleted(new \DateTime());
                $otherNeed->setIsDisplayed(false);
            }
            $this->em->flush();
        }

        return new JsonResponse([
            'resultCode' => 1
        ]);
    }

    /**
     * @Route("/addPicture/{id}/{type}", name="paprec_other_need_addPicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addPictureAction(Request $request, OtherNeed $otherNeed)
    {
        $picture = new Picture();

        foreach ($this->getParameter('paprec_other_need_types_picture') as $type) {
            $types[$type] = $type;
        }

        $form = $this->createForm(PictureProductType::class, $picture, array(
            'types' => $types
        ));

        $form->handleRequest($request);
        if ($form->isValid()) {
            $otherNeed->setDateUpdate(new \DateTime());
            $picture = $form->getData();

            if ($picture->getPath() instanceof UploadedFile) {
                $pic = $picture->getPath();
                $pictoFileName = md5(uniqid('', true)) . '.' . $pic->guessExtension();

                $pic->move($this->getParameter('paprec.other_need.picto_path'), $pictoFileName);

                $picture->setPath($pictoFileName);
                $picture->setType($request->get('type'));
                $picture->setOtherNeed($otherNeed);
                $otherNeed->addPicture($picture);
                $this->em->persist($picture);
                $this->em->flush();
            }

            return $this->redirectToRoute('paprec_other_need_view', array(
                'id' => $otherNeed->getId()
            ));
        }
        return $this->render('otherNeed/view.html.twig', array(
            'otherNeed' => $otherNeed,
            'formAddPicture' => $form->createView()
        ));
    }


    /**
     * @Route("/editPicture/{id}/{pictureID}", name="paprec_other_need_editPicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function editPictureAction(Request $request, OtherNeed $otherNeed)
    {

        $pictureID = $request->get('pictureID');
        $picture = $this->pictureManager->get($pictureID);
        $oldPath = $picture->getPath();

        foreach ($this->getParameter('paprec_other_need_types_picture') as $type) {
            $types[$type] = $type;
        }

        $form = $this->createForm(PictureProductType::class, $picture, array(
            'types' => $types
        ));

        $form->handleRequest($request);
        if ($form->isValid()) {
            $otherNeed->setDateUpdate(new \DateTime());
            $picture = $form->getData();

            if ($picture->getPath() instanceof UploadedFile) {
                $pic = $picture->getPath();
                $pictoFileName = md5(uniqid('', true)) . '.' . $pic->guessExtension();

                $pic->move($this->getParameter('paprec.other_need.picto_path'), $pictoFileName);

                $picture->setPath($pictoFileName);
                $this->removeFile($this->getParameter('paprec.other_need.picto_path') . '/' . $oldPath);
                $this->em->flush();
            }

            return $this->redirectToRoute('paprec_other_need_view', array(
                'id' => $otherNeed->getId()
            ));
        }
        return $this->render('otherNeed/view.html.twig', array(
            'otherNeed' => $otherNeed,
            'formEditPicture' => $form->createView()
        ));
    }

    /**
     * @Route("/removePicture/{id}/{pictureID}", name="paprec_other_need_removePicture")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removePictureAction(Request $request, OtherNeed $otherNeed)
    {
        $pictureID = $request->get('pictureID');

        $pictures = $otherNeed->getPictures();
        foreach ($pictures as $picture) {
            if ($picture->getId() == $pictureID) {
                $otherNeed->setDateUpdate(new \DateTime());
                $this->removeFile($this->getParameter('paprec.other_need.picto_path') . '/' . $picture->getPath());
                $this->em->remove($picture);
                continue;
            }
        }
        $this->em->flush();

        return $this->redirectToRoute('paprec_other_need_view', array(
            'id' => $otherNeed->getId()
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


}
