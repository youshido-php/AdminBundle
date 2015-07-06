<?php

namespace Youshido\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Yaml\Yaml;

class DefaultController extends Controller {
    /**
     * @Route("/", name="admin.homepage")
     * @Route("/dashboard", name="admin.dashboard")
     */
    public function indexAction() {
        $this->get('adminContext')->setActiveModuleName('dashboard');

        //$companies  = $this->getDoctrine()->getRepository('AppBundle:Company')->getDashboardList();
        //$therapists = $this->getDoctrine()->getRepository('AppBundle:Therapist')->findAll();

        return $this->render('YAdminBundle:Default:index.html.twig',
            [
            'siteStatistics' => null,
            'widgets'        => [
        //        'companies' => [
        //            'title'   => 'Recent Companies',
        //            'type'    => 'table',
        //            'actions' => true,
        //            'objects' => $companies,
        //            'config'  => $this->get('adminContext')->getActiveModuleForAction('dashboard', 'company'),
        //        ],
        //        'therapist' => [
        //            'title'   => 'Recent Therapists',
        //            'type'    => 'table',
        //            'actions' => true,
        //            'objects' => $therapists,
        //            'config'  => $this->get('adminContext')->getActiveModuleForAction('dashboard', 'therapist'),
        //        ],
            ],
        ]
        );
    }

}
