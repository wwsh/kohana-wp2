<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * OAuth Access Request
 *
 * @package    Kohana/OAuth
 * @category   Request
 * @author     Kohana Team
 * @copyright  (c) 2010 Kohana Team
 * @license    http://kohanaframework.org/license
 * @since      3.0.7
 */
class Kohana_OAuth_Request_Access extends OAuth_Request
{

    protected $name = 'access';

    protected $required = array(
        'oauth_consumer_key'     => true,
        'oauth_token'            => true,
        'oauth_signature_method' => true,
        'oauth_signature'        => true,
        'oauth_timestamp'        => true,
        'oauth_nonce'            => true,
        'oauth_verifier'         => true,
        'oauth_version'          => true,
    );

    public function execute(array $options = null)
    {
        return OAuth_Response::factory(parent::execute($options));
    }

} // End OAuth_Request_Access
