<?php

namespace ScDss;

if(file_exists(__DIR__ .'/config.php'))
{
    require_once __DIR__ . '/config.php';
}
else
{
    define('WEBSERVICE_DOMAIN', '');
    define('WEBSERVICE_USER', '');
    define('WEBSERVICE_PASSWORD', '');
    define('WEBSERVICE_BASE_URL', '');
}

use \jamesiarmes\PhpNtlm\SoapClient as NtlmSoapClient;
use SimpleXMLElement;
use SoapFault;
use stdClass;

class ScDssClient
{
    const DEFAULT_BASE_URL = 'https://cac-stage.dss.sc.gov/hssCACProviderWS/';
    const TARGET_NAMESPACE = 'http://www.dss.sc.gov/';

    /** @var NtlmSoapClient */
    protected $client;
    /** @var string|null */
    protected $lastError;

    /**
     * ScDssClient constructor.
     *
     * @param array $options
     *
     *   The following options are accepted:
     *
     *   -  base_url: The fully qualified URL containing the asmx web service. If not specified, WEBSERVICE_BASE_URL from config.php will be used.
     *   -  username: The Windows Authentication username to send to the service. If not specified, WEBSERVICE_USER from config.php will be used.
     *   -  password: The Windows Authentication password to send to the service.If not specified, WEBSERVICE_PASSWORD from config.php will be used.
     *   -  domain: The Windows Authentication domain to send to the service.If not specified, WEBSERVICE_DOMAIN from config.php will be used.
     *
     * @throws SoapFault
     */
    public function __construct($options = [])
    {
        if (isset($options['base_url'])) {
            $baseUrl = $options['base_url'];
        } elseif (defined('WEBSERVICE_BASE_URL')) {
            $baseUrl = WEBSERVICE_BASE_URL;
        } else {
            $baseUrl = self::DEFAULT_BASE_URL;
        }

        if (substr($baseUrl, -1) !== '/') {
            $baseUrl = $baseUrl . '/';
        }
        $baseUrl .= 'hssCACInterface.asmx';
        if (isset($options['domain'])) {
            $domain = $options['domain'];
        } else {
            $domain = WEBSERVICE_DOMAIN;
        }
        if (isset($options['username'])) {
            $username = $options['username'];
        } else {
            $username = WEBSERVICE_USER;
        }
        if (isset($options['password'])) {
            $password = $options['password'];
        } else {
            $password = WEBSERVICE_PASSWORD;
        }

        $options = [
            'location' => $baseUrl,
            'uri'      => self::TARGET_NAMESPACE,
            'user'     => $username,
            'password' => $password,
            'trade'    => true
        ];
        if (!empty($this->domain)) {
            $options['user'] .= '@' . $domain;
        }

        $this->client = new NtlmSoapClient(__DIR__ . '/service.wsdl', $options);
    }

    /**
     * @return SimpleXMLElement|bool
     */
    public function getCACInterfaceList()
    {
        try {
            /** @var stdClass $result */
            /** @noinspection PhpUndefinedMethodInspection */
            $result = $this->client->getCACInterfaceList();
            if (!property_exists($result, 'getCACInterfaceListResult')) {
                $this->lastError = "Response missing expected element 'getCACInterfaceListResult'."
                    . "\n" . $this->getLastResponse();
                return false;
            }
            $result = $result->getCACInterfaceListResult;
            if (!property_exists($result, 'any')) {

                $this->lastError = "Response missing expected element 'getCACInterfaceListResult/any'."
                    . "\n" . $this->getLastResponse();
                return false;
            }
            $result = $result->any;
            $originalDisableEntityLoaderValue = libxml_disable_entity_loader(true);
            $originalUseInternalErrorsValue = libxml_use_internal_errors(true);
            $responseContent = simplexml_load_string($result);
            libxml_disable_entity_loader($originalDisableEntityLoaderValue);
            libxml_use_internal_errors($originalUseInternalErrorsValue);
            if ($responseContent === false) {
                $this->lastError = implode("\n", libxml_get_errors());
                libxml_clear_errors();
                return false;
            }
            return $responseContent;
        } catch (SoapFault $ex) {
            $this->lastError = $ex->getMessage() . "\n" . $this->getLastResponse();
            return false;
        }
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function getLastResponse(): string
    {
        return $this->client->__getLastResponseHeaders() . "\n" . $this->client->__getLastResponse();
    }
}