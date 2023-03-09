<?php

namespace App\Controller;

use App\Entity\FollowUp;
use App\Entity\FollowUpFile;
use App\Entity\QuoteRequestFile;
use App\Form\FollowUpFileType;
use App\Form\FollowUpType;
use App\Form\QuoteRequestFileType;
use App\Service\FollowUpManager;
use App\Tools\DataTable;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class FollowUpController extends AbstractController
{

    private $em;
    private $translator;
    private $followUpManager;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        FollowUpManager $followUpManager
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->followUpManager = $followUpManager;
    }

    /**
     * @Route("", name="paprec_follow_up_index")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function indexAction()
    {
        return $this->render('followUp/index.html.twig');
    }

    /**
     * @Route("/loadList", name="paprec_follow_up_loadList")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
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

        $cols['id'] = array('label' => 'id', 'id' => 'fU.id', 'method' => array('getId'));
        $cols['status'] = array('label' => 'status', 'id' => 'fU.status', 'method' => array('getStatus'));
        $cols['content'] = array('label' => 'content', 'id' => 'fU.content', 'method' => array('getContent'));
        $cols['quoteRequestOrigin'] = array('label' => 'quoteRequestOrigin', 'id' => 'p.origin', 'method' => array('getQuoteRequest', 'getOrigin'));
        $cols['quoteRequestNumber'] = array('label' => 'quoteRequestNumber', 'id' => 'p.number', 'method' => array('getQuoteRequest', 'getNumber'));

        $queryBuilder = $this->getDoctrine()->getManager()->getRepository(FollowUp::class)->createQueryBuilder('fU');


        $queryBuilder->select(array('fU'))
            ->select(array('fU', 'qR'))
            ->where('fU.deleted is NULL')
            ->leftJoin('fU.quoteRequest', 'qR')
            ->andWhere('qR.deleted is NULL');

        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            if (substr($search['value'], 0, 1) === '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('fU.id', '?1')
                ))->setParameter(1, substr($search['value'], 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('fU.content', '?1'),
                    $queryBuilder->expr()->like('fU.status', '?1'),
                    $queryBuilder->expr()->like('qR.origin', '?1'),
                    $queryBuilder->expr()->like('qR.number', '?1')
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
     * @Route("/view/{id}", name="paprec_follow_up_view")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @param Request $request
     * @param FollowUp $followUp
     * @return Response
     * @throws EntityNotFoundException
     */
    public function viewAction(Request $request, FollowUp $followUp): Response
    {
        $this->followUpManager->isDeleted($followUp, true);

        $followUpFile = new FollowUpFile();

        $formAddFollowUpFile = $this->createForm(FollowUpFileType::class, $followUpFile, array());

        return $this->render('followUp/view.html.twig', array(
            'followUp' => $followUp,
            'formAddFollowUpFile' => $formAddFollowUpFile->createView()
        ));
    }

    /**
     * @Route("/add", name="paprec_follow_up_add")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function addAction(Request $request)
    {
        $user = $this->getUser();

        $followUp = new FollowUp();

        $status = [];
        foreach ($this->getParameter('paprec.follow_up.status') as $s) {
            $status[$s] = $s;
        }

        $form = $this->createForm(FollowUpType::class, $followUp, [
            'status' => $status
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $followUp = $form->getData();

            $followUp->setDateCreation(new DateTime);
            $followUp->setUserCreation($user);

            $this->em->persist($followUp);
            $this->em->flush();

            return $this->redirectToRoute('paprec_follow_up_view', array(
                'id' => $followUp->getId()
            ));

        }

        return $this->render('followUp/add.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/edit/{id}", name="paprec_follow_up_edit")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @param Request $request
     * @param FollowUp $followUp
     * @return RedirectResponse|Response
     * @throws EntityNotFoundException
     */
    public function editAction(Request $request, FollowUp $followUp)
    {
        $user = $this->getUser();

        $this->followUpManager->isDeleted($followUp, true);

        $status = [];
        foreach ($this->getParameter('paprec.follow_up.status') as $s) {
            $status[$s] = $s;
        }

        $form = $this->createForm(FollowUpType::class, $followUp, [
            'status' => $status
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $followUp = $form->getData();

            $followUp->setDateUpdate(new DateTime);
            $followUp->setUserUpdate($user);

            $this->em->flush();

            return $this->redirectToRoute('paprec_follow_up_view', array(
                'id' => $followUp->getId()
            ));

        }

        return $this->render('followUp/edit.html.twig', array(
            'form' => $form->createView(),
            'followUp' => $followUp
        ));
    }

    /**
     * @Route("/remove/{id}", name="paprec_follow_up_remove")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function removeAction(Request $request, FollowUp $followUp): RedirectResponse
    {
        $followUp->setDeleted(new DateTime());

        $this->em->flush();

        return $this->redirectToRoute('paprec_follow_up_index');
    }

    /**
     * @Route("/removeMany/{ids}", name="paprec_follow_up_removeMany")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function removeManyAction(Request $request)
    {
        $ids = $request->get('ids');

        if (!$ids) {
            throw new NotFoundHttpException();
        }

        $ids = explode(',', $ids);

        if (is_array($ids) && count($ids)) {
            $followUps = $this->em->getRepository('App:FollowUp')->findById($ids);
            foreach ($followUps as $followUp) {
                $followUp->setDeleted(new \DateTime());
            }
            $this->em->flush();
        }

        return new JsonResponse([
            'resultCode' => 1
        ]);
    }

    /**
     * @Route("/addFollowUpFile/{followUpId}", name="paprec_follow_up_add_follow_up_file")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     */
    public function addFollowUpFileAction(Request $request, $followUpId)
    {
        $followUp = $this->followUpManager->get($followUpId);
        $followUpFile = new FollowUpFile();

        $form = $this->createForm(FollowUpFileType::class, $followUpFile, array());

        $form->handleRequest($request);
        if ($form->isValid()) {
            $followUp->setDateUpdate(new \DateTime());
            $followUpFile = $form->getData();

            if ($followUpFile->getSystemPath() instanceof UploadedFile) {
                $followUpFilePath = $followUpFile->getSystemPath();

                $followUpFileSize = $followUpFile->getSystemPath()->getClientSize();

                $fileMaxSize = $this->getParameter('paprec.follow_up_file.max_file_size');

                if ($followUpFileSize > $fileMaxSize) {
                    $this->get('session')->getFlashBag()->add('error', 'generatedFollowUpFileNotAdded');

                    return $this->redirectToRoute('paprec_follow_up_view', array(
                        'id' => $followUp->getId()
                    ));
                }

                $followUpFileSystemName = md5(uniqid('', true)) . '.' . $followUpFilePath->guessExtension();

                $followUpFilePath->move($this->getParameter('paprec.follow_up_file.directory'), $followUpFileSystemName);

                $followUpFileOriginalName = $followUpFile->getSystemPath()->getClientOriginalName();
                $followUpFileMimeType = $followUpFile->getSystemPath()->getClientMimeType();

                $followUpFile
                    ->setSystemName($followUpFileSystemName)
                    ->setOriginalFileName($followUpFileOriginalName)
                    ->setMimeType($followUpFileMimeType)
                    ->setSystemSize($followUpFileSize)
                    ->setFollowUp($followUp);
                $followUp->addFollowUpFile($followUpFile);
                $this->em->persist($followUpFile);
                $this->em->flush();
            }

            return $this->redirectToRoute('paprec_follow_up_view', array(
                'id' => $followUp->getId()
            ));
        }

        return $this->redirectToRoute('paprec_follow_up_view', array(
            'id' => $followUp->getId()
        ));
    }

    /**
     * @Route("removeFollowUpFile/{followUpId}/{followUpFileId}", name="paprec_follow_up_remove_follow_up_file")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @ParamConverter("followUpFile", options={"id" = "followUpFileId"})
     */
    public function removeFollowUpFileAction(Request $request, FollowUp $followUpId, FollowUpFile $followUpFile)
    {
        $followUp = $this->followUpManager->get($followUpId);
        $this->em->remove($followUpFile);
        $this->em->flush();

        $followUpFileFolder = $this->getParameter('paprec.follow_up_file.directory');
        $followUpFilePath = $followUpFileFolder . '/' . $followUpFile->getSystemName();

        if (file_exists($followUpFilePath)) {
            unlink($followUpFilePath);
        }

        return $this->redirectToRoute('paprec_follow_up_view', array(
            'id' => $followUp->getId()
        ));
    }
}
