<?php

namespace Youshido\AdminBundle\Controller;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Youshido\CMSBundle\Structure\Attribute\AttributedTrait;

class DictionaryController extends BaseEntityController {

    /**
     * @Route("/dictionary/{module}", name="admin.dictionary.default")
     * @Route("/dictionary/{module}/page/{pageNumber}", name="admin.dictionary.page")
     */
    public function defaultAction(Request $request, $module, $pageNumber = 1) {
        return parent::defaultAction($request, $module, $pageNumber);
    }

    /**
     * @Route("/dictionary/{module}/add", name="admin.dictionary.add")
     */
    public function addAction($module, Request $request) {
        return $this->processDetailAction($module, null, $request, 'add');
    }

    /**
     * @Route("/dictionary/{module}/edit/{id}", name="admin.dictionary.edit")
     */
    public function editAction($module, $id, Request $request) {
        return $this->processDetailAction($module, null, $request, 'edit');
    }

    /**
     * @Route("/dictionary/{module}/delete-attribute-file/{id}", name="admin.deleteAttributeFile")
     */
    public function deleteAttributeFile($module, $id, Request $request)
    {
        $this->get('adminContext')->setActiveModuleName($module);
        $moduleConfig = $this->get('adminContext')->getActiveModule();

        if (($object = $this->getDoctrine()->getRepository($moduleConfig['entity'])->find($id)) && ($path = $request->get('path'))) {
            /**
             * @var AttributedTrait $object
             */
            $path = $request->get('path');
            $needRefresh = false;
            foreach($object->getAttributes() as $attr) {
                if (strpos($path, $attr->getValue()) !== false) {
                    // todo correct attribute remove
                    $basePath = $this->get('kernel')->getRootDir() . '/../web';
                    if (is_file($basePath . $path)) {
                        unlink($basePath . $path);
                    }
                    $attr->setValue(null);
                    $needRefresh = true;
                }
            }
            if ($needRefresh) {
                $object->setAttributes($object->getAttributes());
                $em = $this->getDoctrine()->getManager();
                $em->persist($object);
                $em->flush();
            }
        }

        return $this->redirectToRoute($moduleConfig['actions']['edit']['route'], ['module' => $moduleConfig['name'], 'id' => $id]);

        //return new JsonResponse([
        //    'status' => 'success'
        //]);

    }
    
    /**
     * @Route("/dictionary/{module}/duplicate/{id}", name="admin.dictionary.duplicate")
     */
    public function duplicateAction($module, $id, Request $request) {
        $this->get('adminContext')->setActiveModuleName($module);
        $moduleConfig = $this->get('adminContext')->getActiveModule();

        if ($id) {
            $object = $this->getDoctrine()->getRepository($moduleConfig['entity'])->find($id);
            $copy = clone $object;
            if (method_exists($copy, 'getTitle')) {
                $copy->setTitle($copy->getTitle() . ' copy');
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($copy);
            $em->flush();
        }
        return $this->redirectToRoute('admin.dictionary.default', array('module' => $module));
    }

    /**
     * @Route("/dictionary/{module}/remove", name="admin.dictionary.remove")
     */
    public function removeAction($module, Request $request) {
        $this->get('adminContext')->setActiveModuleName($module);
        $moduleConfig = $this->get('adminContext')->getActiveModule();

        if ($ids = $request->get('id')) {
            $ids          = array($ids);
            $entities = $this->getDoctrine()->getRepository($moduleConfig['entity'])->findBy(array('id' => $ids));
            $em = $this->getDoctrine()->getManager();
            foreach($entities as $object) {
                $em->remove($object);
            }
            $em->flush();
        }
        return $this->redirectToRoute($moduleConfig['actions']['default']['route'], ['module' => $module]);
    }
}
