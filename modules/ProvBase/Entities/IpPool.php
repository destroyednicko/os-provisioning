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

namespace Modules\ProvBase\Entities;

use DB;

class IpPool extends \BaseModel
{
    // The associated SQL table for this Model
    public $table = 'ippool';

    // Add your validation rules here
    public function rules()
    {
        // Check out ExtendedValidator.php for own validations! (ip_larger, netmask)
        // Note: ip rule is added in IpPoolController
        // TODO: Take care of IpPoolController::prepare_rules() when adding new rules!
        return [
            'net' => 'required',
            'netmask' => 'required|netmask',     // netmask must not be in first place!
            'ip_pool_start' => 'required|ip_in_range:net,netmask|ip_larger:net',
            'ip_pool_end' => 'required|ip_in_range:net,netmask|ip_larger:ip_pool_start',
            'router_ip' => 'required|ip_in_range:net,netmask',
            'broadcast_ip' => 'nullable|ip_in_range:net,netmask|ip_larger:ip_pool_end',
            'dns1_ip' => 'nullable',
            'dns2_ip' => 'nullable',
            'dns3_ip' => 'nullable',
            'prefix_len' => 'netmask',
            'delegated_len' => 'netmask',
        ];
    }

    // Name of View
    public static function view_headline()
    {
        return 'IP-Pools';
    }

    // View Icon
    public static function view_icon()
    {
        return '<i class="fa fa-tags"></i>';
    }

    // AJAX Index list function
    // generates datatable content and classes for model
    public function view_index_label()
    {
        $bsclass = $this->get_bsclass();

        return ['table' => $this->table,
            'index_header' => [$this->table.'.id', 'netgw.hostname', $this->table.'.type', 'version', $this->table.'.net', $this->table.'.netmask', $this->table.'.router_ip', $this->table.'.description'],
            'header' =>  $this->type.': '.$this->net.' '.$this->netmask,
            'bsclass' => $bsclass,
            'eager_loading' => ['netgw'], ];
    }

    public function get_bsclass()
    {
        $bsclass = 'info';

        if ($this->type == 'CPEPub') {
            $bsclass = 'active';
        }
        if ($this->type == 'CPEPriv') {
            $bsclass = '';
        }
        if ($this->type == 'MTA') {
            $bsclass = 'success';
        }

        return $bsclass;
    }

    /**
     * Returns all netgw hostnames for ip pools as an array
     */
    public function netgw_hostnames()
    {
        return DB::table('netgw')->select('id', 'hostname')->get();
    }

    /**
     * Convert IpPool netmask to CIDR notation
     * e.g. 255.255.255.240 will return /28
     *
     * @return string
     */
    public function maskToCidr()
    {
        if (self::isCidrNotation($this->netmask)) {
            return $this->netmask;
        }

        $long = ip2long($this->netmask);
        $base = ip2long('255.255.255.255');

        return '/'.(string) (32 - log(($long ^ $base) + 1, 2));
    }

    /**
     * Check if netmask is written in Cidr notation (e.g. /16)
     *
     * @param string
     * @return bool
     */
    public static function isCidrNotation($netmask)
    {
        return preg_match('/^\/\d{1,3}$/', $netmask);
    }

    /**
     * Check if route to this pool exists in provisioning server routing table
     *
     * @return bool
     */
    public function ip_route_prov_exists()
    {
        // route is online without setting a static route,
        // e.g. an external router is used (default gateway)
        if ($this->ip_route_online()) {
            return true;
        }

        $optionIpv6 = '';
        $ip = $this->netgw->ip;

        if ($this->version == '6') {
            $optionIpv6 = '-6';
            $ip = $this->netgw->ipv6;
        }

        return strlen(exec("/usr/sbin/ip $optionIpv6 route show ".$this->net.$this->maskToCidr().' via '.$ip)) != 0;
    }

    /*
     * Return true if $this->router_ip is online, otherwise false
     * This implies that the NETGW pool should be set correctly in the NETGW
     */
    public function ip_route_online()
    {
        // Ping: Only check if device is online
        $cmd = 'ping';
        if ($this->version == '6') {
            $cmd .= ' -6';
        }

        exec("sudo $cmd -c1 -i0 -w1 ".$this->router_ip, $ping, $ret);

        return $ret ? false : true;
    }

    /**
     * Return the cisco wildcard mask, which is the inverted subnet mask
     *
     * @return string
     *
     * @author Ole Ernst
     */
    public function wildcard_mask()
    {
        foreach (explode('.', $this->netmask) as $val) {
            $mask[] = ~intval($val) & 255;
        }

        return implode('.', $mask);
    }

    /**
     * Return 'secondary' if this pool is not the first CM pool of the NETGW,
     * otherwise an empty string
     *
     * @return string
     *
     * @author Ole Ernst
     */
    public function is_secondary($netgw)
    {
        if ($this->version == '6') {
            return $this->id == optional($netgw->ippools->firstWhere('version', '6'))->id ? '' : 'secondary';
        }

        return $this->id == optional($netgw->ippools->firstWhere('type', 'CM'))->id ? '' : 'secondary';
    }

    /**
     * Return the range string according to the IpPool. We need to cut out public
     * CPE IP addresses, which have been statically assigned - so that they won't
     * be given out to multiple CPEs
     *
     * @return string
     *
     * @author Ole Ernst
     */
    public function getRanges()
    {
        $empty = "\t\t\trange $this->ip_pool_start $this->ip_pool_end;\n";

        if ($this->type != 'CPEPub') {
            return $empty;
        }

        // TODO: filter endpoints by DB query with INET_ATON
        $endpoints = Endpoint::where('fixed_ip', '=', '1')->get();

        if ($endpoints->count() == 0) {
            return $empty;
        }

        foreach ($endpoints as $ep) {
            $eps[] = ip2long($ep->ip);
        }

        $start = ip2long($this->ip_pool_start);
        $end = ip2long($this->ip_pool_end);
        $eps = array_filter($eps, function ($ep) use ($start, $end) {
            // keep endpoints within pool range
            return $ep >= $start && $ep <= $end;
        });

        if (! $eps) {
            return $empty;
        }

        // array_unique should not be necessary
        $eps = array_unique($eps);

        // reverse for array_pop, rather than array_shift
        rsort($eps);

        $ranges = [];
        $i = $start;
        while ($eps) {
            // get next endpoint
            $ep = array_pop($eps);

            if ($i == $ep) {
                $i++;
                continue;
            }

            $ranges[] = [$i, $ep - 1];
            $i = $ep + 1;
        }

        if ($i <= $end) {
            $ranges[] = [$i, $end];
        }

        $ranges = array_map(function ($range) {
            return "\t\t\trange ".implode(' ', array_map('long2ip', array_unique($range))).';';
        }, $ranges);

        return implode("\n", $ranges)."\n";
    }

    /**
     * Relationships:
     */
    public function netgw()
    {
        return $this->belongsTo(NetGw::class);
    }

    // belongs to a netgw - see BaseModel for explanation
    public function view_belongs_to()
    {
        return $this->netgw;
    }

    /**
     * BOOT:
     * - init ippool observer
     */
    public static function boot()
    {
        parent::boot();

        self::observe(new \Modules\ProvBase\Observers\IpPoolObserver);
        self::observe(new \App\Observers\SystemdObserver);
    }
}
