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
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

class AdminContext
{

    use ContainerAwareTrait;

    protected $modules = [];
    protected $activeModuleName = 'dashboard';
    protected $config;
    protected $guesser;
    protected $isInitialized = false;

    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->guesser   = new DoctrineOrmTypeGuesser($this->container->get('doctrine'));

        $cachePath        = $container->getParameter('kernel.cache_dir') . '/AdminCache.php';
        $userMatcherCache = new ConfigCache($cachePath, true);

        if (!$userMatcherCache->isFresh()) {
            $locator    = new FileLocator([$container->get('kernel')->getRootDir() . '/config/admin']);
            $configPath = $locator->locate('structure.yml');

            $resources = [];

            $resources[] = new FileResource($configPath);
            $config      = Yaml::parse(file_get_contents($configPath));

            if (!empty($config['imports'])) {
                foreach ($config['imports'] as $info) {
                    $configPath  = $locator->locate($info['resource']);
                    $resources[] = new FileResource($configPath);

                    $config = array_merge_recursive($config, Yaml::parse(file_get_contents($configPath)));
                }
                unset($config['imports']);
            }

            $userMatcherCache->write(sprintf('<?php return %s;', var_export($config, true)), $resources);
        }

        $this->config = require $cachePath;
    }

    protected function initialize()
    {
        if ($this->isInitialized) {
            return true;
        }

        $this->isInitialized = true;

        if ($this->isAuthorized()) {
            foreach ($this->config['modules'] as $key => $info) {
                if (!empty($info['security'])) {
                    $security = (array)$info['security'];

                    if (array_key_exists('conditions', $security)) {
                        foreach ($security['conditions'] as $showCondition) {
                            if (!($this->prepareService($showCondition[0])->{$showCondition[1]}())) {
                                continue 2;
                            }
                        }

                        $roles = array_key_exists('roles', $security) ? $security['roles'] : [];
                    } else {
                        $roles = $security;
                    }

                    if (!$this->isGranted($roles)) {
                        unset($this->config['modules'][$key]);
                        continue;
                    }
                }

                $this->modules[$key] = $this->processModuleStructure($info, $key);
            }

            foreach ($this->modules as $key => $module) {
                if (!empty($module['group'])) {
                    $this->modules[$module['group']]['nodes'][$key] = $module;
                }
            }
            foreach ($this->modules as $key => $module) {
                if (empty($module['type']) || $module['type'] == "Group" && empty($module['nodes'])) {
                    unset($this->modules[$key]);
                }
            }
            $this->config['modules'] = $this->modules;
        }

        return true;
    }

    public function getName()
    {
        return $this->config['name'];
    }

    public function getUseInternationalization()
    {
        return isset($this->config['internationalization']['enable']) && $this->config['internationalization']['enable'];
    }

    public function getInternationalizationConfig()
    {
        if (!$this->getUseInternationalization()) {
            return false;
        }

        return $this->config['internationalization'];
    }

    public function getActiveModule()
    {
        if (array_key_exists($this->activeModuleName, $this->modules)) {
            return $this->modules[$this->activeModuleName];
        }

        return null;
    }

    public function getCurrentObject()
    {
        return [
            "id" => $this->container->get('request_stack')->getCurrentRequest()->get('id')
        ];
    }

    public function updateModuleStructure($moduleName, $structure, $key = null)
    {
        if (!$key) {
            $this->modules[$moduleName] = $structure;
        } else {
            if (array_key_exists($key, $this->modules[$moduleName])) {
                $this->modules[$moduleName][$key] = array_merge($this->modules[$moduleName][$key], $structure);
            } else {
                $this->modules[$moduleName][$key] = $structure;
            }

        }
    }

    public function isAuthorized()
    {
        return $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY');
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
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if (!$this->get('security.authorization_checker')->isGranted($role)) {
                    return false;
                }
            }
        } else {
            if (!$this->get('security.authorization_checker')->isGranted($roles)) {
                return false;
            }
        }

        return true;
    }

    public function getActiveModuleForAction($actionName, $moduleName = null)
    {
        $this->initialize();

        return $this->applyActionConfig($moduleName ? $this->modules[$moduleName] : $this->getActiveModule(), $actionName);
    }

    public function setActiveModuleName($moduleName)
    {
        $this->initialize();
        $this->activeModuleName                             = $moduleName;
        $config                                             = $this->modules[$this->activeModuleName];
        $this->modules[$this->activeModuleName]['isActive'] = true;
        if (!empty($config['group'])) {
            $this->modules[$config['group']]['isActive']                                   = true;
            $this->modules[$config['group']]['nodes'][$this->activeModuleName]['isActive'] = true;
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

        return [
            'modules' => $this->modules,
        ];
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
            $params = is_array($info) ? $info : [];
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
            $config['actions'] = [];
        }
        foreach ($config['actions'] as $key => $info) {
            $params = is_array($info) ? $info : [];

            if (!empty($params['security']) && !empty($params['security']['roles'])) {
                if (!$this->get('admin.security')->checkRoles($params['security']['roles'])) {
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
            $guess              = $this->guesser->guessType($structure['entity'], $columnName);
            $columnInfo['type'] = $guess->getType();
        }
        if (empty($columnInfo['title'])) $columnInfo['title'] = Inflector::classify($columnName);
        if (!array_key_exists('required', $columnInfo)) $columnInfo['required'] = true;
    }

    public function getBackUrl()
    {
        $module = $this->getActiveModule();

        if (!empty($module['back_url_handler'])
            && ($backUrl = $this->prepareService($module['back_url_handler'][0])->{$module['back_url_handler'][1]}())
        ) {
            return $backUrl;
        } else {
            return $this->generateModuleLink($module['type'], $module['name']);
        }
    }

    protected function generateModuleLink($type, $module)
    {
        return $this->container->get('router')->generate('admin.' . $type . '.default', [
            'module' => $module
        ]);
    }

    protected function get($id)
    {
        return $this->container->get($id);
    }

    public function prepareService($service)
    {
        return $this->get(str_replace('@', '', $service));
    }

    public function crateOrderLink($orderField, $order)
    {
        $module = $this->getActiveModule();
        $uri    = $this->generateModuleLink($module['type'], $module['name']);

        $parameters               = $_GET;
        $parameters['orderField'] = $orderField;
        $parameters['order']      = $order;

        return $uri . '?' . http_build_query($parameters);
    }
}
