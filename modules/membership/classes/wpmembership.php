<?php
/*
 * WordPress compatible Membership.
 * Based on classic Membership.
 * Same interface.
 */

defined('SYSPATH') or die('No direct script access.');

class WPMembership implements MembershipInterface
{

    private static $instance;


    protected function __construct()
    {
    }

    public static function instance($force_new = false)
    {
        if (self::$instance == null || $force_new) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param $string
     * @return array
     */
    public static function get_by_role($string)
    {
        $users = get_users('role=' . $string);
        if (empty($users)) {
            return [];
        }

        $users = array_map(
            function (WP_User $user) {
                $set       = (array)$user->data;
                $set['id'] = $set['ID'];
                return $set;
            },
            $users
        );

        return $users;
    }

    public static function get_by_username($username)
    {
        $set = [];

        $user = get_user_by('login', $username);

        if (empty($user)) {
            global $wpdb;
            if (!$user = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $wpdb->users WHERE display_name = %s", $username
            ))
            ) {
                return false;
            }
            $set = (array)$user;
        }

        if (isset($user->data)) {
            $set = (array)$user->data;
        }
        $set['id']   = $set['ID'];
        $set['name'] = $set['display_name'];

        return (object)$set;
    }

    /**
     * Log in a user
     * @param Model_Identity Identity to log in as
     */
    public function login(Model_Identity $identity)
    {
        // todo?
    }

    /**
     * log out the currently logged in user
     */
    public function logout()
    {
        // todo?
    }

    /**
     * Check if user is logged in
     * @return bool True if logged in, false otherwise
     */
    public function logged_in()
    {
        return isset($GLOBALS['current_user']) && !empty($GLOBALS['current_user']->ID);
    }

    /**
     * Get the details of the member currently logged in
     * @return Model_Member Logged in member
     */
    public function get_member()
    {
        if (!$this->logged_in()) {
            return false;
        }

        $result       = $GLOBALS['current_user'];
        $result->name = $result->data->display_name;
        $result->id   = $result->ID; // to not cause mess in code
        return $result;
    }

    /**
     * @return null
     */
    public function get_member_id()
    {
        if (!$this->logged_in()) {
            return null;
        }

        $result = $GLOBALS['current_user'];
        return $result->ID;
    }

    /**
     * Get the currently used identity of the member
     */
    public function get_identity()
    {
        return $this->get_member();
    }

    public function get_roles()
    {
        if (!$this->logged_in()) {
            return false;
        }

        return $this->get_member()->roles;
    }

    public function is_admin()
    {
        $roles = $GLOBALS['current_user']->roles;

        return in_array('administrator', $roles);
    }

    public function is_radiodj()
    {
        $roles = $GLOBALS['current_user']->roles;

        return in_array('radiodj', $roles);
    }
}

