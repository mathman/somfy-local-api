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

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';
require_once __DIR__  . '/../php/somfy.inc.php';

class somfy extends eqLogic {
    /*     * *************************Attributs****************************** */

	public static $states = [
        ['logicalId' => 'core:Memorized1PositionState', 'name' => 'Position mémorisé', 'template' => 'tile'],
		['logicalId' => 'core:TargetClosureState', 'name' => 'Position ciblé', 'template' => 'tile'],
        ['logicalId' => 'core:RSSILevelState', 'name' => 'Niveau RSSI', 'template' => 'tile'],
        ['logicalId' => 'core:ClosureState', 'name' => 'Position', 'template' => 'shutter'],
		['logicalId' => 'core:NameState', 'name' => 'Nom', 'template' => 'line'],
		['logicalId' => 'core:DiscreteRSSILevelState', 'name' => 'Etat RSSI', 'template' => 'line'],
        ['logicalId' => 'core:StatusState', 'name' => 'Etat', 'template' => 'line'],
        ['logicalId' => 'core:OpenClosedState', 'name' => 'Etat volet', 'template' => 'line'],
		['logicalId' => 'core:MovingState', 'name' => 'Mouvement en cours', 'template' => 'line'],
		['logicalId' => 'internal:IntrusionDetectedState', 'name' => 'Intrusion détecté', 'template' => 'line'],
		['logicalId' => 'internal:CurrentAlarmModeState', 'name' => 'Mode alarme', 'template' => 'line'],
		['logicalId' => 'internal:TargetAlarmModeState', 'name' => 'Mode alarme ciblé', 'template' => 'line'],
		['logicalId' => 'internal:AlarmDelayState', 'name' => 'Durée alarme', 'template' => 'tile'],
		['logicalId' => 'core:CountryCodeState', 'name' => 'Code pays', 'template' => 'tile'],
		['logicalId' => 'internal:LightingLedPodModeState', 'name' => 'Etat led', 'template' => 'tile'],
		['logicalId' => 'internal:BatteryStatusState', 'name' => 'Mode batterie', 'template' => 'tile'],
		['logicalId' => 'core:LocalIPv4AddressState', 'name' => 'Adresse IP', 'template' => 'tile'],
		['logicalId' => 'core:ConnectivityState', 'name' => 'Etat connexion', 'template' => 'tile'],
    ];
	
	public static $commands = [
        ['logicalId' => 'setClosure', 'name' => 'Consigne position', 'subType' => 'slider', 'minValue' => '0', 'maxValue' => '100', 'generic_type' => 'FLAP_SLIDER', 'inverse' => true, 'parameter' => 'slider', 'stateName' => 'core:ClosureState'],
		['logicalId' => 'open', 'name' => 'Ouvrir', 'subType' => 'other', 'icon' => '<i class="fa fa-arrow-up"></i>', 'generic_type' => 'FLAP_UP'],
        ['logicalId' => 'close', 'name' => 'Fermer', 'subType' => 'other', 'icon' => '<i class="fa fa-arrow-down"></i>', 'generic_type' => 'FLAP_DOWN'],
		['logicalId' => 'stop', 'name' => 'Arreter', 'subType' => 'other', 'icon' => '<i class="far fa-stop-circle"></i>', 'generic_type' => 'FLAP_STOP'],
		['logicalId' => 'arm', 'name' => 'Armer alarme', 'subType' => 'other', 'icon' => '<i class="icon jeedomapp-lock-home"></i>', 'generic_type' => 'ALARM_ARMED'],
		['logicalId' => 'disarm', 'name' => 'Desarmer alarme', 'subType' => 'other', 'icon' => '<i class="icon jeedomapp-lock-ouvert"></i>', 'generic_type' => 'ALARM_RELEASED'],
		['logicalId' => 'alarmOn', 'name' => 'Activer alarme', 'subType' => 'other', 'icon' => '<i class="icon jeedom-bell"></i>', 'generic_type' => 'SIREN_ON'],
		['logicalId' => 'alarmOff', 'name' => 'Désactiver alarme', 'subType' => 'other', 'icon' => '<i class="icon jeedom-no-bell"></i>', 'generic_type' => 'SIREN_OFF'],
		['logicalId' => 'on', 'name' => 'Activer', 'subType' => 'other', 'icon' => '<i class="icon jeedom-on"></i>', 'generic_type' => 'ENERGY_ON'],
		['logicalId' => 'off', 'name' => 'Désactiver', 'subType' => 'other', 'icon' => '<i class="icon jeedom-off"></i>', 'generic_type' => 'ENERGY_OFF']
    ];

