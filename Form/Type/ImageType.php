<?php
/**
 * Date: 13.10.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\AdminBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Youshido\UploadableBundle\Type\UploadableFileType;

class ImageType extends AbstractType
{

    public function getParent()
    {
        return UploadableFileType::class;
    }


    public function getBlockPrefix()
    {
        return 'image';
    }

    public function getName()
    {
        return 'image';
    }

}
