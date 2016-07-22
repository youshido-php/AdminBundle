<?php
/**
 * Date: 10.07.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\AdminBundle\Service;


use Symfony\Component\DependencyInjection\ContainerAware;

class Security extends ContainerAware
{

    public function isGranted($object, $moduleConfig, $actionName)
    {
        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        if (!empty($moduleConfig['actions'][$actionName]['security'])) {
            $securityConfig = $moduleConfig['actions'][$actionName]['security'];


            if (!empty($securityConfig['roles']) && !$this->checkRoles($securityConfig['roles'])) {
                return false;
            }

            if (!empty($securityConfig['services']) && !$this->checkServices($object, $securityConfig['services'])) {
                return false;
            }
        }

        return true;
    }

    public function checkRoles($roles)
    {
        foreach ((array)$roles as $role) {
            if (!$this->container->get('security.authorization_checker')->isGranted($role)) {
                return false;
            }
        }

        return true;
    }

    public function checkServices($object, $services)
    {
        foreach ((array)$services as $service) {
            $service = $this->container->get('adminContext')->prepareService($service[0]);
            $method  = $service[1];
            $valid   = $service->$method($object);

            if (!$valid) {
                return false;
            }
        }

        return true;
    }

}