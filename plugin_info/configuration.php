<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>
<form class="form-horizontal">
    <fieldset>
		<div class="form-group">
			<label class="col-md-4 control-label">{{Pin de la box}}
			</label>
			<div class="col-md-4">
				<input class="configKey form-control" data-l1key="client_pin"/>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-4 control-label">{{Nom d'hôte de la box}}
			</label>
			<div class="col-md-4">
				<input class="configKey form-control" data-l1key="client_host"/>
			</div>
			<div class="col-md-4">
				<a class="btn btn-primary" id="bt_GetBoxSomfy"> {{Découvrir la box}}</a>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-4 control-label">{{Port de communication}}</label>
			<div class="col-md-4">
				<input class="configKey form-control" data-l1key="client_port" />
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-4 control-label">{{Version du protocol}}</label>
			<div class="col-md-4">
				<input class="configKey form-control" disabled="true" data-l1key="client_version" />
			</div>
		</div>
        <div class="form-group">
			<label class="col-md-4 control-label">{{Nom d'utilisateur}}
			</label>
			<div class="col-md-4">
				<input class="configKey form-control" data-l1key="client_username"/>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-4 control-label">{{Mot de passe}}
			</label>
			<div class="col-md-4">
				<input class="configKey form-control inputPassword" data-l1key="client_password">
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-4 control-label">{{Clé de sécurité}}
			</label>
			<div class="col-md-4">
				<input class="configKey form-control" data-l1key="client_token"/>
			</div>
			<div class="col-md-4">
				<a class="btn btn-primary" id="bt_TokenSomfy"> {{Générer une clé de sécurité}}</a>
			</div>
		</div>
	</fieldset>
</form>

<script>
    $('#bt_GetBoxSomfy').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/somfy/core/ajax/somfy.ajax.php", // url du fichier php
            data: {
                action: "getBox",
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
                window.location.reload();
            }
        });
    });
	$('#bt_TokenSomfy').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/somfy/core/ajax/somfy.ajax.php", // url du fichier php
            data: {
                action: "generateToken",
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
                window.location.reload();
            }
        });
    });
</script>
