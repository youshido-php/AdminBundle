<?php
/**
 * Date: 01.03.16
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\AdminBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Youshido\AdminBundle\Form\Transformer\DateRangeViewTransformer;
use Youshido\AdminBundle\Form\Validator\DateRangeValidator;

class DateRangeType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('start_date', 'date', array_merge_recursive([
                'property_path'  => 'start',
                'widget'         => 'single_text',
                'format'         => 'yyyy-MM-dd',
                'model_timezone' => 'UTC',
                'view_timezone'  => 'UTC',
                'attr'           => [
                    'data-type'   => 'start',
                    'data-column' => 'date'
                ],
            ], $options['start_options']))
            ->add('end_date', 'date', array_merge_recursive([
                'property_path'  => 'end',
                'widget'         => 'single_text',
                'format'         => 'yyyy-MM-dd',
                'model_timezone' => 'UTC',
                'view_timezone'  => 'UTC',
                'attr'           => [
                    'data-type'   => 'end',
                    'data-column' => 'date'
                ],
            ], $options['end_options']));

        $builder->addViewTransformer($options['transformer']);
        $builder->addEventSubscriber($options['validator']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'    => 'Youshido\AdminBundle\Form\Model\DateRange',
            'end_options'   => [],
            'start_options' => [],
            'transformer'   => null,
            'validator'     => null,
        ]);

        $resolver->setNormalizer('transformer', function (Options $options, $value) {
            if (!$value) {
                $value = new DateRangeViewTransformer(new OptionsResolver());
            }

            return $value;
        });

        $resolver->setNormalizer('validator', function (Options $options, $value) {
            if (!$value) {
                $value = new DateRangeValidator(new OptionsResolver());
            }

            return $value;
        });
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'date_range';
    }
}