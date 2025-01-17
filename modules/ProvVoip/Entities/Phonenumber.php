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

namespace Modules\ProvVoip\Entities;

use Illuminate\Support\Collection;

class Phonenumber extends \BaseModel
{
    // The associated SQL table for this Model
    public $table = 'phonenumber';

    // Add your validation rules here
    public function rules()
    {
        $rules = [
            'country_code' => ['required', 'numeric'],
            'prefix_number' => ['required', 'numeric'],
            'number' => ['required', 'numeric'],
            'mta_id' => ['required', 'exists:mta,id,deleted_at,NULL', 'min:1'],
            'port' => ['required', 'numeric', 'min:1'],
            // inject id to rules (so it is passed to prepare_rules)
            'id' => $this->id,
            /* 'active' => ['required', 'boolean'], */
            // TODO: check if password is secure and matches needs of external APIs (e.g. envia TEL)
        ];

        if (! \Module::collections()->has('ProvVoipEnvia')) {
            foreach (['username', 'sipdomain'] as $param) {
                $rules[$param][] = 'required';
            }
        }

        return $rules;
    }

    // Name of View
    public static function view_headline()
    {
        return 'Phonenumber';
    }

    // View Icon
    public static function view_icon()
    {
        return '<i class="fa fa-list-ol"></i>';
    }

    // AJAX Index list function
    // generates datatable content and classes for model
    public function view_index_label()
    {
        return [
            'table' => $this->table,
            'index_header' => [$this->table.'.number', 'phonenumbermanagement.activation_date', 'phonenumbermanagement.deactivation_date', 'phonenr_state', 'modem_city', 'sipdomain'],
            'header' => 'Port '.$this->port.': '.$this->prefix_number.'/'.$this->number,
            'bsclass' => $this->get_bsclass(),
            'edit' => ['phonenumbermanagement.activation_date' => 'get_act', 'phonenumbermanagement.deactivation_date' => 'get_deact', 'phonenr_state' => 'get_state', 'number' => 'build_number', 'modem_city' => 'modem_city'],
            'eager_loading' => ['phonenumbermanagement', 'mta.modem'],
            'disable_sortsearch' => ['phonenr_state' => 'false', 'modem_city' => 'false'],
            'filter' => ['phonenumber.number' => $this->number_query()],
        ];
    }

    public function number_query()
    {
        return "CONCAT(phonenumber.prefix_number,'/',phonenumber.number) like ?";
    }

    public function get_bsclass()
    {
        if (! array_key_exists('phonenumbermanagement', $this->relations)) {
            return 'warning';
        }

        $management = $this->phonenumbermanagement;
        $act = $management ? $management->activation_date : null;
        $deact = $management ? $management->deactivation_date : null;

        // deal with legacy problem of zero dates
        if (! boolval($act)) {
            return 'danger';
        }

        if ($act > date('c')) {
            return 'warning';
        }

        if (! boolval($deact)) {
            return 'success';
        }

        return $deact > date('c') ? 'warning' : 'info';
    }

    public function get_state()
    {
        $management = $this->phonenumbermanagement;

        if (is_null($management)) {
            if ($this->active) {
                $state = 'Active.';
            } else {
                $state = 'Deactivated.';
            }
            $state .= ' No PhonenumberManagement existing!';
        } else {
            $act = $management->activation_date;
            $deact = $management->deactivation_date;

            if (! boolval($act)) {
                $state = 'No activation date set!';
            } elseif ($act > date('c')) {
                $state = 'Waiting for activation.';
            } else {
                if (! boolval($deact)) {
                    $state = 'Active.';
                } else {
                    if ($deact > date('c')) {
                        $state = 'Active. Deactivation date set but not reached yet.';
                    } else {
                        $state = 'Deactivated.';
                    }
                }
            }

            if (boolval($management->autogenerated)) {
                $state .= ' – PhonenumberManagement generated automatically!';
            }
        }

        return $state;
    }

    public function get_act()
    {
        $management = $this->phonenumbermanagement;

        if (is_null($management)) {
            $act = 'n/a';
        } else {
            $act = $management->activation_date;

            if ($act == '0000-00-00') {
                $act = null;
            }
        }

        // reuse dates for view
        if (is_null($act)) {
            $act = '-';
        }

        return $act;
    }

