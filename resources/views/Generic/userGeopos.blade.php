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
<!-- Get users geoposition when last update was more than 10 min ago -->
@if (\Module::collections()->has('HfcCustomer') && (time() - strtotime(\Auth::user()->geopos_updated_at)) > 10*60)

<script>

    function updatePos(pos)
    {
        $.ajax({
            type: 'post',
            url: '{{ route("user.updateGeopos") }}',
            timeout: 500,
            data: {
                _token: "{{\Session::get('_token')}}",
                id: '{{ \Auth::user()->id }}',
                x: pos.coords.longitude,
                y: pos.coords.latitude,
            },
        });
    }

    // https://www.w3schools.com/html/html5_geolocation.asp
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(updatePos);
    }

</script>

@endif
