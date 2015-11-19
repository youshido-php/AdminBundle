<?php
/**
 * Date: 13.10.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\AdminBundle\Form\Type;


use Symfony\Component\Form\AbstractType;

class ImageType extends AbstractType
{

    public function getParent()
    {
        return 'youshido_file';
    }


    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'image';
    }
}