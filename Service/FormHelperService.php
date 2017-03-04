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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Youshido\AdminBundle\Form\Type\HtmlType;
use Youshido\AdminBundle\Form\Type\ImageType;
use Youshido\AdminBundle\Form\Type\PickedDateTimeType;
use Youshido\AdminBundle\Form\Type\PickedDateType;
use Youshido\AdminBundle\Form\Type\WysiwygType;

class FormHelperService
{

    use ContainerAwareTrait;

    public function buildFormItem($column, $info, FormBuilder $formBuilder)
    {
        if (!is_array($info)) {
            $info = [
                'title' => $column,
                'type' => $info
            ];
        }

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
                                $service = (string)$handler[0];
                                $method  = (string)$handler[1];

                                $queryBuilder = $this->container->get('admin.context')
                                    ->prepareService($service)->$method($queryBuilder);
                            }
                        }

                        return $queryBuilder;
                    };
                }

                $formBuilder->add($column, EntityType::class, $options);
                break;

            default:
                $formBuilder->add($column, $this->getClassForType($info['type']), $options);
        }
    }

    protected function getClassForType($type)
    {
        $types = [
            'text' => TextType::class,
            'choice' => ChoiceType::class,
            'checkbox' => CheckboxType::class,
            'date' => PickedDateType::class,
            'datetime' => PickedDateTimeType::class,
            'wysiwyg' => WysiwygType::class,
            'image' => ImageType::class,
            'html' => HtmlType::class,
        ];
        if (strpos($type, '\\') === false) {

        }
        return !empty($types[$type]) ? $types[$type] : $type;
    }


    public function get($service)
    {
        return $this->container->get($service);
    }

}