    public function get_deact()
    {
        $management = $this->phonenumbermanagement;

        if (is_null($management)) {
            $deact = 'n/a';
        } else {
            $deact = $management->deactivation_date;

            if ($deact == '0000-00-00') {
                $deact = null;
            }
        }

        // reuse dates for view
        if (is_null($deact)) {
            $deact = '-';
        }

        return $deact;
    }

    public function build_number()
    {
        return $this->prefix_number.'/'.$this->number;
    }

    public function asString()
    {
        if ($this->country_code == '0049' && $this->prefix_number[0] == 0) {
            return $this->prefix_number.$this->number;
        }

        $local = $this->prefix_number[0] == 0 ? substr($this->prefix_number, 1) : $this->prefix_number;

        return $this->country_code.$local.$this->number;
    }

    public function modem_city()
    {
        return $this->mta->modem->zip.' '.$this->mta->modem->city;
    }

    /**
     * ALL RELATIONS
     * link with mtas
     */
    public function mta()
    {
        return $this->belongsTo(Mta::class, 'mta_id');
    }

    // belongs to an mta
    public function view_belongs_to()
    {
        return $this->mta;
    }

    /**
     * Eager load relationships to prevent query duplication
     *
     * @return void
     */
    public function loadEditViewRelations()
    {
        $this->load([
            'mta:id,modem_id,hostname,mac',
            'mta.modem:id,contract_id,salutation,company,department,firstname,lastname,street,house_number,zip,city,district,installation_address_change_date,mac',
            'mta.modem.contract:id,number,firstname,lastname,contract_start',
            'mta.modem.contract.modems:id,contract_id,salutation,company,department,firstname,lastname,street,house_number,zip,city,district,installation_address_change_date',
            'mta.modem.contract.modems.mtas:id,modem_id,hostname,mac',
        ]);
    }

    // View Relation.
    public function view_has_many()
    {
        $ret = [];
        if (\Module::collections()->has('ProvVoip')) {
            $relation = $this->phonenumbermanagement;

            // can be created if no one exists, can be deleted if one exists
            if (is_null($relation)) {
                $ret['Edit']['PhonenumberManagement']['relation'] = new Collection();
                $ret['Edit']['PhonenumberManagement']['options']['hide_delete_button'] = 1;
            } else {
                $ret['Edit']['PhonenumberManagement']['relation'] = collect([$relation]);
                $ret['Edit']['PhonenumberManagement']['options']['hide_create_button'] = 1;
            }

            $ret['Edit']['PhonenumberManagement']['class'] = 'PhonenumberManagement';
        }

        if (\Module::collections()->has('ProvVoipEnvia')) {
            // TODO: auth - loading controller from model could be a security issue ?
            $ret['Edit']['EnviaAPI']['view']['view'] = 'provvoipenvia::ProvVoipEnvia.actions';
            $ret['Edit']['EnviaAPI']['view']['vars']['extra_data'] = \Modules\ProvVoip\Http\Controllers\PhonenumberController::_get_envia_management_jobs($this);
        }

        if (\Module::collections()->has('VoipMon')) {
            $ret['Monitoring']['Cdr']['class'] = 'Cdr';
            $ret['Monitoring']['Cdr']['relation'] = $this->cdrs()->orderBy('id', 'DESC')->get();
        }

        return $ret;
    }

