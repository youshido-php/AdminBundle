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
use Symfony\Component\Form\Extension\Core\DataTransformer\BooleanToStringTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Youshido\CMSBundle\Structure\Attribute\AttributedInterface;
use Youshido\CMSBundle\Structure\Attribute\BaseAttribute;

class FormHelperService extends ContainerAware {

    /**
     * @param AttributedInterface $object
     * @return Form
     */
    public function getVarsFormForAttributes($object)
    {
        /**
         * @var FormBuilder $formBuilder
         */
        $formBuilder = $this->container->get('form.factory')->createNamedBuilder('form'.$object->getId(), 'form', $object->getAttributesViewValues());
        foreach ($object->getAttributesFormFields() as $field) {
            $this->buildFormItem($field['name'], $field, $formBuilder, $object);
        }
        return $formBuilder->getForm();
    }

    public function buildFormItem($column, $info, FormBuilder $formBuilder, $object = null)
    {
        $options = array('attr' => array('class' => '', 'autocomplete' => 'off'));

        if (in_array($info['type'], ['text', 'integer'])) {
            $options['attr']['class'] = 'form-control';
        } elseif ($info['type'] == 'entity') {
            $options['attr']['class'] = 'form-control';
            if (!empty($info['multiple'])) {
                $options['attr']['class'] .= ' basic-tags w500';
            }
        }
        if (!empty($info['mask'])) {
            $options['attr']['data-mask'] = $info['mask'];
            $options['attr']['class'] .= ' input-mask';
        }
        if (!empty($info['placeholder'])) {
            $options['attr']['placeholder'] = $info['placeholder'];
        }
        if (array_key_exists('required', $info)){
            $options['required'] = (bool) $info['required'];
        }
        if (array_key_exists('readonly', $info)){
            $options['read_only'] = (bool) $info['readonly'];
        }
        if (array_key_exists('title', $info)){
            $options['label'] = $info['title'];
        }

        if(array_key_exists('description', $info) && $info['description']){
            $options['attr']['help'] = $info['description'];
        }

        switch ($info['type']) {
            case 'date':
                //$transformer = new DateTimeToStringTransformer();
                $options['attr']['class'] .= 'form-control';
                $options['widget'] = 'single_text';
                $options['format'] = 'yyyy-MM-dd';
                //$formBuilder->add(
                //    $formBuilder->create($column, 'text', $options)->addModelTransformer($transformer)
                //);
                $formBuilder->add($column, 'date', $options);
                break;
            case 'entity':
                $options = array_merge(array(
                    'class' => $info['entity'],
                    'required' => false,
                    'placeholder' => 'Any ' . $info['title'],
                    'label' => $info['title']
                ), $options);

                if (!empty($info['where']) || !empty($info['handler'])) {
                    $options['query_builder'] = function (EntityRepository $er) use ($info) {
                        $queryBuilder = $er->createQueryBuilder('t');

                        if(!empty($info['where'])){
                            $queryBuilder
                                ->where($info['where']);
                        }

                        if(!empty($info['handler'])){
                            $handlers = (array) $info['handler'];

                            foreach($handlers as $handler){
                                $queryBuilder = $this->container->get('adminContext')
                                    ->prepareService($handler[0])->$handler[1]($queryBuilder);
                            }
                        }

                        return $queryBuilder;
                    };
                }
                if (!empty($info['required'])) {
                    unset($options['placeholder']);
                }
                if (!empty($info['groupBy'])) {
                    $options['group_by'] = $info['groupBy'];
                }
                if (!empty($info['multiple'])) {
                    $options['multiple'] = true;
                }
                $formBuilder->add($column, 'entity', $options);
                break;
            case 'collection':
                $options = array_merge(array(
                    'type' => new $info['form'](),
                    'allow_add' => true,
                    'allow_delete' => true,
                ), $options);

                $formBuilder->add($column, 'collection', $options);
                break;
            case 'textarea':
            case 'html':
            case 'wysiwyg':
                $formBuilder->add($column, 'textarea', $options);
                break;
            case 'boolean':
            case 'checkbox':
                $formBuilder->add($column, 'checkbox', $options);
                break;
            case 'file':
                $options = array_merge(array(
                    'entity_class' => !empty($info['entity']) ? $info['entity'] : null,
                    'entity_property' => !empty($info['entity_property']) ? $info['entity_property'] : $column
                ), $options);

                $formBuilder->add($column, 'youshido_file', $options);
                break;
            case 'image':
                $options = array_merge(array(
                    'required' => false,
                    'entity_class' => !empty($info['entity']) ? $info['entity'] : null,
                    'entity_property' => !empty($info['entity_property']) ? $info['entity_property'] :  $column
                ), $options);

                $formBuilder->add($column, 'youshido_file', $options);
                break;
            case 'label':
                $formBuilder->add($column, 'hidden', $options);
                break;
            case 'hidden':
                $formBuilder->add($column, 'hidden', $options);
                break;
            case 'choice':
                $options['choices'] = $info['choices'];

                if (!empty($info['multiple'])) {
                    $options['multiple'] = true;
                }

                $formBuilder->add($column, 'choice', $options);
                break;
            case 'integer':
                $formBuilder->add($column, 'integer', $options);
                break;

            case 'autocomplete':
                if(!$object){
                    throw new \Exception('Autocomplete type not work for filters');
                }

                if(!isset($info['url']['route']) || !$info['url']['route']){
                    throw new \Exception('You must specify route for autocomplete type');
                }

                $route = $this->container->get('router')->generate($info['url']['route']);
                $options['attr'] = array_merge(
                    isset($options['attr']) && is_array($options['attr']) ? $options['attr'] : [],
                    [
                        'class'       => 'form-control js-autocomplete',
                        'data-url'    => $route,
                        'data-params' => json_encode(isset($info['url']['params']) ? $info['url']['params'] : [])
                    ]
                );

                if(!isset($info['property']) || !$info['property']){
                    throw new \Exception('You must specify property for autocomplete type');
                }
                if(!isset($info['entity']) || !$info['entity']){
                    throw new \Exception('You must specify property for autocomplete type');
                }

                $accessor =  PropertyAccess::createPropertyAccessor();
                $value = $accessor->getValue($object, $info['property']);
                $repository = $this->container->get('doctrine')->getRepository($info['entity']);

                $multiple = isset($info['multiple']) && $info['multiple'] == true;
                $options['multiple'] = $multiple;
                $options['required'] = false;

                if($multiple){
                    if(is_array($value) || $value instanceof \IteratorAggregate ){
                        foreach($value as $valueItem){
                            $options['choices'][$valueItem->getId()] = $valueItem->__toString();
                        }
                    }else{
                        $options['choices'] = [];
                    }
                }else{
                    if($value){
                        $options['choices'] = [$value->getId() => is_object($value) ? $value->__toString() : $value];
                    }
                }


                $formBuilder->add($column, 'choice', $options);
                $formBuilder->get($column)
                    ->resetViewTransformers()
                    ->addModelTransformer(new CallbackTransformer(
                        function ($object) use ($multiple) {
                            if($multiple){
                                if(is_array($object) || $object instanceof \IteratorAggregate){
                                    $result = [];
                                    foreach($object as $objectItem){
                                        $result[] = (string) $objectItem->getId();
                                    }

                                    return $result;
                                }
                            }else{
                                return $object ? (string) $object->getId() : null;
                            }
                        },
                        function ($submittedValue) use($multiple, $repository, $column) {
                            if($submittedValue){
                                if($multiple && is_array($submittedValue)){
                                    $collection = new ArrayCollection();

                                    foreach($submittedValue as $id){
                                        $value = $repository->find($id);

                                        if($value){
                                            $collection->add($value);
                                        }
                                    }

                                    return $collection;
                                }else{
                                    return $repository->find($submittedValue);
                                }
                            }

                            return $submittedValue;
                        }
                    ));

                break;

            default:
                $formBuilder->add($column, 'text', $options);

        }
    }


    public function get($service) {
        return $this->container->get($service);
    }

}