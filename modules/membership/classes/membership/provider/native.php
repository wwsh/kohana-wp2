<?php

defined('SYSPATH') or die('No direct script access.');

/**
 * This module emulates OAuth for native top80 logins.
 * There are two separate authentification systems. This module is tieing them together.
 * A regular account created at our page can be converted into membership.
 * Diagram: users <--------------> members / identities :)
 *
 * @throws Exception
 *
 */
class Membership_Provider_Native extends Membership_Provider
{
    const AUTHORIZE_URL = 'account/authorize';
    const TOKEN_URL     = 'account/access_token';
    const PROFILE_URL   = 'account/me';

    public function startLogin()
    {
        $data = array(
            //'client_id' => $this->settings['client_id'],
            'redirect_uri' => $this->return_url,
        );

        header('Location: ' . url::base(true) . self::AUTHORIZE_URL . '?' . http_build_query($data, null, '&'));
        die();
    }

    public function verifyLogin()
    {
        $data = array(
            'client_id'     => $this->settings['client_id'],
            'redirect_uri'  => $this->return_url,
            'client_secret' => $this->settings['client_secret'],
            'code'          => $_GET['code'],
        );

        // Get an access token
        $url = url::base(true) . self::TOKEN_URL . '?' . http_build_query($data, null, '&');
        Kohana::$log->add(LOG_DEBUG, 'Access token url: ' . $url)->write();
        $result = file_get_contents($url);
        parse_str($result, $result_array);

        // Make sure we actually have a token
        if (empty($result_array['access_token'])) {
            throw new Exception('Invalid native response received. Response = "' . $result . '"');
        }

        // Grab the user's data
        $access_token = $result_array['access_token'];
        $user         = (array)json_decode(file_get_contents(url::base(true) . self::PROFILE_URL . '?access_token=' . $access_token));
        if (!is_array($user)) {
            throw new Exception('Invalid native user data returned');
        }

        return $user;

    }
}

?>