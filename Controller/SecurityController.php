<?php

namespace Youshido\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Youshido\AdminBundle\Entity\AdminUser;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="admin.login")
     */
    public function loginAction()
    {
        $authenticationUtils = $this->get('security.authentication_utils');
        $error               = $authenticationUtils->getLastAuthenticationError();
        $lastUsername        = $authenticationUtils->getLastUsername();

        return $this->render('@YAdmin/Security/login.html.twig', [
            'lastUsername' => $lastUsername,
            'loginError'   => $error,
        ]);
    }

    /**
     * @Route("/login_check", name="admin.login_check")
     */
    public function loginCheckAction()
    {

    }


    /**
     * @Route("/logout", name="admin.logout")
     */
    public function logoutAction()
    {

    }

    /**
     * @param Request $request
     * @return Response
     *
     * @Route("/user/new", name="admin.user.new")
     */
    public function newUserAction(Request $request)
    {
        $adminContext = $this->get('admin.context');
        $config       = $adminContext->getActiveModuleForAction('add', 'admin-users');

        /** @var AdminUser $user */
        $user = new $config['entity']();
        $user->setIsActive(true);

        $form = $this->createFormBuilder($user, [
            'action' => $this->generateUrl('admin.user.new'),
            'attr'   => ['class' => 'form-horizontal']
        ])
            ->add('login', null, ['attr' => ['data-column' => 'text']])
            ->add('password', RepeatedType::class, [
                'type'            => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'options'         => ['attr' => ['data-column' => 'text']],
                'required'        => true,
                'first_options'   => ['label' => 'Password'],
                'second_options'  => ['label' => 'Repeat Password'],
            ])
            ->add('rights', null, ['attr' => ['style' => 'width: 400px;', 'data-column' => 'select']])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            if ($user->getRights()->count()) {
                /** @var PasswordEncoderInterface $encoder */
                $encoder  = $this->get('security.encoder_factory')->getEncoder($user);
                $password = $encoder->encodePassword($user->getPassword(), $user->getSalt());

                $user
                    ->setPassword($password)
                    ->setIsActive(true);

                $m = $this->get('doctrine')->getManager();

                $m->persist($user);
                $m->flush();

                $this->addFlash('success', 'New user has been added!');

                return $this->redirectToRoute('admin.dictionary.default', ['module' => 'admin-users']);
            } else {
                $form->get('rights')->addError(new FormError('Minimum one right need to be added.'));
            }
        }

        $vars = [
            'object'       => $user,
            'moduleConfig' => $this->get('admin.context')->getActiveModuleForAction('create'),
            'form'         => $form->createView(),
        ];

        return $this->render('@YAdmin/Security/new-user.html.twig', $vars);
    }

}
