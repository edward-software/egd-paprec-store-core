<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\File;
use App\Form\FileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class HomeController extends AbstractController
{
    private $em;

    public function __construct(
        EntityManagerInterface $em
    ) {
        $this->em = $em;
    }

    /**
     * @Route("/", name="paprec_home_home")
     * @Security("has_role('ROLE_USER')")
     *
     * @return Response
     */
    public function indexAction(): Response
    {
        return $this->render('home/index.html.twig');
    }

    /**
     * @Route("/addFile", name="paprec_add_file")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function addFileAction(Request $request)
    {
        $file = new File();

        $form = $this->createForm(FileFormType::class, $file, []);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $file = $form->getData();

            if ($file->getSystemPath() instanceof UploadedFile) {
                $filePath = $file->getSystemPath();

                $fileSize = $file->getSystemPath()->getClientSize();

                $fileMaxSize = $this->getParameter('paprec.quote_request_file.max_file_size');

                if ($fileSize > $fileMaxSize) {
                    $this->get('session')->getFlashBag()->add('error', 'generatedFileNotAdded');

                    return $this->redirectToRoute('paprec_home_home');
                }

                $fileSystemName = md5(uniqid('', true)) . '.' . $filePath->guessExtension();

                $filePath->move($this->getParameter('paprec.quote_request_file.directory'),
                    $fileSystemName);

                $fileOriginalName = $file->getSystemPath()->getClientOriginalName();
                $fileMimeType = $file->getSystemPath()->getClientMimeType();

                $file
                    ->setSystemName($fileSystemName)
                    ->setOriginalFileName($fileOriginalName)
                    ->setMimeType($fileMimeType)
                    ->setSystemSize($fileSize);
                $this->em->persist($file);
                $this->em->flush();
            }

            return $this->redirectToRoute('paprec_home_home');
        }

        return $this->redirectToRoute('paprec_home_home');
    }

    /**
     * @Route("removeFile/{id}", name="paprec_remove_file")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removeFileAction(Request $request, File $file)
    {

        $this->em->remove($file);
        $this->em->flush();

        $fileFolder = $this->getParameter('paprec.quote_request_file.directory');
        $filePath = $fileFolder . '/' . $file->getSystemName();

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        return $this->redirectToRoute('paprec_home_home');
    }

    /**
     * @Route("/downloadFile/{id}", name="paprec_download_file")
     * @Security("has_role('ROLE_COMMERCIAL') or has_role('ROLE_COMMERCIAL_MULTISITES')")
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function downloadFileAction(Request $request, File $file)
    {
        $fileFolder = $this->getParameter('paprec.quote_request_file.directory');
        $filePath = $fileFolder . '/' . $file->getSystemName();
        if (file_exists($filePath)) {
            $response = new BinaryFileResponse($filePath);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $file->getOriginalFileName());

            return $response;
        }

        return $this->redirectToRoute('paprec_home_home');

    }

    /**
     * @Route("/faq", name="paprec_home_home_q&a")
     * @Security("has_role('ROLE_USER')")
     *
     * @return Response
     */
    public function qAAction(): Response
    {
        $files = $this->em->getRepository('App:File')->findAll();

        $file = new File();
        $formAddFile = $this->createForm(FileFormType::class, $file, array());

        return $this->render('home/q&a.html.twig', [
            'files' => $files,
            'formAddFile' => $formAddFile->createView()
        ]);
    }
}
