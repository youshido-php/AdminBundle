<?php

namespace Youshido\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Youshido\AdminBundle\Entity\AdminUser;

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

    /**
     * @param Request $request
     * @return Response
     *
     * @Route("/user/new", name="admin.user.new")
     */
    public function newUserAction(Request $request)
    {
        $adminContext = $this->get('adminContext');
        $config = $adminContext->getActiveModuleForAction('add', 'admin-users');

        $user = new $config['entity']();
        $user->setIsActive(true);

        $form = $this->createFormBuilder($user, [
            'action' => $this->generateUrl('admin.user.new'),
            'attr' => ['class' => 'form-horizontal']
        ])
            ->add('login', null, ['attr' => ['class' => 'form-control']])
            ->add('isActive', null, ['attr' => ['class' => 'form-control'], 'required' => false])
            ->add('password', 'repeated', array(
                'type' => 'password',
                'invalid_message' => 'The password fields must match.',
                'options' => array('attr' => array('class' => 'form-control')),
                'required' => true,
                'first_options'  => array('label' => 'Password'),
                'second_options' => array('label' => 'Repeat Password'),
            ))
            ->add('rights', null, ['attr' => ['style' => 'width: 400px;']])
            ->getForm();

        $form->handleRequest($request);

        if($form->isValid()){
            $password = $this->get('security.encoder_factory')->getEncoder($user)->encodePassword($user->getPassword(), $user->getSalt());
            $user->setPassword($password);

            $m = $this->get('doctrine')->getManager();

            $m->persist($user);
            $m->flush();

            $this->redirectToRoute('admin.dictionary.default', ['module' => 'admin-users']);
        }

        $vars = [
            'object'       => $user,
            'moduleConfig' => $this->get('adminContext')->getActiveModuleForAction('create'),
            'form'         => $form->createView(),
        ];

        return $this->render('@YAdmin/Security/new_user.html.twig', $vars);
    }

}
