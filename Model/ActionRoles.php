<?php

namespace LaxCorp\ProfileAdminBundle\Model;

/**
 * @inheritdoc
 */
class ActionRoles
{

    /**
     * @inheritdoc
     */
    public static function editRoles()
    {
        return [
            'ROLE_CLIENT_PROFILE_ADMIN',
            'ROLE_SUPER_ADMIN'
        ];
    }

    /**
     * @inheritdoc
     */
    public static function showRoles()
    {
        return [
            'ROLE_CLIENT_PROFILE_ADMIN',
            'ROLE_CLIENT_PROFILE_SHOW',
            'ROLE_APP_ADMIN_CLIENT_VIEW',
            'ROLE_SUPER_ADMIN'
        ];
    }

    /**
     * @inheritdoc
     */
    public static function listRoles()
    {
        return self::showRoles();
    }

    /**
     * @inheritdoc
     */
    public static function createRoles()
    {
        return self::editRoles();
    }

    /**
     * @inheritdoc
     */
    public static function enableRoles()
    {
        return self::editRoles();
    }

    /**
     * @inheritdoc
     */
    public static function disableRoles()
    {
        return self::editRoles();
    }

    /**
     * @inheritdoc
     */
    public static function deleteRoles()
    {
        return self::editRoles();
    }

    /**
     * @inheritdoc
     */
    public static function byAction(string $action)
    {
        switch ($action) {
            case 'edit':
                return self::editRoles();
            case 'show':
                return self::showRoles();
            case 'list':
                return self::listRoles();
            case 'create':
                return self::createRoles();
            case 'enable':
                return self::enableRoles();
            case 'disable':
                return self::disableRoles();
            case 'delete':
                return self::deleteRoles();
        }

        return [];
    }

}