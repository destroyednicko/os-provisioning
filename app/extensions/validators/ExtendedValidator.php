<?php
/**
 * Copyright (c) NMS PRIME GmbH ("NMS PRIME Community Version")
 * and others – powered by CableLabs. All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\extensions\validators;

use Log;
use File;
use PHP_IBAN\IBAN;

/*
 * Our own ExtendedValidator Class
 *
 * IMPORTANT: add Validator::extend('xyz', 'ExtendedValidator@validateXyz'); to
 * ExtendedValidatorServiceProvider under app/Providers
 */
class ExtendedValidator
{
    public function notNull($attribute, $value, $parameters)
    {
        if ($value == '' || $value == '0' || $value == null || $value == '0000-00-00') {
            return false;
        }

        return true;
    }

    /*
     * Checks if value of a data field is zero when another field has specific values (declared in parameters)
     * NOTE: the fields name has to be replaced by prepare_rules-function of the Controller with the value of the data of this field
     */
    public function nullIf($attribute, $value, $parameters)
    {
        $data = $parameters[0];
        // dd($attribute, $value, $parameters);
        unset($parameters[0]);

        if (in_array($data, $parameters)) {
            if ($value == 0 || $value == null) {
                return true;
            }

            return false;
        }

        return true;
    }

    /*
     * MAC validation
     *
     */
    public function validateMac($attribute, $value, $parameters)
    {
        return boolval(filter_var($value, FILTER_VALIDATE_MAC));
    }

    /**
     * Check if ip ($value) is inside the ip range of a net
     *
     * @param 1 net_ip
     * @param 2 netmask
     * @return true if inside
     *
     * @author Nino Ryschawy
     */
    public function validateIpInRange($attribute, $value, $parameters)
    {
        // calculate netmask for cidr notation (e.g.: 10.0.0.0/21)
        $netmask = ip2long($parameters[1]);
        $base = ip2long('255.255.255.255');
        // $prefix = 32-log(($netmask ^ $base)+1,2);

        $ip = ip2long($value);
        $start = ip2long($parameters[0]);
        // $end = $start + (1 << (32 - $prefix)) -1;
        $end = $start + (1 << log(($netmask ^ $base) + 1, 2)) - 1;

        return $ip >= $start && $ip <= $end;
    }

    /**
     * Check if ip ($value) is larger than the one specified in $parameters
     *
     * @author Nino Ryschawy
     *
     * @return bool
     */
    public function ipLarger($attribute, $value, $parameters)
    {
        // IPv6
        if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->inetPtoi($value) > $this->inetPtoi($parameters[0]);
        }

