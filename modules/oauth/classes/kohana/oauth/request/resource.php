<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * OAuth Resource Request
 *
 * @package    Kohana/OAuth
 * @category   Request
 * @author     Kohana Team
 * @copyright  (c) 2010 Kohana Team
 * @license    http://kohanaframework.org/license
 * @since      3.0.7
 */
class Kohana_OAuth_Request_Resource extends OAuth_Request
{

    protected $name = 'resource';

    // http://oauth.net/core/1.0/#rfc.section.7
    protected $required = array(
        'oauth_consumer_key'     => true,
        'oauth_token'            => true,
        'oauth_signature_method' => true,
        'oauth_signature'        => true,
        'oauth_timestamp'        => true,
        'oauth_nonce'            => true,
        'oauth_version'          => true,
    );

} // End OAuth_Request_Resource
