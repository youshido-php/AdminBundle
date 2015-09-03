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

class DictionaryController extends BaseEntityController
{

    /**
     * @Route("/dictionary/{module}/{pageNumber}", name="admin.dictionary.default", requirements={ "pageNumber" : "\d+"})
     */
    public function defaultAction(Request $request, $module, $pageNumber = 1)
    {
        return parent::defaultAction($request, $module, $pageNumber);
    }

    /**
     * @Route("/dictionary/{module}/add", name="admin.dictionary.add")
     */
    public function addAction($module, Request $request)
    {
        return $this->processDetailAction($module, null, $request, 'add');
    }

    /**
     * @Route("/dictionary/{module}/export", name="admin.dictionary.export")
     */
    public function exportAction($module, Request $request)
    {
        return parent::exportAction($module, $request);
    }

    /**
     * @Route("/dictionary/{module}/edit/{id}", name="admin.dictionary.edit")
     */
    public function editAction($module, $id, Request $request)
    {
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
            foreach ($object->getAttributes() as $attr) {
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
    }

    /**
     * @Route("/dictionary/{module}/duplicate/{id}", name="admin.dictionary.duplicate")
     */
    public function duplicateAction($module, $id, Request $request)
    {
        return parent::duplicateAction($module, $id, $request);
    }

    /**
     * @Route("/dictionary/{module}/remove", name="admin.dictionary.remove")
     */
    public function removeAction($module, Request $request)
    {
        return parent::removeAction($module, $request);
    }

    /**
     * @param Request $request
     *
     * @Route("/dictionary/{module}/batch/remove", name="dictionary.batchs.remove")
     */
    public function batchRemoveAction(Request $request, $module)
    {
        if($request->getMethod() == 'POST' && ($ids = $request->get('ids', []))){

            $this->get('adminContext')->setActiveModuleName($module);
            $moduleConfig = $this->get('adminContext')->getActiveModule();

            $query = $this->getDoctrine()->getRepository($moduleConfig['entity'])
                ->createQueryBuilder('t');

            $query
                ->delete()
                ->where($query->expr()->in('t.id', $ids))
                ->getQuery()
                ->execute();

            $this->addFlash('success', 'Entities deleted');
        }

        $referer = $request->headers->get('referer', false);
        if($referer){
            return $this->redirect($referer = $request->headers->get('referer'));
        }

        return $this->redirectToRoute('admin.dashboard');
    }
}
