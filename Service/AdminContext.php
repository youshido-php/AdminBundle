<?php
/**
 * Created by PhpStorm.
 * User: mounter
 * Date: 4/29/15
 * Time: 1:29 PM
 */

namespace Youshido\AdminBundle\Service;


use Doctrine\Common\Util\Inflector;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

class AdminContext
{

    protected $_modules          = array();
    protected $_activeModuleName = 'dashboard';
    protected $_config;
    protected $_container;
    protected $_guesser;
    protected $_isInitialized    = false;

    public function __construct(ContainerInterface $container = null)
    {
        $this->_container = $container;

        $locator       = new FileLocator([$container->get('kernel')->getRootDir() . '/config/admin/']);
        $configPath    = $locator->locate('structure.yml');
        $this->_config = Yaml::parse(file_get_contents($configPath));
        if (!empty($this->_config['imports'])) {
            foreach ($this->_config['imports'] as $info) {
                $configPath    = $locator->locate($info['resource']);
                $this->_config = array_merge_recursive($this->_config, Yaml::parse(file_get_contents($configPath)));
            }
            unset($this->_config['imports']);
        }
        $this->_guesser = new DoctrineOrmTypeGuesser($this->_container->get('doctrine'));
        //throw new \Exception();
        //$this->initialize();
    }

    protected function initialize()
    {
        if ($this->_isInitialized) return true;
        $this->_isInitialized = true;
        if ($this->isAuthorized()) {
            foreach ($this->_config['modules'] as $key => $info) {
                if (!empty($info['security'])) {
                    $roles = explode(',', $info['security']);
                    if (!$this->isGranted($roles)) {
                        unset($this->_config['modules'][$key]);
                        continue;
                    }
                }
                $this->_modules[$key] = $this->processModuleStructure($info, $key);
            }

            foreach ($this->_modules as $key => $module) {
                if (!empty($module['group'])) {
                    $this->_modules[$module['group']]['nodes'][$key] = $module;
                }
            }
            foreach ($this->_modules as $key => $module) {
                if (empty($module['type']) || $module['type'] == "Group" && empty($module['nodes'])) {
                    unset($this->_modules[$key]);
                }
            }
            $this->_config['modules'] = $this->_modules;
        }
    }

    public function getName()
    {
        return $this->_config['name'];
    }

    public function getActiveModule()
    {
        return $this->_modules[$this->_activeModuleName];
    }

    public function getCurrentObject()
    {
        return [
            "id"    => $this->_container->get('request')->get('id')
        ];
    }

    public function updateModuleStructure($moduleName, $structure, $key = null)
    {
        if (!$key) {
            $this->_modules[$moduleName] = $structure;
        } else {
            $this->_modules[$moduleName][$key] = array_merge($this->_modules[$moduleName][$key], $structure);
        }
    }

    public function isAuthorized()
    {
        return $this->getToken() && $this->getToken()->getRoles();
    }

    public function getToken()
    {
        return $this->get('security.token_storage')->getToken();
    }

    public function isSuperAdmin()
    {
        return $this->isGranted('ROLE_ROOT');
    }

    public function isGranted($roles)
    {
        $granted = $this->getToken()->getUser()->getRoles();
        if (in_array('ROLE_ROOT', $granted)) return true;
        foreach ((array)$roles as $role) {
            if (!in_array($role, $granted)) return false;
        }
        return true;
    }

    public function getActiveModuleForAction($actionName, $moduleName = null)
    {
        $this->initialize();
        return $this->applyActionConfig($moduleName ? $this->_modules[$moduleName] : $this->getActiveModule(), $actionName);
    }

    public function setActiveModuleName($moduleName)
    {
        $this->initialize();
        $this->_activeModuleName                              = $moduleName;
        $config                                               = $this->_modules[$this->_activeModuleName];
        $this->_modules[$this->_activeModuleName]['isActive'] = true;
        if (!empty($config['group'])) {
            $this->_modules[$config['group']]['isActive']                                    = true;
            $this->_modules[$config['group']]['nodes'][$this->_activeModuleName]['isActive'] = true;
        }
    }

    public function isMlt()
    {
        return false;
    }

    public function getLanguages()
    {

    }

    public function getStructure()
    {
        $this->initialize();
        return array(
            'modules' => $this->_modules,
        );
    }

    protected function applyActionConfig($structure, $actionName)
    {
        if (!empty($structure['actions'][$actionName])) {
            $s = $structure['actions'][$actionName];
            if (!empty($s['show']) && !empty($structure['columns'])) {
                foreach ($structure['columns'] as $columnName => $info) {
                    if (!in_array($columnName, $s['show'])) unset($structure['columns'][$columnName]);
                }
            } elseif (!empty($s['hide']) && !empty($structure['columns'])) {
                foreach ($s['hide'] as $columnName) {
                    if (!empty($structure['columns'][$columnName])) unset($structure['columns'][$columnName]);
                }
            }
        }
        return $structure;
    }

    protected function processModuleStructure($structure, $key)
    {
        if (empty($structure['type'])) $structure['type'] = 'dictionary';
        if (empty($structure['name'])) $structure['name'] = Inflector::tableize($key);
        if (empty($structure['title'])) $structure['title'] = ucfirst(Inflector::classify($key));
        if (empty($structure['entity'])) $structure['entity'] = 'AppBundle\Entity\\' . Inflector::classify($key);
        if (empty($structure['link']) && ($structure['type'] !== 'Group')) {
            $structure['link'] = $this->_container->get('router')->generate('admin.'.$structure['type'].'.default', [
                'module'    => $structure['name']
            ]);
        }
        if (empty($structure['tabs'])) $structure['tabs'] = [];
        if (!empty($structure['columns'])) {
            foreach ($structure['columns'] as $columnName => &$columnInfo) {
                $this->processColumnInfo($columnName, $columnInfo, $structure);
            }
        }
        foreach ($structure['tabs'] as $alias => $info) {
            $params = is_array($info) ? $info : array();
            if (empty($params['title'])) {
                $params['title'] = is_string($info) ? $info : ucfirst($alias);
            }
            if ($alias == "general" && empty($params['template'])) {
                $params['template'] = '@YAdmin/_fragments/formFields.html.twig';
            }
            $structure['tabs'][$alias] = $params;
        }
        $structure['actions'] = $this->processActions($structure);
        return $structure;
    }

    protected function processActions($config)
    {
        if (empty($config['actions'])) {
            $config['actions'] = array();
        }
        foreach ($config['actions'] as $key => $info) {
            $params = is_array($info) ? $info : array();
            if (!empty($params['security']) && !$this->isGranted($params['security'])) {
                unset($config['actions'][$key]);
                continue;
            }
            if (empty($params['route'])) {
                $params['route'] = is_string($info) ? $info : 'admin.' . $config['type'] . '.' . $key;
            }
            if (empty($params['title'])) {
                $params['title'] = ucfirst($key);
            }
            $config['actions'][$key] = $params;
        }
        return $config['actions'];
    }

    protected function processColumnInfo($columnName, &$columnInfo, $structure)
    {
        if (empty($columnInfo['type'])) {
            $guess              = $this->_guesser->guessType($structure['entity'], $columnName);
            $columnInfo['type'] = $guess->getType();
        }
        if (empty($columnInfo['title'])) $columnInfo['title'] = Inflector::classify($columnName);
    }

    protected function get($id)
    {
        return $this->_container->get($id);
    }
}