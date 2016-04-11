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
use Youshido\AdminBundle\Entity\AdminRight;
use Youshido\AdminBundle\Entity\AdminUser;

class AdminSetupCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('admin:setup')
            ->setDescription('Initialize admin structure');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $right = new AdminRight();
        $right->setId('ROLE_SUPER_ADMIN');
        $right->setTitle('Super Admin');

        $admin = new AdminUser();
        $admin->setLogin('admin');
        $admin->setIsActive(true);
        $admin->addRight($right);

        $passwordText = '1';
        $password     = $this->getContainer()->get('security.encoder_factory')->getEncoder($admin)->encodePassword($passwordText, $admin->getSalt());
        $admin->setPassword($password);
        $em = $this->getContainer()->get('doctrine')->getManager();

        $em->persist($admin);
        $em->persist($right);
        $em->flush();

        $output->writeln(sprintf('<info>%s</info>', 'Structure initialized.'));
        $output->writeln(sprintf('<info>%s</info>', 'User "admin" with password "1" created.'));
    }
}