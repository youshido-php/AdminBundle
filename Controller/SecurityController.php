<?php

namespace Youshido\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class SecurityController extends Controller {
    /**
     * @Route("/login", name="admin.login")
     */
    public function loginAction() {

        //$admin = new AdminUser();
        //$admin->setLogin('admin');
        //$admin->setPassword('1');
        //$password = $this->get('security.encoder_factory')->getEncoder($admin)->encodePassword($admin->getPassword(), $admin->getSalt());
        //$admin->setPassword($password);
        //
        //$admin->setIsActive(true);
        //$m = $this->getDoctrine()->getManager();
        //$m->persist($admin);
        //$m->flush();

        $authenticationUtils = $this->get('security.authentication_utils');
        $error               = $authenticationUtils->getLastAuthenticationError();
        $lastUsername        = $authenticationUtils->getLastUsername();
        if ($this->container->has('profiler')) {
            //$this->container->get('profiler')->disable();
        }
        return $this->render('@YAdmin/Security/login.html.twig', array(
            'lastUsername' => $lastUsername,
            'loginError'   => $error,
        ));
    }

    /**
     * @Route("/login_check", name="admin.login_check")
     */
    public function loginCheckAction() {

    }


    /**
     * @Route("/logout", name="admin.logout")
     */
    public function logoutAction() {

    }

}
