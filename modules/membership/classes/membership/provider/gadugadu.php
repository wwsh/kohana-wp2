<?php
/*
 * Kohana-Membership
 * Copyright (C) 2011, Daniel Lo Nigro (Daniel15) <daniel at dan.cx>
 * http://go.dan.cx/kohana-membership
 * 
 * This file is part of Kohana-Membership.
 * 
 * Kohana-Membership is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Kohana-Membership is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Kohana-Membership.  If not, see <http://www.gnu.org/licenses/>.
 */

defined('SYSPATH') or die('No direct script access.');

class Membership_Provider_GaduGadu extends Membership_Provider
{
    const AUTHORIZE_URL = 'https://login.gg.pl/authorize';
    const TOKEN_URL     = 'https://auth.api.gg.pl/token';
    const PROFILE_URL   = 'https://pubdir.api.gg.pl/users/me';

    /**
     * GG API version
     */
    const VERSION = '1.0';

    protected $scopes = array(
        'pubdir' => 'https://pubdir.api.gg.pl',
        'users'  => 'https://users.api.gg.pl',
        'life'   => 'https://life.api.gg.pl',
    );
    /**
     * @desc Typ formatu odpowiedzi z serwera
     */
    protected $responseType = '';
    /**
     * Czy analizować odpowiedź serwera
     */
    protected $parseResponse = true;
    /**
     * @desc Ostatnia odpowiedź serwera
     */
    protected $response;
    /**
     * @desc Tablica ostatnich błędów
     */
    private $lastError;
    /**
     * @desc Ostatnie nagłówki
     */
    private $lastHeaders;
    /**
     * @desc Czas odpowiedzi
     */
    protected $requestTimeout = 3;
    /**
     * @desc Identyfikator aplikacji
     */
    private $client_id = null;
    /**
     * @desc Hasło aplikacji
     */
    private $client_secret = null;
    /**
     * @desc Token użytkownika
     */
    private $access_token = null;
    /**
     * @desc Dane do odnowienia tokenu użytkownika
     */
    private $refresh_token = null;

    public function startLogin()
    {
        $data = array(
            'client_id'     => $this->settings['client_id'],
            'redirect_uri'  => $this->return_url,
            'type'          => 'web_server',
            'scope'         => join(' ', array_keys($this->scopes)),
            'response_type' => 'code',
        );

        header('Location: ' . self::AUTHORIZE_URL . '?' . http_build_query($data, null, '&'));
        die();
    }

    public function verifyLogin()
    {
        $data = array(
            'client_id'     => $this->settings['client_id'],
            'client_secret' => $this->settings['client_secret'],
            'code'          => $_GET['code'],
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $this->return_url,
        );

        // Get an access token
        $result = $this->_doRequest('POST', self::TOKEN_URL, $data);

        $result_array = $result;

        // Make sure we actually have a token
        if (empty($result_array['access_token'])) {
            throw new Exception('Invalid response received from GG. Response = "' . $result . '"');
        }

        // Grab the user's data
        $this->access_token = $access_token = $result_array['access_token'];
        $user               = $this->_doRequest('GET', self::PROFILE_URL, null, $this->getAuthHeader());
        //$user = json_decode(file_get_contents(self::PROFILE_URL . '?access_token=' . $access_token));
        if ($user == null) {
            throw new Exception('Invalid user data returned from GG');
        }

        // The profile is nested, extract
        $user = Arr::path($user, 'result.users', array());

        // first element off array
        $user = array_shift($user);

        //return array_merge(array('identity'=>$user->link,'display_name'=>$user->name,'email'=>null),(array)$user);

        return array(
            'identity'     => $user['id'],
            'email'        => null,
            'name'         => $user['name'],
            'display_name' => $user['label'],
        );
    }


    /**
     * /* GG API CODE
     */

    /**
     * @desc Pobierz nowy token na podstawie starego
     *
     * @return string
     */
    public function refreshToken()
    {

        return $this->_doRequest('POST', self::TOKEN_URL, array(
            'refresh_token' => $this->refresh_token,
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri'  => $this->getURI(),
        ));
    }

