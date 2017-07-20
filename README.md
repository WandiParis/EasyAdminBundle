# Wandi/EasyAdminBundle

Wandi/EasyAdminBundle is a Symfony 3 prepacked admin bundle. It includes :
- [javiereguiluz/EasyAdminBundle](https://github.com/javiereguiluz/EasyAdminBundle)
- [dustin10/VichUploaderBundle](https://github.com/dustin10/VichUploaderBundle)
- [egeloen/IvoryCKEditorBundle](https://github.com/egeloen/IvoryCKEditorBundle)
- [ckfinder/ckfinder-symfony3-bundle](https://github.com/ckfinder/ckfinder-symfony3-bundle)


## How to configure

### Install via composer
```
$ composer require wandi/easy-admin-bundle
```

### Registering the bundles
```php
$bundles = [
    // ...
    new \Wandi\EasyAdminBundle\WandiEasyAdminBundle(),
    new \JavierEguiluz\Bundle\EasyAdminBundle\EasyAdminBundle(),
    new \Vich\UploaderBundle\VichUploaderBundle(),
    new \Ivory\CKEditorBundle\IvoryCKEditorBundle(),
    new \CKSource\Bundle\CKFinderBundle\CKSourceCKFinderBundle(),
];
```

### Configuration

* Add required config to ```app/config/config.yml```: 

```yaml
# Easy Admin
easy_admin:
    design:
        assets:
            js:
                - '/bundles/cksourceckfinder/ckfinder/ckfinder.js'
                - '/bundles/wandieasyadmin/js/ckfinder.js'
    entities:
        - AppBundle\Entity\Post
        - AppBundle\Entity\Tag
        # ... 
 
# VichUploader
vich_uploader:
    db_driver: orm
  
# CKFinder
ckfinder:
    connector:
        authenticationClass: Wandi\EasyAdminBundle\Services\CKFinderAuthentication
```

* Add route to ```app/config/routing.yml```: 
```yaml
wandi_easy_admin:
    resource: "@WandiEasyAdminBundle/Resources/config/routing.yml"
    prefix:   /admin
```

**Note**: Here we use the prefix **/admin** for all our admin paths

* Update ```app/config/security.yml``` configuration: 
```yaml
security:
    encoders:
        # ...
        Wandi\EasyAdminBundle\Entity\User: bcrypt
    
    providers:
        # ...    
        wandi_easy_admin:
            entity: { class: 'Wandi\EasyAdminBundle\Entity\User' }
            
    firewalls:
        wandi_easy_admin:
            pattern: '^/admin'
            anonymous: true
            logout:
                path: wandi_easy_admin_logout
            guard:
                authenticators:
                    - wandi_easy_admin.form_authenticator
        # ...

    access_control:
        # ...
        - { path: '^/admin/login', role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: '^/admin/', role: ROLE_EASY_ADMIN }
```

**Important**: Make sure that no firewall declared before our, does not match with the prefix we use

### Update schema

The bundle uses its own **User** entity. So we need to update your database schema.
```
$ php bin/console doctrine:schema:update -f
```

### Commands

* Setup Wandi/EasyAdminBundle **(required)**

It's a shortcut for download and install all the assets for CKEditor and CKFinder
 ```
 $ php bin/console wandi:easy-admin:setup
 ```
 
* Create an admin user
 ```
 $ php bin/console wandi:easy-admin:create-user admin password
 ```