    /**
     * Format MTAs for select 2 field and allow for seaching.
     *
     * @param  string|null  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function select2Mtas(?string $search): \Illuminate\Database\Eloquent\Builder
    {
        return MTA::select('mta.id', 'mta.hostname', 'mta.mac', 'c.number', 'c.firstname', 'c.lastname')
            ->selectRaw('CONCAT(mta.hostname, \' (\' ,mta.mac, \') => \', c.number, \' - \', c.firstname, \' \', c.lastname) as text')
            ->join('modem as m', 'm.id', '=', 'mta.modem_id')
            ->join('contract as c', 'c.id', '=', 'm.contract_id')
            ->where('m.deleted_at', '=', null)
            ->where('c.deleted_at', '=', null)
            ->when($search, function ($query, $search) {
                return $query->where('mta.hostname', 'like', "%{$search}%")
                    ->orWhere('mta.mac', 'like', "%{$search}%")
                    ->orWhere('c.number', 'like', "%{$search}%")
                    ->orWhere('c.firstname', 'like', "%{$search}%")
                    ->orWhere('c.lastname', 'like', "%{$search}%");
            });
    }

    /**
     * Return a list of MTAs the current phonenumber can be assigned to.
     * special case activated envia TEL module:
     * - MTA has to belong to the same contract
     * - Installation address of current modem match installation address of new modem
     *
     * @author Patrick Reichel, Christian Schramm
     */
    public function mtasWhenEnviaEnabled()
    {
        if (! $this->exists) {
            return [null => trans('view.select.base', ['model' => trans('view.select.Mta')])];
        }

        $ret = [];
        $currentModem = $this->mta->modem;

        foreach ($this->mta->modem->contract->modems as $modem) {
            if ($this->isPhonenumberReassignmentAllowed($currentModem, $modem)) {
                foreach ($modem->mtas as $mta) {
                    $ret[$mta->id] = $mta->hostname.' ('.$mta->mac.')';
                }
            }
        }

        return $ret;
    }

    /**
     * Checks if a number can be reassigned to a given new modem
     *
     * @author Patrick Reichel, Christian Schramm
     */
    protected function isPhonenumberReassignmentAllowed($currentModem, $newModem): bool
    {
        $intersect = array_intersect_assoc($currentModem->getAttributes(), $newModem->getAttributes());
        $check = ['salutation', 'company', 'department', 'firstname', 'lastname', 'street',
            'house_number', 'zip', 'city', 'district', 'installation_address_change_date',
        ];

        return ! (bool) array_diff_key(array_flip($check), $intersect);
    }

    /**
     * link to management
     */
    public function phonenumbermanagement()
    {
        return $this->hasOne(PhonenumberManagement::class);
    }

    /**
     * Phonenumbers can be related to EnviaOrders – if this module is active.
     *
     * @param	$withTrashed boolean; if true return also soft deleted orders; default is false
     * @param	$whereStatement raw SQL query; default is returning of all orders
     *				Attention: Syntax of given string has to meet SQL syntax!
     * @return EnviaOrders if module ProvVoipEnvia is enabled, else “null”
     *
     * @author Patrick Reichel
     */
    public function enviaorders($withTrashed = false, $whereStatement = '1')
    {
        if (! \Module::collections()->has('ProvVoipEnvia')) {
            return optional();
        }

        $orders = $this->belongsToMany(
            \Modules\ProvVoipEnvia\Entities\EnviaOrder::class,
            'enviaorder_phonenumber',
            'phonenumber_id',
            'enviaorder_id'
            )
            ->whereRaw($whereStatement)
            ->withTimestamps();

        if ($withTrashed) {
            return $orders->withTrashed();
        }

        return $orders;
    }

    /**
     * Helper to detect if an envia TEL contract has been created for this phonenumber
     * You can either make a bool test against this method or get the id of a contract has been created
     *
     * @return misc:
     *			null if module ProvVoipEnvia is disabled
     *			false if there is no envia TEL contract
     *			external_contract_id for the contract the number belongs to
     *
     * @author Patrick Reichel
     */
    public function envia_contract_created()
    {

        // no envia module ⇒ no envia contracts
        if (! \Module::collections()->has('ProvVoipEnvia')) {
            return;
        }

        // the check is simple: if there is an external contract ID we can be sure that a contract has been created
        if (! is_null($this->contract_external_id)) {
            return $this->contract_external_id;
        } else {
            // take the most recent contract from modem
            // TODO: on handling of multiple contracts per modem: return all IDs
            $envia_contract = $this->mta->modem->enviacontracts->last();
            if ($envia_contract) {
                return $envia_contract->envia_contract_reference;
            } else {
                return false;
            }
        }
    }

