<?php
/**
 * Date: 01.03.16
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\AdminBundle\Form\Transformer;


use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Youshido\AdminBundle\Form\Model\DateRange;

class DateRangeViewTransformer implements DataTransformerInterface
{

    protected $options = [];

    public function __construct(OptionsResolver $resolver, array $options = [])
    {
        $this->setDefaultOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    public function setDefaultOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'include_end' => true,
        ]);

        $resolver->setAllowedValues([
            'include_end' => [true, false],
        ]);
    }

    public function transform($value)
    {
        if (!$value) {
            return null;
        }
        if (!$value instanceof DateRange) {
            throw new UnexpectedTypeException($value, 'Youshido\AdminBundle\Form\Model\DateRange');
        }

        return $value;
    }

    public function reverseTransform($value)
    {
        if (!$value) {
            return null;
        }

        if (!$value instanceof DateRange) {
            throw new UnexpectedTypeException($value, 'Youshido\AdminBundle\Form\Model\DateRange');
        }

        if ($this->options['include_end']) {
            if ($value->end) {
                $value->end->setTime(23, 59, 59);
            }
        }

        return $value;
    }
}