LaxCorp billing profile admin bundle
=======================================================

Install 
-------
composer require laxcorp/profile-admin-bundle

Add in app/AppKernel.php
------------------------
```php
$bundles = [
    new LaxCorp\BillingPartnerBundle\ProfileAdminBundle()
]
```
Add in app/congif/routing.yml
------------------------
```php
lax_corp_profile_admin:
    resource: "@ProfileAdminBundle/Resources/config/routing.yml"
```