    /**
     * Helper to detect if an envia TEL contract has been terminated for this phonenumber.
     * You can either make a bool test against this method or get the id of a contract if terminated
     *
     * @return misc:
     *			null if module ProvVoipEnvia is disabled
     *			false if there is no envia TEL contract or the contract is still active
     *			external_contract_id for the contract if terminated
     *
     * @author Patrick Reichel
     */
    public function envia_contract_terminated()
    {

        // no envia module ⇒ no envia contracts
        if (! \Module::collections()->has('ProvVoipEnvia')) {
            return;
        }

        // if there is no external id we assume that there is no envia contract
        if (is_null($this->contract_external_id)) {
            return false;
        }

        // as we are able to delete single phonenumbers from a contract (without deleting the contract if other numbers are attached)
        // we here have to count the numbers containing the current external contract id

        $envia_contract = \Modules\ProvVoipEnvia\Entities\EnviaContract::where('envia_contract_reference', '=', $this->contract_external_id)->first();

        // no contract – seems to be deleted
        if (is_null($envia_contract)) {
            return $envia_contract;
        }

        // no end date set: contract seems to be active
        if (is_null($envia_contract->external_termination_date) && is_null($envia_contract->end_date)) {
            return false;
        }

        return $this->contract_external_id;
    }

    /**
     * link to monitoring
     *
     * @author Ole Ernst
     */
    public function cdrs()
    {
        return $this->hasMany(\Modules\VoipMon\Entities\Cdr::class);
    }

    /**
     * Daily conversion (called by cron job)
     *
     * @author Patrick Reichel
     */
    public function daily_conversion()
    {
        $this->set_active_state();
    }

    /**
     * (De)Activate phonenumber depending on existance and (de)activation dates in PhonenumberManagement
     *
     * @author Patrick Reichel
     */
    public function set_active_state()
    {
        $changed = false;

        $management = $this->phonenumbermanagement;

        if (is_null($management)) {

            // if there is still no management: deactivate the number
            // TODO: decide if a phonenumbermanagement is required in each case or not
            // until then: don't change the state on missing management
            /* if ($this->active) { */
            /* 	$this->active = False; */
            /* 	$changed = True; */
            /* } */
            \Log::info('No PhonenumberManagement for phonenumber '.$this->prefix_number.'/'.$this->number.' (ID '.$this->id.') – will not change the active state.');
        } else {

            // get the dates for this number
            $act = $management->activation_date;
            $deact = $management->deactivation_date;

            if (! boolval($act)) {

                // Activation date not yet reached: deactivate
                if ($this->active) {
                    $this->active = false;
                    $changed = true;
                }
            } elseif ($act > date('c')) {

                // Activation date not yet reached: deactivate
                if ($this->active) {
                    $this->active = false;
                    $changed = true;
                }
            } else {
                if (! boolval($deact)) {

                    // activation date today or in the past, no deactivation date: activate
                    if (! $this->active) {
                        $this->active = true;
                        $changed = true;
                    }
                } else {
                    if ($deact > date('c')) {

                        // activation date today or in the past, deactivation date in the future: activate
                        if (! $this->active) {
                            $this->active = true;
                            $changed = true;
                        }
                    } else {

                        // deactivation date today or in the past: deactivate
                        if ($this->active) {
                            $this->active = false;
                            $changed = true;
                        }
                    }
                }
            }
        }
        // write to database if there are changes
        if ($changed) {
            if ($this->active) {
                \Log::info('Activating phonenumber '.$this->prefix_number.'/'.$this->number.' (ID '.$this->id.').');
            } else {
                \Log::info('Deactivating phonenumber '.$this->prefix_number.'/'.$this->number.' (ID '.$this->id.').');
            }

            $this->save();
        }
    }

    /**
     * Dummy method to match BaseModel::delete() requirements
     *
     * We do not have to delete envia TEL orders here – this is later done by cron job.
     *
     * @author Patrick Reichel
     */
    public function deleteNtoMEnviaOrder($envia_order)
    {
        return $envia_order->delete();
    }

    /**
     * BOOT:
     * - init phone observer
     */
    public static function boot()
    {
        parent::boot();

        self::observe(new \Modules\ProvVoip\Observers\PhonenumberObserver);
    }
}
