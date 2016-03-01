<?php
/**
 * Date: 13.10.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\AdminBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Youshido\UploadableBundle\Type\FileType;

class ImageType extends AbstractType
{

    public function getParent()
    {
        return FileType::class;
    }


    public function getBlockPrefix()
    {
        return 'image';
    }

}