    /**
     * @desc Informacja zwracana przez biblioteke curl
     * kody protokołu http http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     */
    public function getLastError()
    {

        return $this->lastError;
    }

    /**
     * @desc Pobierz nagłówek autoryzacyjny
     *
     * @return string
     */
    public function getAuthHeader()
    {
        return 'Authorization: OAuth ' . $this->access_token;
    }

    /**
     * @desc Pobranie adresu url zapytania do api
     *
     * @param string $method nazwa metody http: 'GET','POST','PUT','DELETE'
     * @param string $uri nazwa zasobu jako uri
     * @param mixed $params dodatkowe parametry zapytania
     * @param bool $ssl czy zapytanie jest po https
     *
     * @return string
     */
    protected function getRequestURL($method, $uri, $params = null, $ssl = false, $responseType = '')
    {
        return $uri . ($responseType ? '.' . $responseType : '') . (is_array($params) && count($params) > 0 && $method == 'GET' ? '?' . http_build_query($params) : '');
    }

    /**
     * @desc Zapytanie http do api realizowane przez użytkownika podpisane przez OAuth
     *
     * @param string $method nazwa metody http: 'GET','POST','PUT','DELETE'
     * @param string $uri nazwa zasobu jako uri
     * @param mixed $params dodatkowe parametry zapytania
     * @param bool $ssl czy zapytanie jest po https
     * @param string $responseType w jakim formacie ma być odpowiedź z serwera
     *
     * @return mixed     tablica elementów zwróconych przez API
     */
    private function _doRequest($method, $uri, $params = null, $headers = null, $ssl = false, $responseType = null)
    {

        try {
            $resp = $this->_ggApiRequest($method, $uri, $params, $headers, $ssl, $responseType);
        } catch (Kohana_Exception $e) {
            if ($e->getMessage() != 'expired_token' || !$this->refresh_token) {
                throw $e;
            }
            $token_data = $this->refreshToken();
            //$_SESSION['token_data'] = $token_data;
            Session::instance()->set('token_data', $token_data);
            $this->setToken($token_data['access_token'], $token_data['refresh_token']);
            $resp = $this->_ggApiRequest($method, $uri, $params, array($this->getAuthHeader()), $ssl, $responseType);
        }
        if ($resp !== false) {
            return $resp;
        }

        throw new Kohana_Exception($this->getRequestURL($method, $uri, $params, $ssl,
                                                        $responseType) . ' ' . $this->getLastError());
    }

