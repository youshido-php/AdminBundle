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
        return array(
            new \Twig_SimpleFunction('routeExist', array($this, 'routeExists')),
        );
    }

    public function routeExists($name)
    {
        $router = $this->container->get('router');

        return (null === $router->getRouteCollection()->get($name)) ? false : true;
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