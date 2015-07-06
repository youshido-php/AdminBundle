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

class AdminAddUserCommand extends ContainerAwareCommand {

    protected function configure() {
        $this
            ->setName('admin:add-user')
            ->setDescription('Add new admin user')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Who do you want to add?'
            )
            ->addArgument(
                'password',
                InputArgument::OPTIONAL,
                'password'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $name         = $input->getArgument('name');
        $passwordText = $input->getArgument('password');
        if (empty($name)) $name = 'admin';

        $admin = new AdminUser();
        $admin->setLogin($name);
        if (empty($passwordText)) {
            $passwordText = substr(md5(time()), 0, 6);
        }

        $admin->setPassword($passwordText);
        $password = $this->getContainer()->get('security.encoder_factory')->getEncoder($admin)->encodePassword($admin->getPassword(), $admin->getSalt());
        $admin->setPassword($password);

        $admin->setIsActive(true);
        $m = $this->getContainer()->get('doctrine')->getManager();
        $m->persist($admin);
        $m->flush();
        $output->writeln('Password set for user "' . $name . '": ' . $passwordText);
        $output->writeln('Executed.');
    }
}