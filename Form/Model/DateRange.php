<?php
/**
 * Date: 01.03.16
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\AdminBundle\Form\Model;


use Youshido\AdminBundle\Form\Model\Interfaces\RangableFormTypeInterface;

class DateRange implements RangableFormTypeInterface
{
    /**
     * @var \DateTime
     */
    public $start;

    /**
     * @var \DateTime
     */
    public $end;

    public function __construct(\DateTime $start = null, \DateTime $end = null)
    {
        $this->start = $start ?: (new \DateTime())->modify('first day of this month')->setTime(0, 0, 0);
        $this->end   = $end ?: (new \DateTime())->modify('last day of this month')->setTime(23, 59, 59);
    }

    public function getStart()
    {
        return $this->start;
    }

    public function getEnd()
    {
        return $this->end;
    }
}