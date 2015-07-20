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
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Parser;
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
        $this->_guesser = new DoctrineOrmTypeGuesser($this->_container->get('doctrine'));

        $cachePath = $container->getParameter('kernel.cache_dir').'/AdminCache.php';
        $userMatcherCache = new ConfigCache($cachePath, true);

        if (!$userMatcherCache->isFresh()) {
            $locator       = new FileLocator([$container->get('kernel')->getRootDir() . '/config/admin']);
            $configPath    = $locator->locate('structure.yml');

            $resources = [];

            $resources[] = new FileResource($configPath);
            $config = Yaml::parse(file_get_contents($configPath));

            if (!empty($config['imports'])) {
                foreach ($config['imports'] as $info) {
                    $configPath    = $locator->locate($info['resource']);
                    $resources[] = new FileResource($configPath);

                    $config = array_merge_recursive($config, Yaml::parse(file_get_contents($configPath)));
                }
                unset($config['imports']);
            }

            $userMatcherCache->write(sprintf('<?php return %s;', var_export($config, true)), $resources);
        }

        $this->_config = require $cachePath;
    }

    protected function initialize()
    {
        if ($this->_isInitialized) return true;
        $this->_isInitialized = true;
        if ($this->isAuthorized()) {
            foreach ($this->_config['modules'] as $key => $info) {
                if (!empty($info['security'])) {
                    $security = (array) $info['security'];

                    if(array_key_exists('conditions', $security)){
                        foreach ($security['conditions'] as $showCondition) {
                            if(!($this->prepareService($showCondition[0])->{$showCondition[1]}())){
                                continue 2;
                            }
                        }

                        $roles = array_key_exists('roles', $security) ? $security['roles'] : [];
                    }else{
                        $roles = $security;
                    }

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
            if (array_key_exists($key, $this->_modules[$moduleName])) {
                $this->_modules[$moduleName][$key] = array_merge($this->_modules[$moduleName][$key], $structure);
            }else{
                $this->_modules[$moduleName][$key] = $structure;
            }

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
        if(is_array($roles)){
            foreach($roles as $role){
                if (!$this->get('security.authorization_checker')->isGranted($role)) {
                    return false;
                }
            }
        }else{
            if (!$this->get('security.authorization_checker')->isGranted($roles)) {
                return false;
            }
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
            $structure['link'] = $this->generateModuleLink($structure['type'], $structure['name']);
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

            if (!empty($params['security']) && !empty($params['security']['roles'])) {
                if(!$this->get('admin.security')->checkRoles($params['security']['roles'])){
                    unset($config['actions'][$key]);

                    continue;
                }
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
        if (!array_key_exists('required', $columnInfo)) $columnInfo['required'] = true;
    }

    public function getBackUrl()
    {
        $module = $this->getActiveModule();

        if(!empty($module['back_url_handler'])
            && ($backUrl = $this->prepareService($module['back_url_handler'][0])->{$module['back_url_handler'][1]}())){
            return $backUrl;
        }else{
           return $this->generateModuleLink($module['type'], $module['name']);
        }
    }

    protected function generateModuleLink($type, $module)
    {
        return $this->_container->get('router')->generate('admin.'.$type.'.default', [
            'module'    => $module
        ]);
    }

    protected function get($id)
    {
        return $this->_container->get($id);
    }

    public function prepareService($service)
    {
        return $this->get(str_replace('@', '', $service));
    }
}