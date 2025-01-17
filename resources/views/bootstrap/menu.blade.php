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
?>
{{-- begin Navbar --}}
<nav id="header" class="header navbar navbar-expand navbar-default navbar-fixed-top d-print-none">
  {{-- only one row Navbar --}}
    <div class="d-flex">
      {{-- begin mobile sidebar expand / collapse button --}}
        <button type="button" class="navbar-toggle m-l-20" data-click="sidebar-toggled">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>

        {{-- NMSPrime Logo with link to global dashboard --}}
        <span class="navbar-brand d-none d-sm-none d-md-block">
          {!! '<a'.($nmsprimeLogoLink ? ' href="'.$nmsprimeLogoLink.'">' : '>') !!}
            <img src="{{asset('images/nmsprime-logo.png')}}" style="width:100%; margin-top:-10px; margin-left:5px" class="">
          </a>
        </span>

      {{-- end mobile sidebar expand / collapse button --}}
      <div class="col tab-overflow p-t-5 m-l-5 d-none d-md-block">
        <ul class="nav nav-pills p-t-5">
          <li class="prev-button"><a href="javascript:;" data-click="prev-tab" class="m-t-10"><i class="fa fa-arrow-left"></i></a></li>
          @yield('content_top')
          <li class="next-button"><a href="javascript:;" data-click="next-tab" class="m-t-10"><i class="fa fa-arrow-right"></i></a></li>
        </ul>
      </div>

      <ul class="navbar-nav ml-auto">
        {{-- global search form --}}
        <li class="nav-item d-flex">
          <a id="togglesearch" href="javascript:;" class="waves-effect waves-light" data-toggle="navbar-search">
            <i class="fa fa-search fa-2x" aria-hidden="true"></i>
          </a>
        </li>

        {{-- Help Section --}}
        <li class="nav-item dropdown d-none d-md-block">
          <a id="navbarDropdown"
            class="nav-link dropdown-toggle"
            href="#"
            role="button"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
            style="padding: 12px 10x 8px 8px;">
            <i class="fa fa-question fa-2x" aria-hidden="true"></i>
          </a>
          <div class="dropdown-menu" aria-labelledby="navbarDropdown" style="right: 0;left:auto;">
            <a class="dropdown-item" href="https://devel.roetzer-engineering.com/" target="_blank">
              <i class="fa fa-question-circle" aria-hidden="true" style="width: 20px;"></i>Documentation
            </a>
            <a class="dropdown-item" href="https://www.youtube.com/channel/UCpFaWPpJLQQQLpTVeZnq_qA" target="_blank">
              <i class="fa fa-tv" aria-hidden="true" style="width: 20px;"></i>Youtube
            </a>
            <a class="dropdown-item" href="https://nmsprime.com/forum" target="_blank">
              <i class="fa fa-wpforms" aria-hidden="true" style="width: 20px;"></i>Forum
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href={{route('SupportRequest.index')}}>
              <i class="fa fa-envelope-open" aria-hidden="true" style="width: 20px;"></i>Professional Help
            </a>
          </div>
        </li>

        @if (Module::collections()->has(['Dashboard', 'HfcBase']))
          {{-- Modem Statistics (Online/Offline) --}}
          <li  class='d-none d-md-block m-t-10' style='font-size: 2em; font-weight: bold'>
            <a href="{{ route('HfcBase.index') }}" style="text-decoration: none;">
              @if (is_object($modem_statistics))
                <span data-toggle="tooltip" data-placement="auto" title="{{ trans('messages.modem_statistics') }}">
                  <i class="{{ $modem_statistics->fa }} fa-lg text-{{ $modem_statistics->style }}"></i>
                  <span class="badge badge-{{ $modem_statistics->style }}">{!! $modem_statistics->text !!}</span>
                </span>
              @endif
            </a>
          </li>
        @endif
        @if (Module::collections()->has('ProvVoipEnvia'))
          {{-- count of user interaction needing EnviaOrders --}}
          <li  class='d-none d-md-block m-t-10' style='font-size: 2em; font-weight: bold'>
            <a href="{{route('EnviaOrder.index', ['show_filter' => 'action_needed'])}}" target="_self" style="text-decoration: none;">
              @if ($envia_interactioncount > 0)
                <span data-toggle="tooltip" data-placement="auto" title="{{ $envia_interactioncount }} {{ trans_choice('messages.envia_interaction', $envia_interactioncount )}}">
                  <i class="fa fa-times fa-lg text-danger"></i>
                  <span class="badge badge-danger">Envia<br>{{ $envia_interactioncount }}</span>
                </span>
              @else
                <span data-toggle="tooltip" data-placement="auto" title="{{ trans('messages.envia_no_interaction')}}">
                  <i class="fa fa-check fa-lg text-success"></i>
                </span>
              @endif
            </a>
          </li>
        @endif

        {{-- Notification Section --}}
        @include('bootstrap._navbar-notifications')

        {{-- User Menu --}}
        <li class="nav-item dropdown m-r-10">
          <a id="navbarDropdown"
            class="nav-link d-flex align-items-center dropdown-toggle"
            href="#"
            role="button"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false">
            <i class="fa fa-user-circle-o fa-2x d-inline" aria-hidden="true"></i>
            <span class="d-none d-md-inline">
              {{ $user->first_name.' '. $user->last_name }}
            </span>
          </a>
          <div class="dropdown-menu" aria-labelledby="navbarDropdown" style="right: 0;left:auto;">
            <a class="dropdown-item" href="{{ route('User.profile', $user->id) }}">
              <i class="fa fa-cog" aria-hidden="true"></i>
              {{ \App\Http\Controllers\BaseViewController::translate_view('UserSettings', 'Menu')}}
            </a>
            @if (Bouncer::can('update', App\User::class))
              <a class="dropdown-item" href="{{route('User.index')}}">
                <i class="fa fa-cogs" aria-hidden="true"></i>
                {{ \App\Http\Controllers\BaseViewController::translate_view('UserGlobSettings', 'Menu')}}
              </a>
            @endif
            @if (Bouncer::can('update', App\Role::class))
              <a class="dropdown-item" href="{{route('Role.index')}}">
                <i class="fa fa-users" aria-hidden="true"></i>
                {{ \App\Http\Controllers\BaseViewController::translate_view('UserRoleSettings', 'Menu')}}
              </a>
            @endif
            <div class="dropdown-divider"></div>
            {!! Form::open(['url' => route('logout.post')]) !!}
              <button class="dropdown-item">
                <i class="fa fa-sign-out" aria-hidden="true"></i>
                {{ \App\Http\Controllers\BaseViewController::translate_view('Logout', 'Menu')}}
              </button>
            {!!Form::close() !!}
          </div>
        </li>
      </ul>
    {{-- end header navigation right --}}
    <div class="search-form bg-white" style="height: auto;">
      <form id="globalSearchForm" class="form-open" method="GET" onsubmit="linkTag();">
        <div class="btn-group search-btn">
          <div class="input-group-append">
            <button class="btn btn-primary" onsubmit="linkTag();" for="prefillSearchbar">{{ trans('view.jQuery_sSearch') }}</button>
          </div>
          <select class="custom-select" id="prefillSearchbar" onchange="getSearchTag();">
            <option selected value="" data-route="{{ route('Base.globalSearch') }}">{{ trans('view.jQuery_All') }}</option>
            @if (Module::collections()->has('ProvMon')) {
              <option value="ip:" data-route="{{ route('Ip.globalSearch') }}">IP</option>
            @endif
          </select>
        </div>
        <input id="globalSearch" type="text" name="query" class="form-control navbar" placeholder="{{ \App\Http\Controllers\BaseViewController::translate_view('EnterKeyword', 'Search') }}">
        <a href="#" class="close" data-dismiss="navbar-search"><i class="fa fa-angle-up fa-2x" aria-hidden="true"></i></a>
      </form>
    </div>
  </div> {{-- End ROW --}}
</nav>

<script type="text/javascript">

function getSearchTag()
{
  var element = document.getElementById('prefillSearchbar');
  document.getElementById('globalSearch').value = element.options[element.selectedIndex].value;
}

function linkTag()
{
  var element = document.getElementById('prefillSearchbar');
  var select = element.options[element.selectedIndex];
  var search = document.getElementById('globalSearch');

  // if you search 'ip:...' in 'all', still use the 'ip:' tag
  // if there is no tag, you search in 'all'
  Array.from(element.options).forEach(function(option) {
    if (search.value.startsWith(option.value) && option.value != '') {
      var querySelector = `[value='${option.value}']`;
      document.getElementById('globalSearchForm').action = document.querySelectorAll(querySelector)[0].dataset.route;
    } else {
      document.getElementById('globalSearchForm').action = select.dataset.route;
    }
  });
}

</script>
