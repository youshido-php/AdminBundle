<?php
/**
 * Created by PhpStorm.
 * User: mounter
 * Date: 4/29/15
 * Time: 3:59 PM
 */

namespace Youshido\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Youshido\AdminBundle\Entity\AdminUser;

class CreateUserCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('admin:create-user')
            ->setDescription('Add new admin user')
            ->addArgument(
                'login',
                InputArgument::REQUIRED,
                'Who do you want to add?'
            )
            ->addArgument(
                'password',
                InputArgument::REQUIRED,
                'password'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $login        = $input->getArgument('login');
        $passwordText = $input->getArgument('password');

        $em = $this->getContainer()->get('doctrine')->getManager();

        $user = $em
            ->getRepository('YAdminBundle:AdminUser')
            ->findOneBy(['login' => $login]);

        if ($user) {
            $output->writeln(sprintf('<error>%s</error>', 'User with same login already exists'));

            return;
        }

        $user = new AdminUser();

        $password = $this->getContainer()
            ->get('security.encoder_factory')
            ->getEncoder($user)
            ->encodePassword($passwordText, $user->getSalt());

        $user
            ->setLogin($login)
            ->setPassword($password)
            ->setIsActive(true);


        $em->persist($user);
        $em->flush();

        $output->writeln(sprintf('<info>Created user with login "%s" and password "%s"</info>', $login, $passwordText));
    }
}