<?php
/**
 * Date: 01.03.16
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\AdminBundle\Form\Validator;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Youshido\AdminBundle\Form\Model\DateRange;

class DateRangeValidator implements EventSubscriberInterface
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
            'allow_end_in_past' => false,
            'allow_single_day'  => true,
        ]);

        $resolver->setAllowedValues([
            'allow_end_in_past' => [true, false],
            'allow_single_day'  => [true, false],
        ]);
    }

    public function onPostSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        /* @var $dateRange DateRange */
        $dateRange = $form->getNormData();

        if ($dateRange->start > $dateRange->end) {
            $form->addError(new FormError('date_range.invalid.end_before_start'));
        }

        if (!$this->options['allow_single_day'] and ($dateRange->start->format('Y-m-d') === $dateRange->end->format('Y-m-d'))) {
            $form->addError(new FormError('date_range.invalid.single_day'));
        }

        if (!$this->options['allow_end_in_past'] and ($dateRange->end < new \DateTime())) {
            $form->addError(new FormError('date_range.invalid.end_in_past'));
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SUBMIT => 'onPostSubmit',
        ];
    }
}