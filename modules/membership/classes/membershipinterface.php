<?php
/*
 Membership interface.
 */

defined('SYSPATH') or die('No direct script access.');


interface MembershipInterface
{

    public static function instance($force_new = false);

    /**
     * Log in a user
     * @param Model_Identity Identity to log in as
     */
    public function login(Model_Identity $identity);

    /**
     * log out the currently logged in user
     */
    public function logout();

    /**
     * Check if user is logged in
     * @return bool True if logged in, false otherwise
     */
    public function logged_in();

    /**
     * Get the details of the member currently logged in
     * @return Model_Member Logged in member
     */
    public function get_member();

    /**
     * Get the currently used identity of the member
     */
    public function get_identity();

    public function get_roles();

    public function is_admin();
}

