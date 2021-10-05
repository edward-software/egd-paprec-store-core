<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\UserEditType;
use App\Form\UserMyProfileType;
use App\Form\UserType;
use App\Service\NumberManager;
use App\Tools\DataTable;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\Exception as PSException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swift_Mailer;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends AbstractController
{
    private $em;
    private $numberManager;
    private $translator;

    public function __construct(
        EntityManagerInterface $em,
        NumberManager $numberManager,
        TranslatorInterface $translator
    ) {
        $this->em = $em;
        $this->addressManager = $numberManager;
        $this->translator = $translator;
    }
    
    /**
     * @Route("/", name="paprec_user_index")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return Response
     */
    public function indexAction()
    {
        return $this->render('user/index.html.twig');
    }
    
    /**
     * @Route("/loadList", name="paprec_user_loadList")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request            $request
     * @param DataTable          $dataTable
     * @param PaginatorInterface $paginator
     *
     * @return JsonResponse
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

        $cols['username'] = ['label' => 'username', 'id' => 'u.username', 'method' => ['getUsername']];
        $cols['firstName'] = ['label' => 'firstName', 'id' => 'u.firstName', 'method' => ['getFirstName']];
        $cols['lastName'] = ['label' => 'lastName', 'id' => 'u.lastName', 'method' => ['getLastName']];
        $cols['email'] = ['label' => 'email', 'id' => 'u.email', 'method' => ['getEmail']];
        $cols['enabled'] = ['label' => 'enabled', 'id' => 'u.enabled', 'method' => ['isEnabled']];
        $cols['dateCreation'] = ['label' => 'dateCreation', 'id' => 'u.dateCreation', 'method' => ['getDateCreation'], 'filter' => [
            ['name' => 'format', 'args' => ['Y-m-d H:i:s']]]
        ];
        $cols['id'] = ['label' => 'id', 'id' => 'u.id', 'method' => ['getId']];
        

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select(['u'])
            ->from(User::class, 'u')
            ->where('u.deleted IS NULL');

        $searchValue = null;
        if (is_array($search) && isset($search['value']) && $search['value'] != '') {
            $searchValue = $search['value'];
        }

        if ($searchValue !== null) {
            if (substr($searchValue, 0, 1) === '#') {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->eq('u.id', '?1')
                ))->setParameter(1, substr($searchValue, 1));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->orx(
                    $queryBuilder->expr()->like('u.username', '?1'),
                    $queryBuilder->expr()->like('u.firstName', '?1'),
                    $queryBuilder->expr()->like('u.lastName', '?1'),
                    $queryBuilder->expr()->like('u.email', '?1'),
                    $queryBuilder->expr()->like('u.dateCreation', '?1')
                ))->setParameter(1, '%' . $searchValue . '%');
            }
        }
        $dt = $dataTable->generateTable($cols, $queryBuilder, $pageSize, $start, $orders, $columns, $filters,
            $paginator, $rowPrefix);

        // Reformatage de certaines données
        $tmp = array();
        foreach ($dt['data'] as $data) {
            $line = $data;
            $line['enabled'] = $data['enabled'] ?$this->translator->trans('General.1') : $this->translator->trans('General.0');
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
     * @Route("/export", name="paprec_user_export")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request             $request
     *
     * @return StreamedResponse
     * @throws PSException
     */
    public function exportAction(Request $request)
    {

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select(['u'])
            ->from(User::class, 'u')
            ->where('u.deleted is NULL');

        /** @var User[] $users */
        $users = $queryBuilder->getQuery()->getResult();

        /** @var Spreadsheet $spreadsheet */
        $spreadsheet = new Spreadsheet();

        $spreadsheet
            ->getProperties()
            ->setCreator("Paprec Shop")
            ->setLastModifiedBy("Paprec Shop")
            ->setTitle("Paprec Shop - Users")
            ->setSubject("Extact");

        $spreadsheet
            ->setActiveSheetIndex(0)
            ->setCellValue('A1', 'ID')
            ->setCellValue('B1', 'Company')
            ->setCellValue('C1', 'First name')
            ->setCellValue('D1', 'Last name')
            ->setCellValue('E1', 'Email')
            ->setCellValue('F1', 'Username')
            ->setCellValue('G1', 'Roles')
            ->setCellValue('H1', 'Postal codes')
            ->setCellValue('I1', 'Enabled')
            ->setCellValue('J1', 'Creation date');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Users');

        $i = 2;

        if ($users && is_iterable($users) && count($users)) {
            foreach ($users as $user) {
                $roles = [];

                if ($user && is_iterable($user->getRoles()) && count($user->getRoles()))
                    foreach ($user->getRoles() as $role) {
                        if ($role !== 'ROLE_USER') {
                            $roles[] = $this->translator->trans($role);
                        }
                    }

                $postalCodesArr = [];
                if ($user && is_iterable($user->getPostalCodes()) && count($user->getPostalCodes())) {

                    $postalCodes = $user->getPostalCodes();
                    foreach ($postalCodes as $postalCode) {
                        $postalCodesArr[] = $postalCode->getCode();
                    }
                }

                $spreadsheet
                    ->setActiveSheetIndex(0)
                    ->setCellValue('A' . $i, $user->getId())
                    ->setCellValue('B' . $i, $user->getCompanyName())
                    ->setCellValue('C' . $i, $user->getFirstName())
                    ->setCellValue('D' . $i, $user->getLastName())
                    ->setCellValue('E' . $i, $user->getEmail())
                    ->setCellValue('F' . $i, $user->getUsername())
                    ->setCellValue('G' . $i, implode(',', $roles))
                    ->setCellValue('H' . $i, implode(',', $postalCodesArr))
                    ->setCellValue('I' . $i, $this->translator->trans('General.' . $user->isEnabled()))
                    ->setCellValue('J' . $i, $user->getDateCreation()->format('Y-m-d'));
                $i++;
            }
        }

        $fileName = 'Paprec-Shop-Extract-Users-' . date('Y-m-d') . '.xlsx';

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
     * @Route("/view/{id}", name="paprec_user_view")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param User    $user
     *
     * @return Response
     */
    public function viewAction(Request $request, User $user)
    {
        if ($user->getDeleted() !== null) {
            throw new NotFoundHttpException();
        }

        return $this->render('user/view.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @Route("/add", name="paprec_user_add")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function addAction(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        /** @var User $user */
        $user = new User();

        $roles = [];
        foreach ($this->getParameter('security.role_hierarchy.roles') as $role => $children) {
            $roles[$role] = $role;
        }

        $languages = [];
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }

        $form = $this->createForm(UserType::class, $user, [
            'roles' => $roles,
            'languages' => $languages
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = $form->getData();
            $user->setDateCreation(new DateTime);
            $user->setPassword($passwordEncoder->encodePassword(
                $user,
                $user->getPassword()
            ));

            $this->em->persist($user);
            $this->em->flush();

            return $this->redirectToRoute('paprec_user_view', [
                'id' => $user->getId()
            ]);
        }

        return $this->render('user/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/edit/{id}", name="paprec_user_edit")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param User    $user
     *
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function editAction(Request $request, User $user, UserPasswordEncoderInterface $passwordEncoder)
    {
        if ($user->getDeleted() !== null) {
            throw new NotFoundHttpException();
        }

        $roles = [];
        foreach ($this->getParameter('security.role_hierarchy.roles') as $role => $children) {
            $roles[$role] = $role;
        }

        $languages = [];
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }

        $form = $this->createForm(UserEditType::class, $user, [
            'roles' => $roles,
            'languages' => $languages
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = $form->getData();
            $user->setDateUpdate(new DateTime);
            $user->setPassword($passwordEncoder->encodePassword(
                $user,
                $user->getPassword()
            ));

            $this->em->flush();

            return $this->redirectToRoute('paprec_user_view', [
                'id' => $user->getId()
            ]);
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    /**
     * @Route("/editMyProfile", name="paprec_user_editMyProfile")
     * @Security("has_role('ROLE_USER')")
     *
     * @param Request                      $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     *
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function editMyProfileAction(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        /** @var User $user */
        $user = $this->getUser();

        $languages = [];
        foreach ($this->getParameter('paprec_languages') as $language) {
            $languages[$language] = $language;
        }

        $form = $this->createForm(UserMyProfileType::class, $user, [
            'languages' => $languages
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $user->setPassword($passwordEncoder->encodePassword(
                $user,
                $user->getPassword()
            ));
            $user->setDateUpdate(new DateTime);

            $this->em->flush();

            return $this->redirectToRoute('paprec_home_home');
        }

        return $this->render('user/editMyProfile.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    /**
     * @Route("/sendAccess/{id}", name="paprec_user_sendAccess")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request                 $request
     * @param User                    $user
     * @param Swift_Mailer            $mailer
     * @param TokenGeneratorInterface $tokenGenerator
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function sendAccessAction(Request $request, User $user, Swift_Mailer $mailer, TokenGeneratorInterface $tokenGenerator, UserPasswordEncoderInterface $passwordEncoder)
    {
        if (!$user->isEnabled()) {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('errors', 'userIsNotEnabled');

            return $this->redirectToRoute('paprec_user_view', [
                'id' => $user->getId()
            ]);
        }

        $password = substr($tokenGenerator->generateToken(), 0, 8);
        $encodedpassword = $passwordEncoder->encodePassword(
            $user, $password);

        $user->setPassword($encodedpassword);
        $user->setDateUpdate(new DateTime);

        $this->em->flush();

        $message = (new Swift_Message('Paprec : Identifiants'))
            ->setFrom($_ENV['MAILER_SENDER'])
            ->setTo($user->getEmail())
            ->setBody(
                $this->render(
                    'user/sendAccessEmail.html.twig', [
                        'user' => $user,
                        'password' => $password,
                    ]
                ),
                'text/html'
            )
        ;

        if ($mailer->send($message)) {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', 'accessHasBeenSent');
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', 'accessCannotBeSent');
        }

        return $this->redirectToRoute('paprec_user_view', [
            'id' => $user->getId()
        ]);
    }

    /**
     * @Route("/sendAccessMany/{ids}", name="paprec_user_sendAccessMany")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request                 $request
     * @param Swift_Mailer            $mailer
     * @param TokenGeneratorInterface $tokenGenerator
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function sendAccessManyAction(Request $request, Swift_Mailer $mailer, TokenGeneratorInterface $tokenGenerator)
    {

        $ids = $request->get('ids');

        if (!$ids) {
            throw new NotFoundHttpException();
        }

        $ids = explode(',', $ids);

        if (is_array($ids) && count($ids)) {

            /** @var User[] $users */
            $users = $this->em->getRepository(User::class)->findById($ids);

            foreach ($users as $user) {
                if ($user->isEnabled()) {
                    $password = substr($tokenGenerator->generateToken(), 0, 8);

                    $user->setPassword($password);
                    $user->setDateUpdate(new DateTime);
                    $this->em->flush();

                    $message = (new Swift_Message('BKF : Identifiants'))
                        ->setFrom($_ENV['MAILER_paprec_SENDER'])
                        ->setTo($user->getEmail())
                        ->setBody(
                            $this->render(
                                'user/sendAccessEmail.html.twig', [
                                    'user' => $user,
                                    'password' => $password,
                                ]
                            ),
                            'text/html'
                        )
                    ;

                    if ($mailer->send($message)) {
                        $this
                            ->get('session')
                            ->getFlashBag()
                            ->add('success', [
                                'msg' => 'accessHasBeenSent',
                                'var' => $user->getEmail()
                            ]);
                    } else {
                        $this
                            ->get('session')
                            ->getFlashBag()
                            ->add('error', [
                                'msg' => 'accessCannotBeSent',
                                'var' => $user->getEmail()
                            ]);
                    }
                }
            }
        }

        return $this->redirectToRoute('paprec_user_index');
    }

    /**
     * @Route("/remove/{id}", name="paprec_user_remove")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param User    $user
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function removeAction(Request $request, User $user)
    {

        $deletedUsername = substr($user->getUsername() .  uniqid('', true), 0, 255);
        $deletedEmail = substr($user->getEmail() .  uniqid('', true), 0, 255);

        $user->setUsername($deletedUsername);
        $user->setEmail($deletedEmail);

        $user->setDeleted(new DateTime);
        $user->setEnabled(false);
        $this->em->flush();

        return $this->redirectToRoute('paprec_user_index');
    }

    /**
     * @Route("/removeMany/{ids}", name="paprec_user_removeMany")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     *
     * @throws Exception
     */
    public function removeManyAction(Request $request)
    {
        $ids = $request->get('ids');

        if (!$ids) {
            throw new NotFoundHttpException();
        }

        $ids = explode(',', $ids);

        if (is_array($ids) && count($ids)) {

            /** @var User[] $users */
            $users = $this->em->getRepository(User::class)->findById($ids);

            foreach ($users as $user) {
                /**
                 * On modifie l'email et l'username qui sont uniques dans FOSUser
                 * Ainsi on pourra de nouveau ajouté qqun avec le même username
                 */
                $deletedUsername = substr($user->getUsername() . uniqid('', true), 0, 255);
                $deletedEmail = substr($user->getEmail() .  uniqid('', true), 0, 255);
                $user->setUsername($deletedUsername);
                $user->setEmail($deletedEmail);

                $user->setDeleted(new DateTime);
                $user->setEnabled(false);
            }
            $this->em->flush();
        }

        return new JsonResponse([
            'resultCode' => 1
        ]);
    }
}
