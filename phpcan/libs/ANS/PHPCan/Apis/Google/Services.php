<?php

# Arvin Castro, arvin@sudocode.net
# 27 June 2011
# http://sudocode.net/sources/includes/class-clientlogin-php/

/*
* phpCan - http://idc.anavallasuiza.com/
*
* phpCan is released under the GNU Affero GPL version 3
*
* More information at license.txt
*/

namespace ANS\PHPCan\Apis\google;

defined('ANS') or die();

use \ANS\PHPCan\Apis;

class Services extends Xhttp
{
    protected $logged = false;

    private $services = array('alerts', 'analytics', 'blogger', 'cl', 'cp', 'cprose', 'writely', 'peoplewise', 'trendspro', 'groups2', 'friendview', 'mail', 'local', 'sj', 'lh2', 'reader', 'trends', 'urlshortener', 'grandcentral', 'sitemaps', 'youtube');

    public function login ($email, $password, $service, $accountType = 'HOSTED_OR_GOOGLE')
    {
        if (!in_array($service, $this->services)) {
            $this->Errors->set('api', __('The selected service is not available'), 'google-services');

            return false;
        }

        $options = array(
            'http' => array(
                'ignore_errors' => true,
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query(array(
                    'accountType' => $accountType,
                    'Email' => $email,
                    'Passwd' => $password,
                    'service' => $service,
                    'source' => 'sudocode-clientlogin-040711'
                ))
            )
        );

        $context = stream_context_create($options);

        $this->response = file_get_contents('https://www.google.com/accounts/ClientLogin', false, $context);

        if (false !== strpos($http_response_header[0], '200')) {
            foreach (explode("\n", $this->response) as $line) {
                list($key, $value) = explode('=', $line, 2);

                if ($key) {
                    $this->{strtolower($key)} = $value;
                }
            }

            $this->logged = true;

            return true;
        } else {
            $this->logged = false;

            $this->Errors->set('api', (($this->response) ? $this->response: $http_response_header[0]), 'google-services');

            return false;
        }
    }

    public function toAuthorizationHeader ()
    {
        return "GoogleLogin auth={$this->auth}";
    }
}
