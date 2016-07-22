<?php
/*
 * This file is a part of jobrain-site project.
 *
 * @author Alexandr Viniychuk <a@viniychuk.com>
 * created: 9:17 PM 6/20/15
 */

namespace Youshido\AdminBundle\Service;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;

class FormHelperService extends ContainerAware
{

    public function buildFormItem($column, $info, FormBuilder $formBuilder, $object = null)
    {
        $attr = [
            'data-column' => $info['type']
        ];

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
            case 'entity':
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
                                $service = $this->container->get('adminContext')->prepareService($handler[0]);
                                $method  = $handler[1];

                                $queryBuilder = $service->$method($queryBuilder);
                            }
                        }

                        return $queryBuilder;
                    };
                }

                $formBuilder->add($column, 'entity', $options);
                break;

            case 'autocomplete':
                if (!$object) {
                    throw new \Exception('Autocomplete type not work for filters');
                }

                if (!isset($info['url']['route']) || !$info['url']['route']) {
                    throw new \Exception('You must specify route for autocomplete type');
                }

                $route = $this->container->get('router')->generate($info['url']['route']);
                $options['attr'] = array_merge(
                    isset($options['attr']) && is_array($options['attr']) ? $options['attr'] : [],
                    [
                        'class' => 'form-control js-autocomplete',
                        'data-url' => $route,
                        'data-params' => json_encode(isset($info['url']['params']) ? $info['url']['params'] : [])
                    ]
                );

                if (!isset($info['property']) || !$info['property']) {
                    throw new \Exception('You must specify property for autocomplete type');
                }
                if (!isset($info['entity']) || !$info['entity']) {
                    throw new \Exception('You must specify property for autocomplete type');
                }

                $accessor = PropertyAccess::createPropertyAccessor();
                $value = $accessor->getValue($object, $info['property']);
                $repository = $this->container->get('doctrine')->getRepository($info['entity']);

                $multiple = isset($info['multiple']) && $info['multiple'] == true;
                $options['multiple'] = $multiple;
                $options['required'] = false;

                if ($multiple) {
                    if (is_array($value) || $value instanceof \IteratorAggregate) {
                        foreach ($value as $valueItem) {
                            $options['choices'][$valueItem->getId()] = $valueItem->__toString();
                        }
                    } else {
                        $options['choices'] = [];
                    }
                } else {
                    if ($value) {
                        $options['choices'] = [$value->getId() => is_object($value) ? $value->__toString() : $value];
                    }
                }


                $formBuilder->add($column, 'choice', $options);
                $formBuilder->get($column)
                    ->resetViewTransformers()
                    ->addModelTransformer(new CallbackTransformer(
                        function ($object) use ($multiple) {
                            if ($multiple) {
                                if (is_array($object) || $object instanceof \IteratorAggregate) {
                                    $result = [];
                                    foreach ($object as $objectItem) {
                                        $result[] = (string)$objectItem->getId();
                                    }

                                    return $result;
                                }
                            } else {
                                return $object ? (string)$object->getId() : null;
                            }
                        },
                        function ($submittedValue) use ($multiple, $repository, $column) {
                            if ($submittedValue) {
                                if ($multiple && is_array($submittedValue)) {
                                    $collection = new ArrayCollection();

                                    foreach ($submittedValue as $id) {
                                        $value = $repository->find($id);

                                        if ($value) {
                                            $collection->add($value);
                                        }
                                    }

                                    return $collection;
                                } else {
                                    return $repository->find($submittedValue);
                                }
                            }

                            return $submittedValue;
                        }
                    ));

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