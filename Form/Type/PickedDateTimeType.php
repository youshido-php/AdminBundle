<?php
/**
 * Date: 13.10.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\AdminBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PickedDateTimeType extends AbstractType
{

    public function getParent()
    {
        return DateType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd HH:SS',
        ]);
    }


    public function getBlockPrefix()
    {
        return 'picked_date_time';
    }

    public function getName()
    {
        return 'picked_date_time';
    }
}