    /*     * ***********************Methode static*************************** */

    public static function dependancy_info($_refresh = false) {
		$return = array();
		$return['log'] = 'somfy_update';
		$return['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependance';
		$return['state'] = (self::compilationOk()) ? 'ok' : 'nok';
		return $return;
	}

	public static function dependancy_install() {
		log::remove(__CLASS__ . '_update');
		return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder(__CLASS__) . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
	}
	
	public static function compilationOk() {
		if (shell_exec('ls /usr/bin/node 2>/dev/null | wc -l') == 0) {
			return false;
		}
		return true;
	}
    
    public static function deamon_info() {
		$return = array();
		$return['state'] = 'nok';
		$pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
		if (file_exists($pid_file)) {
			if (posix_getsid(trim(file_get_contents($pid_file)))) {
				$return['state'] = 'ok';
			} else {
				shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
			}
		}
		$return['launchable'] = 'ok';
		return $return;
	}
	
	public static function deamon_start($_debug = false) {
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		$gateway_path = dirname(__FILE__) . '/../../resources/somfy';
        $key = config::byKey('somfy::key', __CLASS__);
        $secret = config::byKey('somfy::secret', __CLASS__);

		$cmd = 'node ' . $gateway_path . '/index.js ';
		$cmd .= '9000';
		$cmd .= ' ' . jeedom::getApiKey(__CLASS__);
		$cmd .= ' ' . jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
		
		log::add(__CLASS__, 'info', 'Lancement démon somfy : ' . $cmd);
		exec($cmd . ' >> ' . log::getPathToLog(__CLASS__) . ' 2>&1 &');
		$i = 0;
		while ($i < 30) {
			$deamon_info = self::deamon_info();
			if ($deamon_info['state'] == 'ok') {
				break;
			}
			sleep(1);
			$i++;
		}
		if ($i >= 30) {
			log::add(__CLASS__, 'error', 'Impossible de lancer le démon somfy', 'unableStartDeamon');
			return false;
		}
		message::removeAll(__CLASS__, 'unableStartDeamon');
		log::add(__CLASS__, 'info', 'Démon somfy lancé');
	}
	
	public static function deamon_stop() {
		try {
			$deamon_info = self::deamon_info();
			if ($deamon_info['state'] == 'ok') {
				try {
					somfyRequest('/stop');
				} catch (Exception $e) {
					
				}
			}
			$pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
			if (file_exists($pid_file)) {
				$pid = intval(trim(file_get_contents($pid_file)));
				system::kill($pid);
			}
			sleep(1);
		} catch (\Exception $e) {
			
		}
	}
	
	public static function getBox() {
		log::add(__CLASS__, 'debug', "getBox()");
		$services = somfyRequest('queryServices');
		if (sizeof($services) > 0) {
			config::save('client_host', $services[0]['host'], __CLASS__);
			config::save('client_pin', $services[0]['txt']['gateway_pin'], __CLASS__);
			config::save('client_port', $services[0]['port'], __CLASS__);
			config::save('client_version', $services[0]['txt']['fw_version'], __CLASS__);
		}
	}
	
	public static function generateToken() {
		log::add(__CLASS__, 'debug', "generateToken()");
		$login = config::byKey('client_username', __CLASS__, '');
		$password = config::byKey('client_password', __CLASS__, '');
		$pin = config::byKey('client_pin', __CLASS__, '');
		$token = somfyRequest('generateToken?login=' . $login . '&password=' . $password . '&pin=' . $pin);
		if ($token['success'] == true) {
			config::save('client_token', $token['token'], __CLASS__);
		}
	}
    
    public static function syncEqLogic() {
		log::add(__CLASS__, 'debug', "syncEqLogic()");

		$host = config::byKey('client_host', __CLASS__, '');
		$port = config::byKey('client_port', __CLASS__, '');
		$token = config::byKey('client_token', __CLASS__, '');
		$devices = somfyRequest('setup/devices?host=' . $host . '&port=' . $port . '&token=' . $token);
        foreach ($devices as $device) {
			//log::add('somfy', 'debug', print_r($device, true));
			log::add(__CLASS__, 'debug', $device['label']);
			$newEqLogic = eqLogic::byLogicalId($device['deviceURL'], __CLASS__);
			if (!is_object($newEqLogic)) {
				$newEqLogic = new somfy();
				$newEqLogic->setEqType_name(__CLASS__);
				$newEqLogic->setIsEnable(0);
				$newEqLogic->setIsVisible(0);
				$newEqLogic->setName($device['label']);
				$newEqLogic->setLogicalId($device['deviceURL']);
				$newEqLogic->save();
			}
			$newEqLogic->updateCmds($device);
        }
    }
    
    public static function pull() {
        $host = config::byKey('client_host', __CLASS__, '');
		$port = config::byKey('client_port', __CLASS__, '');
		$token = config::byKey('client_token', __CLASS__, '');
		$devices = somfyRequest('setup/devices?host=' . $host . '&port=' . $port . '&token=' . $token);
        foreach ($devices as $device) {
            if ($device['available'] != true) {
                log::add(__CLASS__, 'debug', "Device " . $device['deviceURL'] . " not available");
                continue;
            }
            $eqLogic = eqLogic::byLogicalId($device['deviceURL'], __CLASS__);
            if (is_object($eqLogic)) {
                log::add(__CLASS__, 'debug', "Update data from device " . $device['deviceURL']);
                $eqLogic->updateData($device);
            }
        }
	}

    /*     * *********************Méthodes d'instance************************* */
	
	public function updateCmds($device) {
		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new somfyCmd();
		}
		$refresh->setName('Rafraichir');
		$refresh->setEqLogic_id($this->getId());
		$refresh->setLogicalId('refresh');
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->setOrder(0);
		$refresh->save();
		
		$order = 1;
		switch ($device['definition']['uiClass']) {
			case 'OnOff':
			case 'RollerShutter':
			case 'Alarm':
			case 'Pod':
			case 'ProtocolGateway':
				foreach ($device['states'] as $state) {
					$key = array_search($state['name'], array_column(somfy::$states, 'logicalId'));
					if ($key !== false) {
						switch ($state['type']) {
							case 1:
								$cmd = $this->getCmd(null, $state['name']);
								if (!is_object($cmd)) {
									$cmd = new somfyCmd();
								}
								$cmd->setName(somfy::$states[$key]['name']);
								$cmd->setTemplate('dashboard', somfy::$states[$key]['template']);
								$cmd->setTemplate('mobile', 'tile');
								$cmd->setEqLogic_id($this->getId());
								$cmd->setLogicalId($state['name']);
								$cmd->setType('info');
								$cmd->setSubType('numeric');
								$cmd->setOrder($order);
								$order++;
								$cmd->save();
								break;
							case 3:
								$cmd = $this->getCmd(null, $state['name']);
								if (!is_object($cmd)) {
									$cmd = new somfyCmd();
								}
								$cmd->setName(somfy::$states[$key]['name']);
								$cmd->setEqLogic_id($this->getId());
								$cmd->setLogicalId($state['name']);
								$cmd->setType('info');
								$cmd->setSubType('string');
								$cmd->setOrder($order);
								$order++;
								$cmd->save();
								break;
							case 6:
								$cmd = $this->getCmd(null, $state['name']);
								if (!is_object($cmd)) {
									$cmd = new somfyCmd();
								}
								$cmd->setName(somfy::$states[$key]['name']);
								$cmd->setEqLogic_id($this->getId());
								$cmd->setLogicalId($state['name']);
								$cmd->setType('info');
								$cmd->setSubType('binary');
								$cmd->setOrder($order);
								$order++;
								$cmd->save();
								break;
							default:
								break;
						}
						log::add(__CLASS__, 'debug', "Etat " . $state['name'] . " crée");
					}
					else {
						log::add(__CLASS__, 'debug', "Etat " . $state['name'] . " non pris en compte");
					}
				}
				break;
			default:
				break;
		}
		foreach ($device['definition']['commands'] as $command) {
			$key = array_search($command['commandName'], array_column(somfy::$commands, 'logicalId'));
			if ($key !== false) {
				$cmd = $this->getCmd(null, $command['commandName']);
				if (!is_object($cmd)) {
					$cmd = new somfyCmd();
				}
				$cmd->setName(somfy::$commands[$key]['name']);
				$cmd->setEqLogic_id($this->getId());
				$cmd->setLogicalId($command['commandName']);
				$cmd->setType('action');
				$cmd->setSubType(somfy::$commands[$key]['subType']);
				if (isset(somfy::$commands[$key]['icon'])) {
					$cmd->setDisplay('icon', somfy::$commands[$key]['icon']);
				}
				if (isset(somfy::$commands[$key]['generic_type'])) {
					$cmd->setDisplay('generic_type', somfy::$commands[$key]['generic_type']);
				}
				if (isset(somfy::$commands[$key]['minValue']) && isset(somfy::$commands[$key]['maxValue'])) {
					$cmd->setConfiguration('minValue', somfy::$commands[$key]['minValue']);
					$cmd->setConfiguration('maxValue', somfy::$commands[$key]['maxValue']);
				}
				if (isset(somfy::$commands[$key]['stateName'])) {
					$cmd->setValue($this->getCmd(null, somfy::$commands[$key]['stateName'])->getId());
				}

				$cmd->setOrder($order);
				$order++;
				$cmd->save();
				
				log::add(__CLASS__, 'debug', "Commande " . $command['commandName'] . " crée");
			}
			else {
				log::add(__CLASS__, 'debug', "Commande " . $command['commandName'] . " non prise en compte");
			}
		}
	}

