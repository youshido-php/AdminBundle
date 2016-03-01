<?php
/*
 * This file is a part of jobrain-site project.
 *
 * @author Alexandr Viniychuk <a@viniychuk.com>
 * created: 9:17 PM 6/20/15
 */

namespace Youshido\AdminBundle\Service;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Form\FormBuilder;

class FormHelperService
{

    use ContainerAwareTrait;

    public function buildFormItem($column, $info, FormBuilder $formBuilder)
    {
        $attr = [];
        if(isset($info['options']['attr'])){
            $attr = array_merge($info['options']['attr'], $attr);
        }

        $options = array_merge(isset($info['options']) ? $info['options'] : [], [
            'attr' => $attr
        ]);

        if (isset($info['title']) && !isset($options['label'])) {
            $options['label'] = $info['title'];
        }

        switch ($info['type']) {
            case EntityType::class:
                if (!empty($info['where']) || !empty($info['handler'])) {
                    $options['query_builder'] = function (EntityRepository $er) use ($info) {
                        $queryBuilder = $er->createQueryBuilder('t');

                        if (!empty($info['where'])) {
                            $queryBuilder
                                ->where($info['where']);
                        }

                        if (!empty($info['handler'])) {
                            $handlers = (array)$info['handler'];

                            foreach ($handlers as $handler) {
                                $queryBuilder = $this->container->get('admin.context')
                                    ->prepareService($handler[0])->$handler[1]($queryBuilder);
                            }
                        }

                        return $queryBuilder;
                    };
                }

                $formBuilder->add($column, EntityType::class, $options);
                break;

            default:
                $formBuilder->add($column, $info['type'], $options);
        }
    }


    public function get($service)
    {
        return $this->container->get($service);
    }

}
