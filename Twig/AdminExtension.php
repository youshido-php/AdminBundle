<?php
/**
 * Date: 07.07.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\AdminBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class AdminExtension extends \Twig_Extension
{

    use ContainerAwareTrait;

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('routeExist', [$this, 'routeExists']),
            new \Twig_SimpleFunction('isTypeOf', [$this, 'isTypeOf']),
        ];
    }

    public function routeExists($name)
    {
        $router = $this->container->get('router');

        return (null === $router->getRouteCollection()->get($name)) ? false : true;
    }

    public function isTypeOf($object, $type)
    {
        if (!is_object($object)) {
            throw new \Exception('First argument must be object for "isTypeOf" twig function ');
        }

        return get_class($object) === $type;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'admin_extension';
    }
}