        // IPv4
        return ip2long($value) > ip2long($parameters[0]);
    }

    /**
     * Converts an IPv6 string to a decimal value as string to be compareable
     * See https://www.samclarke.com/php-ipv6-to-128bit-int/
     *
     * @param string
     * @return mixed
     */
    private function inetPtoi($ip)
    {
        if (! function_exists('bcadd')) {
            throw new \Exception('PHP bcmath package must be installed!');
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return;
        }

        $parts = unpack('J*', inet_pton($ip));

        // convert any unsigned ints to signed from unpack
        foreach ($parts as &$part) {
            if ($part < 0) {
                $part = bcadd($part, '18446744073709551616');
            }
        }

        return bcadd($parts[2], bcmul($parts[1], '18446744073709551616'));
    }

    /**
     * Check if value is a valid IP netmask
     *
     * @author Nino Ryschawy
     *
     * @return bool
     */
    public function netmask($attribute, $value, $parameters)
    {
        $netmask = str_replace(' ', '', $value);

        // Allow cidr notation
        if (\Modules\ProvBase\Entities\IpPool::isCidrNotation($netmask)) {
            return true;
        }

        $netmask = ip2long($netmask);
        $base = ip2long('255.255.255.255');
        $prefix = log(($netmask ^ $base) + 1, 2);

        $number = (int) (10000 * $prefix);

        return is_int($number /= 10000);
    }

    /**
     * Validates Sepa Creditor Id
     * see: https://github.com/AbcAeffchen/SepaUtilities/blob/master/src/SepaUtilities.php
     */
    public function validateCreditorId($attribute, $value, $parameters)
    {
        $country_code = substr($value, 0, 2);

        // TODO: add more countries, improve by check against check digits
        $creditor_id_length = [
            'AT' => 18,         // Austria
            'BE' => 20,         // Belgium
            'DE' => 18,         // Germany
            'EE' => 20,         // Estonia
            'ES' => 16,
            'FR' => 13,
            'IT' => 23,
            'LU' => 26,         // Luxembourg
            'NL' => 19,
            'PT' => 13,         // Portugal
        ];

        if (strlen($value) != (isset($creditor_id_length[$country_code]) ? $creditor_id_length[$country_code] : 1000)) {
            return false;
        }

        return preg_match('#^[a-zA-Z]{2,2}[0-9]{2,2}([A-Za-z0-9]|[\+|\?|/|\-|:|\(|\)|\.|,|\']){3,3}([A-Za-z0-9]|[\+|\?|/|\-|:|\(|\)|\.|,|\']){1,28}$#', $value);
    }

    /*
     * Geoposition validation
     * see: http://stackoverflow.com/questions/7113745/what-regex-expression-will-check-gps-values
     */
    public function validateGeopos($attribute, $value, $parameters)
    {
        return preg_match('/(-?[\d]{1,3}\.[\d]{0,12},?){2}/', $value);
    }

    /*
     * DOCSIS configfile validation
     */
    public function validateDocsis($attribute, $value, $parameters)
    {
        if (! $value) {
            return true;
        }

        $arguments = func_get_args();

        /* Configfile */
        $device = $parameters[0];
        if ($device == null) {
            return false;
        }
        if ($device == 'tr069') {
            // configfile is csv, nothing to validate
            return true;
        }

        $dir = "/tftpboot/$device";
        $cf_file = $dir.'dummy-validator.conf';

        /*
         * Replace all {xyz} content
         */
        $rows = explode("\n", $value);
        $s = '';
        foreach ($rows as $row) {
            if (preg_match('/(string)/i', $row)) {
                $s .= "\n".preg_replace('/\\{[^\\{]*\\}/im', 'text', $row);
            } elseif (preg_match('/(ipaddress)/i', $row)) {
                $s .= "\n".preg_replace('/\\{[^\\{]*\\}/im', '1.1.1.1', $row);
            } else {
                $s .= "\n".preg_replace('/\\{.+\\}/im', '1', $row);
            }
        }

        /*
         * Write Dummy File and try to encode
         */
        $text = "Main\n{\n\t".$s."\n}";
        $ret = File::put($cf_file, $text);

        if ($ret === false) {
            exit('Error writing to file');
        }

        if ($device == 'cm') {
            Log::info("Validation: docsis -e $cf_file $dir/../keyfile $dir/dummy-validator.cfg");
            exec("rm -f $dir/dummy-validator.cfg && docsis -e $cf_file $dir/../keyfile $dir/dummy-validator.cfg 2>&1", $outs);
        } elseif ($device == 'mta') {
            Log::info("Validation: docsis -p $cf_file $dir/dummy-validator.cfg");
            exec("rm -f $dir/dummy-validator.cfg && docsis -p $cf_file $dir/dummy-validator.cfg 2>&1", $outs, $ret);    //return value is always 0
        }

        /*
         * Parse Errors - only one error is shown - subtract 3 from line nr
         */
        $report = $outs[0];
        preg_match('/[0-9]+$/', $report, $i);
        if (isset($i[0])) {
            $report = preg_replace('/[0-9]+$/', $i[0] - 3, $report);
        }
        // foreach ($outs as $out)
        // {
        // 	$report .= "\n$out";
        // }

        if (! file_exists("$dir/dummy-validator.cfg") && (isset($arguments[3]))) {
            // see: https://laracasts.com/discuss/channels/general-discussion/extending-validation-with-custom-message-attribute?page=1
            // when laravel calls the validation function (validate) they luckily pass "$this" that is the Validator instance as 4th argument - so we can get it here
            $validator = $arguments[3];
            $validator->setCustomMessages(['docsis' => $report]);

            return false;
        }

        return true;
    }

    // $value (field value) must only contain strings of product type enums - used on Salesman
    public function validateProductType($attribute, $value, $parameters)
    {
        $types = \Modules\BillingBase\Entities\Product::getPossibleEnumValues('type');

        $tmp = str_replace([',', '|', '/', ';'], ' ', $value);
        $prods = explode(' ', $tmp);

        foreach ($prods as $type) {
            // skip empty strings
            if (! $type) {
                continue;
            }
            if (! in_array($type, $types)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if given string (freetext) for phonebookentry is valid.
     *
     * @author Patrick Reichel
     */
    public function validatePhonebookString($attribute, $value, $parameters)
    {
        // see: https://laracasts.com/discuss/channels/general-discussion/extending-validation-with-custom-message-attribute?page=1
        // when laravel calls the validation function (validate) they luckily pass "$this" that is the Validator instance as 4th argument - so we can get it here
        $validator = \func_get_arg(3);

        // as of commit f2d4af6840abed1e2192855346d8f1af65758711 now empty strings are “null”
        // so we classify all kinds of empty strings as valid
        // this is save – if empty strings are not allowed another rule will strike; e.g. “required”
        if (! $value) {
            return true;
        }

        // for easier access and improved readability: get needed informations out of config
        $maxlen = \Modules\ProvVoip\Entities\PhonebookEntry::$config[$attribute]['maxlen'];
        $valid = str_split(\Modules\ProvVoip\Entities\PhonebookEntry::$config[$attribute]['valid']);

        // check if given value is to long
        if (strlen($value) > $maxlen) {
            $validator->setCustomMessages(['phonebook_string' => $attribute.' to long (max. '.$maxlen.' characters allowed)']);

            return false;
        }

        // check all value's characters against the list of valid chars
        $invalids = [];
        foreach (str_split($value) as $char) {
            if (! in_array($char, $valid)) {
                array_push($invalids, $char);
            }
        }
        // input invalid if at least one invalid character has been found
        if (boolval($invalids)) {
            $validator->setCustomMessages(['phonebook_string' => 'The following characters are not allowed in '.$attribute.': '.implode('', array_unique($invalids))]);

            return false;
        }

        // all fine => valid
        return true;
    }

    /**
     * Check if given string (predefined) for phonebookentry is valid.
     * The valid values are defined by Telekom and delivered in several files.
     *
     * @author Patrick Reichel
     */
    public function validatePhonebookPredefinedString($attribute, $value, $parameters)
    {

        // see: https://laracasts.com/discuss/channels/general-discussion/extending-validation-with-custom-message-attribute?page=1
        // when laravel calls the validation function (validate) they luckily pass "$this" that is the Validator instance as 4th argument - so we can get it here
        $validator = \func_get_arg(3);

        // attention: data coming into validators are still htmlencoded from view level!
        $search = html_entity_decode($value);

        // use the method that builds the array for the selects => that contains all valid values…
        if (! array_key_exists($search, \Modules\ProvVoip\Entities\PhonebookEntry::get_options_from_file($attribute))) {
            $validator->setCustomMessages(['phonebook_predefined_string' => $value.' is not a valid value for '.$attribute]);

            return false;
        }

        return true;
    }

    /**
     * Check if given one character option is valid
     *
     * @author Patrick Reichel
     */
    public function validatePhonebookOneCharacterOption($attribute, $value, $parameters)
    {

        // see: https://laracasts.com/discuss/channels/general-discussion/extending-validation-with-custom-message-attribute?page=1
        // when laravel calls the validation function (validate) they luckily pass "$this" that is the Validator instance as 4th argument - so we can get it here
        $validator = \func_get_arg(3);

        // get the allowed chars out of config
        $valid = \Modules\ProvVoip\Entities\PhonebookEntry::$config[$attribute]['in_list'];

        if (! in_array($value, $valid)) {
            $validator->setCustomMessages(['phonebook_one_character_option' => $value.' is not valid for '.$attribute.' (have to be in ['.implode('', $valid).']).']);

            return false;
        }

        return true;
    }

    /**
     * Check values that are entry_type dependend
     *
     * @param $parameters first entry needs to be the entry_type value (add “entry_type” to \Modules\ProvVoip\Entities\PhonebookEntry::rules(); will
     *          then be set in PhonebookEntryController::prepare_rules
     *
     * @author Patrick Reichel
     */
    public function validatePhonebookEntryTypeDependend($attribute, $value, $parameters)
    {

        // see: https://laracasts.com/discuss/channels/general-discussion/extending-validation-with-custom-message-attribute?page=1
        // when laravel calls the validation function (validate) they luckily pass "$this" that is the Validator instance as 4th argument - so we can get it here
        $validator = \func_get_arg(3);

        $entry_type = $parameters[0];

        // define which forms are needed to be filled/empty depending on entry_type
        if (in_array($entry_type, ['P'])) {
            $has_to_be_empty = [
                'company',
            ];
            $has_to_be_set = [
                'salutation',
                'lastname',
            ];
        } elseif (in_array($entry_type, ['B', 'F'])) {
            $has_to_be_empty = [
                'salutation',
                'firstname',
                'lastname',
                'academic_degree',
                'noble_rank',
                'nobiliary_particle',
                'other_name_suffix',
            ];
            $has_to_be_set = [
                'company',
            ];
        }

        // check if data is given for field that has to be empty
        if (in_array($attribute, $has_to_be_empty) && $value) {
            $validator->setCustomMessages(['phonebook_entry_type_dependend' => "The $attribute field has to be empty when entry type is $entry_type."]);

            return false;
        }

        // check if needed field is empty
        if (in_array($attribute, $has_to_be_set) && ! $value) {
            $validator->setCustomMessages(['phonebook_entry_type_dependend' => "The $attribute field is required when entry type is $entry_type."]);

            return false;
        }

        // all checks passed
        return true;
    }

    /**
     * Checks if a BIC entry exists in a config/data file
     *
     * @author Nino Ryschawy
     */
    public function validateBicAvailable($attribute, $value, $parameters)
    {
        $iban = new IBAN(strtoupper($parameters[0]));
        $country = strtolower($iban->Country());
        $bank = $iban->Bank();

        $data = Storage::get('config/billingbase/bic_'.$country.'.csv');

        if (strpos($data, $bank) !== false) {
            return true;
        }

        return false;
    }

    public function validateEmpty($attribute, $value, $parameters)
    {
        // d($value, $attribute, $parameters, $this->getValue($parameters[0]));

        return $value ? false : true;
    }

    /**
     * Checks if given string is IPv4 address
     * Only used as helper – use laravel rule “ipv4” instead
     *
     * @author Patrick Reichel
     */
    protected function validateIPv4Address($attribute, $value, $parameters)
    {
        return boolval(filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
    }

    /**
     * Checks if given string is IPv6 address
     * Only used as helper – use laravel rule “ipv6” instead
     *
     * @author Patrick Reichel
     */
    protected function validateIPv6Address($attribute, $value, $parameters)
    {
        return boolval(filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6));
    }

    /**
     * Checks if given string is either IPv4 or IPv6 address
     * Only used as helper – use laravel rule “ip” instead
     *
     * @author Patrick Reichel
     */
    protected function validateIP4Or6Address($attribute, $value, $parameters)
    {
        return
            $this->validateIPv4Address($attribute, $value, $parameters) ||
            $this->validateIPv6Address($attribute, $value, $parameters);
    }

    /**
     * Checks if given string is hostname OR IP address
     *
     * @author Patrick Reichel
     */
    public function validateHostnameOrIp($attribute, $value, $parameters)
    {
        return
            $this->validateHostname($attribute, $value, $parameters) ||
            $this->validateIPv4Address($attribute, $value, $parameters) ||
            $this->validateIPv6Address($attribute, $value, $parameters);
    }

    /**
     * Checks if given string is a hostname
     *
     * @author Patrick Reichel
     */
    protected function validateHostname($attribute, $value, $parameters)
    {
        // check if at least on letter is contained to filter mistyped IP addresses (192.168.10.1111) – such hostnames should not be in use…
        if ((substr_count($value, '.') == 3) && (! preg_match('/[A-Za-z]/', $value))) {
            return false;
        }

        return boolval(filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME));
    }

    /**
     * Checks if given string is hostname OR IP address
     *
     * @author Patrick Reichel
     */
    public function validateCommaSeparatedHostnamesOrIps($attribute, $value, $parameters)
    {
        $parts = explode(',', $value);
        foreach ($parts as $part) {
            if (! $this->validateHostnameOrIp($attribute, trim($part), $parameters)) {
                return false;
            }
        }

        return true;
    }
}
