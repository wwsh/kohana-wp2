<?php defined('SYSPATH') or die('No direct script access.');

/**
 * OAuth Token Request
 *
 * @package    Kohana/OAuth
 * @category   Request
 * @author     Kohana Team
 * @copyright  (c) 2010 Kohana Team
 * @license    http://kohanaframework.org/license
 * @since      3.0.7
 */
class Kohana_OAuth_Request_Token extends OAuth_Request
{

    protected $name = 'request';

    // http://oauth.net/core/1.0/#rfc.section.6.3.1
    protected $required = array(
        'oauth_callback'         => true,
        'oauth_consumer_key'     => true,
        'oauth_signature_method' => true,
        'oauth_signature'        => true,
        'oauth_timestamp'        => true,
        'oauth_nonce'            => true,
        'oauth_version'          => true,
    );

    public function execute(array $options = null)
    {
        return OAuth_Response::factory(parent::execute($options));
    }

} // End OAuth_Request_Token