    public function refresh() {
        if ($this->getIsEnable()) {
			$host = config::byKey('client_host', __CLASS__, '');
			$port = config::byKey('client_port', __CLASS__, '');
			$token = config::byKey('client_token', __CLASS__, '');
            $device = somfyRequest('setup/devices?host=' . $host . '&port=' . $port . '&token=' . $token . '&deviceURL=' . $this->getLogicalId());
			if ($device['available'] != true) {
                log::add(__CLASS__, 'debug', "Device " . $this->getLogicalId() . " not available");
                return false;
            }
            log::add(__CLASS__, 'debug', "Update data from device " . $this->getLogicalId());
            $this->updateData($device);
            return true;
        }
    }
    
    public function updateData($device) {
        foreach ($device['states'] as $state) {
            $cmd = $this->getCmd(null, $state['name']);
            if (is_object($cmd)) {
                $value = $state['value'];
				switch ($state['name']) {
					case "core:ClosureState":
					case "core:TargetClosureState":
					case "core:Memorized1PositionState":
						$value = 100 - $value;
						break;
					default:
						break;
				}
                $cmd->event($value);
            }
        }
    }
	
	public function sendCommand($commandName, $parameter = null) {
		$action["deviceURL"] = $this->getLogicalId();
		$command["name"] = $commandName;

		if ($parameter !== null) {
			$parameters = array_map('intval', explode(",", $parameter));
			$command["parameters"] = $parameters; // array(100);
		}
		else {
			$parameters = "";
			$command["parameters"] = $parameters;
		}

		$commands[] = $command;

		$action["commands"] = $commands;

		$actions[] = $action;

		$row["label"] = $commandName;
		$row["actions"] = $actions;

		$host = config::byKey('client_host', __CLASS__, '');
		$port = config::byKey('client_port', __CLASS__, '');
		$token = config::byKey('client_token', __CLASS__, '');
        $response = somfyRequest('/exec/apply?host=' . $host . '&port=' . $port . '&token=' . $token, $row);
		if (isset($response['execId'])) {
			return true;
		}
		return false;
	}

    /*     * **********************Getteur Setteur*************************** */
}

class somfyCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    public function execute($_options = array()) {
        $eqLogic = $this->getEqLogic();
        if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1) {
            throw new Exception(__('Equipement desactivé impossible d\éxecuter la commande : ' . $this->getHumanName(), __FILE__));
        }
		log::add('somfy','debug','command: '.$this->getLogicalId().' parameters: '.json_encode($_options));
		switch ($this->getLogicalId()) {
            case "refresh":
                return $eqLogic->refresh();
            default:
				$key = array_search($this->getLogicalId(), array_column(somfy::$commands, 'logicalId'));
				if ($key !== false) {
					if (isset(somfy::$commands[$key]['parameter'])) {
						if (isset(somfy::$commands[$key]['inverse']) && somfy::$commands[$key]['inverse'] === true) {
							$parameter = 100 - $_options[somfy::$commands[$key]['parameter']];
						}
						else {
							$parameter = $_options[somfy::$commands[$key]['parameter']];
						}
						return $eqLogic->sendCommand($this->getLogicalId(), $parameter);
					}
					else {
						return $eqLogic->sendCommand($this->getLogicalId());
					}
				}
                return false;
        }
    }

    /*     * **********************Getteur Setteur*************************** */
}