    /**
     * @desc Zapytanie http do api
     *
     * @param string $method nazwa metody http: 'GET','POST','PUT','DELETE'
     * @param string $uri nazwa zasobu jako uri
     * @param mixed $params dodatkowe parametry zapytania
     * @param bool $ssl czy zapytanie jest po https
     * @param string $responseType w jakim formacie ma być odpowiedź z serwera
     *
     * @return mixed     tablica elementów zwróconych przez API
     */
    private function _ggApiRequest($method, $uri, $params = null, $headers = null, $ssl = false, $responseType = 'json')
    {

        $responseType = $responseType === null ? $this->responseType : $responseType;

        if (!in_array($method, array('GET', 'POST', 'PUT', 'DELETE'))) {
            throw new Kohana_Exception('Invalid request method = ' . $method);
        }

        $ch = curl_init();
        if (($method == 'POST' || $method == 'PUT')) {
            $simpleParams = http_build_query((array)$params, null);
            curl_setopt($ch, CURLOPT_POSTFIELDS, !preg_match('/=%40/', $simpleParams) ? $simpleParams : $params);
        }
        if ($method != 'POST') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        $requestUrl = $this->getRequestURL($method, $uri, $params, $ssl, $responseType);
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge((array)$headers, array(
            'Expect: ',
            'User-Agent: GGAPIPHP v' . self::VERSION . ' ' . php_uname('n'),
            'Accept-Charset: ISO-8859-2,utf-8;q=0.7,*;q=0.7'
        )));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->requestTimeout);
        if (defined('CURLOPT_ENCODING')) {
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        }

        $this->lastHeaders = array();
        $this->response    = curl_exec($ch);
        $this->lastError   = curl_error($ch);
        $this->info        = curl_getinfo($ch);
        curl_close($ch);

        if ($this->response === false) {
            return false;
        }

        $this->lastHeaders = $this->_setLastHeaders(substr($this->response, 0, $this->info['header_size'] - 4));
        $this->response    = substr($this->response, $this->info['header_size']);

        if ($this->parseResponse === false) {
            return true;
        }

        if ($this->info['http_code'] !== 200) {
            try {
                $parsedResponse = $this->parseResponse($this->response, $this->info['content_type']);
            } catch (GGAPIParseException $e) {
                $parsedResponse = array('result' => array('errorMsg' => is_array($this->lastHeaders) ? array_shift($this->lastHeaders) : $this->info['http_code']));
            }
            switch ($this->info['http_code']) {
                case 401:
                    throw new Kohana_Http_Exception_401($this->getErrorMsg($parsedResponse));
                case 403:
                    throw new Kohana_Http_Exception_403($this->getErrorMsg($parsedResponse));
                case 404:
                    throw new Kohana_Http_Exception_404($this->getErrorMsg($parsedResponse));
                case 400:
                    throw new Kohana_Http_Exception_400($this->getErrorMsg($parsedResponse));
                case 500:
                    throw new Kohana_Http_Exception_500($this->getErrorMsg($parsedResponse));
                default:
                    throw new Kohana_Exception($this->getErrorMsg($parsedResponse));
            }
        } else {
            $parsedResponse = $this->parseResponse($this->response, $this->info['content_type']);
        }

        return $parsedResponse;
    }

    /**
     * @desc Pobranie informacji o błędzie
     *
     * @param array $parsedResponse
     * @return string
     */
    private function getErrorMsg($parsedResponse)
    {

        return isset($parsedResponse['error_description']) ? $parsedResponse['error_description'] : @$parsedResponse['result']['errorMsg'];
    }

    /**
     * @desc Ustawienie nagłówków
     *
     * @param string $header
     * @return array
     */
    private function _setLastHeaders($header)
    {

        return $this->lastHeaders = explode("\r\n", $header);
    }

    /**
     * @desc Translacja odpowiedzi do tablicy php w zależności od jej formatu
     *
     * @param string $response odpowiedź serwera
     *
     * @return mixed
     */
    public function parseResponse($response, $type)
    {

        switch ($type) {
            case 'text/xml':
                $parsedResponse = array('result' => $this->parseXML($response));
                break;
            case 'application/phps':
                $parsedResponse = @unserialize($response);
                if ($parsedResponse === false) {
                    throw new Kohana_Exception('Parse exception');
                }
                break;
            case 'application/json':
            default:
                $parsedResponse = $this->parseJSON($response);
                if ($parsedResponse === false) {
                    throw new Kohana_Exception('Parse exception');
                }
                break;
        }

        return $parsedResponse;
    }

    /**
     * @desc Translacja xml do php
     *
     * @param string $input
     */
    protected function parseXML($input)
    {
        $sxml = @simplexml_load_string($input);
        if ($sxml === false) {
            throw new Kohana_Exception('Not valid XML response');
        };
        $arr = array();
        if ($sxml) {
            foreach ($sxml as $k => $v) {
                if ($sxml['list']) {
                    $arr[] = self::convert_simplexml_to_array($v);
                } else {
                    $arr[$k] = self::convert_simplexml_to_array($v);
                }
            }
        }
        if (sizeof($arr) > 0) {
            return $arr;
        } else {
            return (string)$sxml;
        }
    }

    /**
     * @desc Translacja JSON do PHP
     *
     * @param string $input
     * @return midex
     */
    protected function parseJSON($input)
    {

        return json_decode($input, true);
    }

}

?>