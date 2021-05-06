<?php

namespace App\Command;

use App\Entity\User;
use Predis\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserCommand extends Command
{
    private $logger;
    private $container;
    private $passwordEncoder;

    public function __construct(ContainerInterface $container, UserPasswordEncoderInterface $passwordEncoder)
    {
//        $this->logger = $logger;

        parent::__construct();

        $this->container = $container;
        $this->passwordEncoder = $passwordEncoder;
    }

    protected function configure()
    {
        $this
            ->setName('egd:create-admin-user');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $user = new User();
        $user->setUsername('admin');
        $user->setEmail('frederic.laine@eggers-digital.com');
        $user->setEnabled(true);
        $user->setPassword($this->passwordEncoder->encodePassword($user, 'admin'));
        $user->setRoles(['ROLE_ADMIN']);

        $errors = $this->container->get('validator')->validate($user);

        if(count($errors)){
            $output->write((string) $errors);
            exit;
        }

        $this->container->get('doctrine.orm.entity_manager')->persist($user);
        $this->container->get('doctrine.orm.entity_manager')->flush();
        $output->write('Admin User added');
        $output->writeln('');

    }
}
