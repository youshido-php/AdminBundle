<?php
/*
 * This file is a part of jobrain-site project.
 *
 * @author Alexandr Viniychuk <a@viniychuk.com>
 * created: 9:17 PM 6/20/15
 */

namespace Youshido\AdminBundle\Service;


use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Form\Extension\Core\DataTransformer\BooleanToStringTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
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
            $this->buildFormItem($field['name'], $field, $formBuilder);
        }
        return $formBuilder->getForm();
    }

    public function buildFormItem($column, $info, FormBuilder $formBuilder)
    {
        $options = array('attr' => array('class' => '', 'autocomplete' => 'off'));

        if ($info['type'] == 'text') {
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

        switch ($info['type']) {
            case 'date':
                $transformer = new DateTimeToStringTransformer();

                $formBuilder->add(
                    $formBuilder->create($column, 'text', $options)->addModelTransformer($transformer)
                );
                break;
            case 'entity':
                $options = array_merge(array(
                    'class' => $info['entity'],
                    'required' => false,
                    'placeholder' => 'Any ' . $info['title'],
                    'label' => $info['title']
                ), $options);

                if (!empty($info['where'])) {
                    $options['query_builder'] = function (EntityRepository $er) use ($info) {
                        return $er->createQueryBuilder('m')
                            ->where($info['where']);
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
                $formBuilder->add($column, 'textarea', $options);
                break;
            case 'html':
                $formBuilder->add($column, 'textarea', $options);
                break;
            case 'boolean':
            case 'checkbox':
                $formBuilder->add($column, 'checkbox', $options);
                break;
            case 'file':
                $options = array_merge(array(
                    'entity_class' => 'Youshido\\Bundle\\CMSBundle\\Structure\\Attribute\\FileAttribute',
                    'entity_property' => 'value'
                ), $options);

                $formBuilder->add($column, 'youshido_file', $options);
                break;
            case 'image':
                $options = array_merge(array(
                    'required' => false,
                    'entity_class' => 'Youshido\\Bundle\\CMSBundle\\Structure\\Attribute\\ImageAttribute',
                    'entity_property' => 'value'
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

                break;
            default:
                $formBuilder->add($column, 'text', $options);

        }
    }


    public function get($service) {
        return $this->container->get($service);
    }

}