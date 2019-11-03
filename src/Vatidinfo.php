<?php

namespace Liagkos\Taxis;


/**
 * Class Vatidinfo
 *
 * Query Greek VAT IDs from the official tax registry and get
 * data back if VAT ID belongs to business, either physical or legal entity
 *
 * Compatible with revised AADE service as of 2018-07-01
 *
 * @link        https://github.com/liagkos/vatidinfo
 *
 * @author      Athanasios Liagkos me@nasos.work
 * @copyright   2018-2019
 * @license     MIT
 * @version     1.0.3
 *
 * @category    Class
 */
class Vatidinfo
{
    /**
     * Client object
     *
     * @var \SoapClient
     */
    private $objClient;

    /**
     * GSIS WSDL URL
     *
     * @var string
     */
    private $WSDL      = 'https://www1.gsis.gr/wsaade/RgWsPublic2/RgWsPublic2?WSDL';

    /**
     * GSIS Endpoint URL
     *
     * @var string
     */
    private $location  = 'https://www1.gsis.gr/wsaade/RgWsPublic2/RgWsPublic2';

    /**
     * Namespace
     *
     * @var string
     */
    private $strWSSENS = 'http://schemas.xmlsoap.org/ws/2002/07/secext';

    /**
     * VatIDinfo constructor
     *
     * Get credentials from https://www1.gsis.gr/sgsisapps/tokenservices/protected/displayConsole.htm
     * Code copied and modified from http://www.php.net/manual/en/soapclient.soapclient.php#97273
     *
     * @param string $username GSIS Username Token
     * @param string $password GSIS Password Token
     *
     * @throws \RuntimeException in case of bad WSDL endpoint
     *
     * @from 1.0.0
     *
     **/
    public function __construct($username, $password)
    {
        $objSoapVarUser = new \SoapVar($username, XSD_STRING, NULL, $this->strWSSENS, NULL, $this->strWSSENS);
        $objSoapVarPass = new \SoapVar($password, XSD_STRING, NULL, $this->strWSSENS, NULL, $this->strWSSENS);

        $objWSSEAuth = (object) array('Username' => $objSoapVarUser,  'Password' => $objSoapVarPass);
        $objSoapVarWSSEAuth = new \SoapVar($objWSSEAuth, SOAP_ENC_OBJECT, NULL, $this->strWSSENS, 'UsernameToken', $this->strWSSENS);

        $objWSSEToken = (object) array('UsernameToken' => $objSoapVarWSSEAuth);
        $objSoapVarWSSEToken = new \SoapVar($objWSSEToken, SOAP_ENC_OBJECT, NULL, $this->strWSSENS, 'UsernameToken', $this->strWSSENS);

        $objSoapVarHeaderVal = new \SoapVar($objSoapVarWSSEToken, SOAP_ENC_OBJECT, NULL, $this->strWSSENS, 'Security', $this->strWSSENS);
        $objSoapVarWSSEHeader = new \SoapHeader($this->strWSSENS, 'Security', $objSoapVarHeaderVal);

        try {
            // We use the error control operator (@) because in case of bad WSDL PHP dies with Fatal Error
            $this->objClient = @new \SoapClient($this->WSDL, array('trace' => false, 'soap_version' => SOAP_1_2));
            $this->objClient->__setLocation($this->location);
            $this->objClient->__setSoapHeaders(array($objSoapVarWSSEHeader));
        } catch (\SoapFault $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode());
        }

    }

    /**
     * Execution of query
     *
     * @param array  $params    [afmFrom=>VAT ID of caller, afmFor=>VAT ID to look for,
     *                          lookDate=>Get info for a specific date YYYY-MM-DD (new in v2), method=>Method date/nodate/info
     *
     * @return array|string     Array of results or false for SOAP error
     *
     * @from 1.0.0
     **/
    public function exec($params)
    {
        // Default options
        $params['method']    = $params['method'] ?? 'query';
        $params['type']      = $params['type'] ?? 'json';
        $params['separator'] = $params['separator'] ?? '.';

        // if afmFrom is omitted, current user is supposed to be the caller
        // if lookDate is omitted, today is the reference date
        if ($params['method'] === 'query') {
            $soapMethod = 'rgWsPublic2AfmMethod';
            $paramsSOAP = [
                'afm_called_by'  => $params['afmFrom'] ?? '',
                'afm_called_for' => $params['afmFor']
            ];
            if (isset($params['lookDate'])) {
                $paramsSOAP['as_on_date'] = $params['lookDate'];
            }
        } else {
            $soapMethod ='rgWsPublic2VersionInfo';
        }

        // Success = SOAP success, regardless of the reply of GSIS
        $result['success'] = true;
        $vars              = $params['method'] === 'query' ? ['INPUT_REC' => $paramsSOAP] : null;

        try {
            $response = $this->objClient->$soapMethod($vars);
        } catch (\SoapFault $e) {
            $result = [
                'success' => false,
                'errorType' => 'SOAP',
                'errorMsg' => $e->getMessage(),
                'erroCode' => $e->getCode()
            ];
        }

        if ($result['success']) {
            if ($params['method'] === 'info') {
                $result['data'] = $response->result;
            } else {
                $result['data'] = $this->parseReply($response->result->rg_ws_public2_result_rtType, $params['separator']);
            }
        }

        return $params['type'] === 'json' ? json_encode($result, JSON_UNESCAPED_UNICODE) : $result;
    }

    /**
     * Beautify the reply object
     *
     * @param object $reply
     * @param string $separator
     *
     * @return array
     *
     * @since 1.0.0
     */
    private function parseReply($reply, $separator)
    {
        $parsed = array();
        // found true = data found for VAT ID, false = error (password, vatid, etc)
        $parsed['found']   = $reply->error_rec->error_code === null;
        $parsed['queryid'] = $reply->call_seq_id;
        $parsed['errors']  = $parsed['found'] ? false :
            [
                'code' => $reply->error_rec->error_code,
                'msg'  => $reply->error_rec->error_descr
            ];
        $parsed['caller'] = [
            // user is the physical person using his tokens
            'user' => [
                'username' => $reply->afm_called_by_rec->token_username,
                'fullname' => $reply->afm_called_by_rec->token_afm_fullname,
                'vatid'    => trim($reply->afm_called_by_rec->token_afm)
            ],
            // owner is the entity on which behalf user is asking information
            // in case of legal entity, this is the legal entity, otherwise
            // usually is the same as the user
            'owner' => [
                'fullname' => $reply->afm_called_by_rec->afm_called_by_fullname,
                'vatid'    => trim($reply->afm_called_by_rec->afm_called_by)
            ]
        ];
        $parsed['data'] = !$parsed['found'] ? [] : [
            'dateShown'   => new \DateTime($reply->afm_called_by_rec->as_on_date),
            'name'        => $reply->basic_rec->onomasia,
            'title'       => $reply->basic_rec->commer_title,
            'vatid'       => trim($reply->basic_rec->afm),
            'doyID'       => $reply->basic_rec->doy,
            'doyName'     => $reply->basic_rec->doy_descr,
            'address'     => [
                'street'      => $reply->basic_rec->postal_address,
                'number'      => trim($reply->basic_rec->postal_address_no),
                'city'        => $reply->basic_rec->postal_area_description,
                'zip'         => $reply->basic_rec->postal_zip_code
            ],
            'isWhat'      => trim($reply->basic_rec->i_ni_flag_descr),
            'isCompany'   => trim($reply->basic_rec->i_ni_flag_descr) !== 'ΦΠ',
            'companyType' => trim($reply->basic_rec->legal_status_descr),
            'isActive'    => $reply->basic_rec->deactivation_flag === '1',
            'isActiveTxt' => trim($reply->basic_rec->deactivation_flag_descr),
            'type'        => trim($reply->basic_rec->firm_flag_descr),
            'regDate'     => $reply->basic_rec->regist_date !== null ? new \DateTime($reply->basic_rec->regist_date) : false,
            'stopDate'    => $reply->basic_rec->stop_date !== null ? new \DateTime($reply->basic_rec->stop_date) : false,
            'normalVat'   => $reply->basic_rec->normal_vat_system_flag === 'Y',
            'activities'  => isset($reply->firm_act_tab) ? $this->parseActivities($reply->firm_act_tab, $separator) : false
        ];

        // All address fields are null in case of stopped business activity
        if (
            $parsed['data']['address']['street'] === null &&
            $parsed['data']['address']['number'] === ''   &&
            $parsed['data']['address']['city']   === null &&
            $parsed['data']['address']['zip']    === null
        ) {
            $parsed['data']['address'] = false;
        }

        return $parsed;
    }

    /**
     * Custom sort and place activities in array
     *
     * @param object $activities
     * @param string $separator
     *
     * @return array
     *
     * @since 1.0.0
     */
    private function parseActivities($activities, $separator)
    {
        if (!is_array($activities->item)) {
            // Single activity
            $parsed[$activities->item->firm_act_kind] = [
                'descr' => $activities->item->firm_act_kind_descr,
                'items' => [
                    'code' => $activities->item->firm_act_code,
                    'descr' => $activities->item->firm_act_descr,
                    'formatted' => $this->formatActivity($activities->item->firm_act_code, $separator)
                ]
            ];
        } else {
            // Multiple activities
            // We want all activities sorted by type and then by code
            $activityPerCode = array();
            $activityPerType = array();
            foreach ($activities->item as $item) {
                $activityPerCode[$item->firm_act_code] = [
                    'type'      => $item->firm_act_kind,
                    'descr'     => $item->firm_act_descr,
                    'formatted' => $this->formatActivity($item->firm_act_code, $separator)
                ];
                $activityPerType[$item->firm_act_kind] = [
                    'typeDescr' => $item->firm_act_kind_descr,
                ];
            }

            ksort($activityPerCode);
            ksort($activityPerType);

            foreach ($activityPerType as $type => $values) {
                $parsed[$type] = [
                    'descr' => $values['typeDescr'],
                    'items' => array()
                ];
            }

            foreach ($parsed as $type => $values) {
                $items = array();
                foreach ($activityPerCode as $code => $codeValues) {
                    if ($codeValues['type'] == $type) {
                        $items[] = [
                            'code'      => $code,
                            'descr'     => $codeValues['descr'],
                            'formatted' => $codeValues['formatted']
                        ];
                    }
                }
                $parsed[$type]['items'] = $items;
            }
        }
        return $parsed;
    }

    /**
     * Format activity code in groups of 2 digits
     * separated by $separator
     *
     * @param $activity  int     Activity xxxxxxxx
     * @param $separator string  Separating character(s)
     *
     * @return string            Formatted activity xx.xx.xx.xx
     *
     * @since 1.0.0
     */
    private function formatActivity($activity, $separator)
    {
        // Fix for activities starting with 0, not displayed in (int) format
        $activity = str_pad($activity, 8, '0', STR_PAD_LEFT);
        return substr($activity, 0, 2) . $separator .
            substr($activity, 2,2) . $separator .
            substr($activity, 4,2) . $separator .
            substr($activity, 6, 2);
    }

}