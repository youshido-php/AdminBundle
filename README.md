# AdminBundle

### 1. Install required bundles via composer: 
``` console
composer require youshido/admin
composer require liip/imagine-bundle
composer require stof/doctrine-extensions-bundle
```

### 2. Enable bundles:
``` php
//AppKernel.php

$bundles = [
    //...

    new Youshido\AdminBundle\YAdminBundle(),
    new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
    new Liip\ImagineBundle\LiipImagineBundle(),
];
```

### 3. Create your own Admin Bundle:
php app/console generate:bundle --namespace=AdminBundle --bundle-name=AdminBundle --dir=src

### 4. Create action in your AdminBundle default controller:
``` php
//AdminBundle\Controller\DefaultController

/**
* @return \Symfony\Component\HttpFoundation\Response
*
* @Route("/", name="admin.dashboard")
* @Route("/dashboard", name="project.admin.dashboard")
*/
public function indexAction()
{
  $this->get('adminContext')->setActiveModuleName('dashboard');

  return $this->render('YAdminBundle:Default:index.html.twig',
      [
          'siteStatistics' => null,
          'widgets' => [

          ],
      ]
  );
}
```

### 5. Enable routing:
``` yaml
//routing.yml

admin:
    resource: "@YAdminBundle/Controller/"
    type:     annotation
    prefix:   /admin

app_admin: #your admin bundle
    resource: "@AdminBundle/Controller/"
    type:     annotation
    prefix:   /admin

_liip_imagine:
    resource: "@LiipImagineBundle/Resources/config/routing.xml"
```

### 6. Add to your config.yml:
``` yaml
//config.yml

stof_doctrine_extensions:
    orm:
        default:
          timestampable: true

liip_imagine:
    resolvers:
       default:
          web_path: ~

    filter_sets:
        cache: ~
        my_thumb:
            quality: 75
            filters:
                thumbnail: { size: [120, 90], mode: outbound }

!!!
twig:
    //...

    globals:
        adminContext: @adminContext
```

### 7. Add to your security.yml
``` yaml
    role_hierarchy:
        ROLE_ADMIN:       [ROLE_USER]
        ROLE_SUPER_ADMIN: [ROLE_ADMIN]

    providers:
        admin_provider:
            entity:
                class: Youshido\AdminBundle\Entity\AdminUser
                property: login


    encoders:
        Youshido\AdminBundle\Entity\AdminUser: md5

    firewalls:
        admin_free:
            pattern: ^/admin/login$
            context: admin
            anonymous: ~

        admin_firewall:
            pattern: ^/admin
            provider: admin_provider
            context: admin
            form_login:
              login_path: admin.login
              check_path: admin.login_check
            logout:
                path:   admin.logout
                target: /admin
```

### 8. Create 'structure.yml' file in app/config/admin directory with content:
``` yaml
name: "Your project name"
modules:
  dashboard:
    icon: fa fa-home
    route: admin.dashboard
    type: ~

imports:
    - { resource: ../../../vendor/youshido/admin/Resources/config/admin/structure.admin.yml }
```

### 9. Run in console
``` console
php app/console doctrine:schema:update --force
php app/console admin:setup
```

### 10. Generate configuration for you entity
``` console
php app/console admin:generate
```

#####Don't forget to import generated file to structure.yml