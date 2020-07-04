<?php

namespace AlphaHue;

use Curl\Curl;
use ErrorException;

class AlphaHue
{
    use LightColors;

    /** @var string $bridge_address Hostname of the bridge. * */
    public string $bridge_address = '';

    /** @var string $bridge_username Username registered with bridge. * */
    public string $bridge_username = '';

    /** @var array $room_classes Allowed room classes for Groups of the type 'Room'. * */
    public array $room_classes = [
        'Living room',
        'Kitchen',
        'Dining',
        'Bedroom',
        'Kids Bedroom',
        'Bathroom',
        'Nursery',
        'Recreation',
        'Office',
        'Gym',
        'Hallway',
        'Toilet',
        'Front Door',
        'Garage',
        'Terrace',
        'Garden',
        'Driveway',
        'Carport',
        'Other'
    ];

    private Curl $curl;
    private array $config;
    private string $baseUrl;

    /**
     * Initializes class with Bridge Address and Username.
     *
     * @see AlphaHue::authorize() For directions on obtaining the $bridge_username.
     *
     * @param string $bridge_address  Host (and optionally port) of the Bridge.
     * @param string $bridge_username Username retrieved from the Bridge through authentication.
     * @throws ErrorException
     *
     * @return void
     */
    public function __construct($bridge_address, $bridge_username)
    {
        $this->bridge_address = $bridge_address;
        $this->bridge_username = $bridge_username;

        $this->baseUrl = "http://{$bridge_address}/api/{$bridge_username}/";

        $this->curl = new Curl($this->baseUrl );
        $this->curl->setDefaultJsonDecoder($assoc = true);
        $this->curl->setHeader('Content-Type', 'application/json');

        $this->getConfiguration();
    }

    /**
     * Returns a registered username with the Bridge.
     *
     * Before connecting to the Bridge and running commands we need to get authorized. Hold the
     * button down on the bridge and execute this function from a script to generate a username.
     * The Bridge Username is required to instantiate this class.
     *
     * @param $bridge_address
     * @param $app_name
     * @param $device_name
     *
     * @return mixed Array response from the server or false on failure.
     */
    public static function authorize($bridge_address, $app_name = 'AlphaHue', $device_name = 'myServer')
    {
        $curl = new Curl("http://{$bridge_address}/api");
        $curl->setDefaultJsonDecoder($assoc = true);
        $curl->setHeader('Content-Type', 'application/json');
        $curl->post('', ['devicetype' => "{$app_name}:{$device_name}"]);

        return $curl->response;
    }

    /**
     * Saves the Bridge configuration settings.
     *
     * @return void
     */
    public function getConfiguration()
    {
        $response = $this->curl->get($this->baseUrl. 'config');
        $this->config = $response;
    }

    /**
     * Hold execution for a few seconds.
     *
     * The Bridge drops updates if they're coming in too quickly. This functions sets a pause
     * between requests.
     *
     * @return void
     */
    private function throttle()
    {
        // Pause for a quarter second.
        usleep(250000);
    }

    /**
     * Lists all supported Bridge timezones.
     *
     * @return mixed Array of supported timezones on success.
     */
    public function getTimezones()
    {
        return $this->curl->get('info/timezones');
    }

    /**
     * Checks compatibility of current API version against min and max versions.
     *
     * @param string $min_version Min acceptable version for compatibility to be true. Formatted x.x.x
     * @param string $max_version Max acceptable version for compatibility to be true. Formatted x.x.x
     *
     * @return bool True if compatible with parameters, false if not.
     */
    public function compatible($min_version, $max_version = false)
    {
        $compatible = version_compare($this->config['apiversion'], $min_version, '>=');
        if ($compatible && $max_version) {
            $compatible = version_compare($this->config['apiversion'], $max_version, '<=');
        }
        return $compatible;
    }

    /**
     * Turns a light On or Off depending on Light ID.
     *
     * @param int    $light_id    ID number of the light attached to the Bridge.
     * @param string $light_state 'on' or 'off' turns the light on or off. Defaults to 'on'.
     *
     * @return void
     */
    public function togglePower($light_id, $light_state = 'on')
    {
        $light_state = ('on' == $light_state); // on=true, off=false
        $this->throttle();

        $this->curl->put($this->baseUrl. "lights/{$light_id}/state",
            json_encode([
                'on' => $light_state
                ]
            )
        );

        return $this->curl->response;
    }

    /**
     * Gets all IDs associated with lights attached to the Bridge.
     *
     * @return mixed Array of light IDs or boolean false on failure.
     */
    public function getLightIds()
    {
        $response = $this->curl->get($this->baseUrl. 'lights');
        return array_keys($response);
    }

    /**
     * Gets the current On/Off state for a light.
     *
     * @param int $light_id ID number of a light attached to the Bridge.
     *
     * @return bool Light state, true for on, false for off.
     */
    public function getLightOnStatus($light_id)
    {
        $response = $this->curl->get($this->baseUrl. "lights/{$light_id}");
        return $response['state']['on'];
    }

    /**
     * Gets the attributes and state of a given light.
     *
     * @param int $light_id Light Identifier.
     *
     * @return mixed Array of state data on success.
     */
    public function getLightState($light_id)
    {
        return $this->curl->get($this->baseUrl. "lights/{$light_id}");
    }

    /**
     * Modifies the state of a specified light.
     *
     * @param int   $light_id   Light Identifier.
     * @param array $attributes { // All attributes are optional.
     *     @var bool   $on      On/Off state of the light. True=On, False=Off.
     *     @var int    $bri     The brightness value to set the light to. (0 to 254).
     *     @var int    $hue     The hue value to set light to. (0 to 65535).
     *     @var int    $sat     Saturation of the light. 254 is most saturated (colored) 0 is white.
     *     @var float  $xy      [x,y] coordinates of a color in CIE color space.
     *     @var int    $ct      The Mired Color temperature of the light. (153 to 500).
     *     @var string $alert   One of three values.
     *                          "none"    The light is not performing an alert effect.
     *                          "select"  The light is performing on breathe cycle.
     *                          "lselect" The light is performing breathe cycles for 15 seconds or
     *                                    until a "none" alert is sent.
     *     @var string $effect  The dynamic effect of the light, "none" and "colorloop" are supported.
     *     @var int    $bri_inc Increments/Decrements the brightness (1.7+ Ignored if $bri passed). (-254 to 254).
     *     @var int    $sat_inc Increments/Decrements the saturation (1.7+ Ignored if $sat passed). (-254 to 254).
     *     @var int    $hue_inc Increments/Decrements the hue (1.7+ Ignored if $hue passed). (-254 to 254).
     *     @var int    $ct_int  Increments/Decrements the value of the ct (1.7+ Ignored if $ct passed) (-65534 to 65534).
     *     @var int    $xy_inc  Increments/Decrements the value of xy (1.7+ Ignored if $xy passed) (-0.5 to 0.5).
     *     @var string $scene   Scene identifier.
     *     @var int    $transitiontime The duration of the transition. (Multiples of 100ms).
     * }
     *
     * @return mixed Confirmation array on success.
     */
    public function setLightState($light_id, $state)
    {
        $this->throttle();
        return $this->curl->put($this->baseUrl. "lights/{$light_id}/state", json_encode($state));
    }

    /**
     * Changes a light to a color by Hex color value.
     *
     * @param int $light_id Light Identifier.
     * @param string $hex Hex string.
     *
     * @return mixed Confirmation array on success.
     */
    public function setLightToHex($light_id, $hex)
    {
        $xy = $this->getXYPointFromHex($hex);
        return $this->setLightState($light_id, ['xy' => $xy]);
    }

    /**
     * Changes a light to a color by RGB values.
     *
     * @param array $rgb {
     *     @var int $red   Red color value (0-255).
     *     @var int $green Green color value (0-255).
     *     @var int $blue  Blue color value (0-255).
     * }
     *
     * @return mixed Confirmation array on success.
     */
    public function setLightToRGB($light_id, $rgb)
    {
        $xy = $this->getXYPointFromRGB($rgb);
        return $this->setLightState($light_id, ['xy' => $xy]);
    }

    /**
     * Sets light attributes.
     *
     * @param int   $light_id   Light Identifier.
     * @param array $attributes {
     *     @var string $name Light name.
     * }
     *
     * @return mixed Confirmation array on success.
     */
    public function setLightAttributes($light_id, $attributes)
    {
        $this->throttle();
        return $this->curl->put($this->baseUrl. "lights/{$light_id}", json_encode($attributes));
    }

    /**
     * Deletes a light from the Bridge.
     *
     * @param int $light_id Light Identifier.
     *
     * @return mixed Confirmation array on success.
     */
    public function deleteLight($light_id)
    {
        $this->throttle();
        return $this->curl->delete($this->baseUrl. "lights/{$light_id}");
    }

    /**
     * Searches for new lights (v>=1.1 new switches).
     *
     * @return mixed Confirmation array on success.
     */
    public function searchNewDevices()
    {
        return $this->curl->post($this->baseUrl. 'lights');
    }

    /**
     * Get all Group IDs associated to the Bridge.
     *
     * @return mixed Array of Groups or false on failure.
     */
    public function getGroups()
    {
        return $this->curl->get($this->baseUrl. 'groups');
    }

    /**
     * Creates a group with the provided name, type and lights.
     *
     * @param string $name   Group name.
     * @param array  $lights Array of Light IDs assigned to the Group.
     * @param string $type   'LightGroup' or 'Room'.
     *
     * @return mixed Array response on success, false on failure.
     */
    public function createGroup($name, array $lights, $type = 'LightGroup', $room_class = 'Other')
    {
        $params['name'] = $name;

        if ($this->compatible('1.11.0')) {
            /**
             * Options for creating Group $type are currently limited to LightGroup and Room.
             *
             * Note: Room is also only an option for API v>=1.11
             *
             * LightGroup and Room are similar except for the following:
             * 1: Room groups can contain 0 lights.
             * 2: A light can be in only 1 Room group.
             * 3: A Room isn't automatically deleted when all lights in it are.
             *
             * Created Room groups are given a default Room Class of 'Other' unless specified,
             * There is a set list of acceptable Room Classes.
             *
             * @see AlphaHue::$room_classes for list of acceptable classes.
             * @see http://developers.meethue.com/documentation/groups-api#21_get_all_groups
             */
            $params['type'] = ('Room' == $type) ? 'Room' : 'LightGroup';

            if ('Room' == $type) {
                // Validate that the Room class in an accepted value, if not, default to 'Other'.
                $params['room_class'] = in_array($room_class, $this->room_classes) ? $room_class : 'Other';
            }
        }

        // Make sure the light IDs are sent over as strings or the API with throw an error.
        $params['lights'] = array_map('strval', $lights);

        $this->throttle();
        return $this->curl->post($this->baseUrl. 'groups', json_encode($params));
    }

    /**
     * Modifies the name, light and class memembership of a group.
     *
     * @param int   $group_id   Group ID number. Group 0 refers to all lights.
     * @param array $attributes {
     *     @var string $name   The new name for the Group.
     *     @var array  $lights IDs of the lights that should be in the group.
     *     @var string $class  (required v>1.11) Category of the Room type.
     * }
     *
     * @return mixed Confirmation array on success.
     */
    public function setGroupAttributes($group_id, $attributes)
    {
        $this->throttle();
        return $this->curl->put($this->baseUrl. "groups/{$group_id}", json_encode($attributes));
    }

    /**
     * Deletes a Group.
     *
     * @param int $group_id Group ID number.
     *
     * @return mixed Confirmation array on success.
     */
    public function deleteGroup($group_id)
    {
        $this->throttle();
        return $this->curl->delete($this->baseUrl. "groups/{$group_id}");
    }

    /**
     * Modifies the state of all lights in a group.
     *
     * @param int   $group_id   Group ID number. Group 0 refers to all lights.
     * @param array $attributes { // All attributes are optional.
     *     @var bool   $on      On/Off state of the light. True=On, False=Off.
     *     @var int    $bri     The brightness value to set the light to. (0 to 254).
     *     @var int    $hue     The hue value to set light to. (0 to 65535).
     *     @var int    $sat     Saturation of the light. 254 is most saturated (colored) 0 is white.
     *     @var float  $xy      [x,y] coordinates of a color in CIE color space.
     *     @var int    $ct      The Mired Color temperature of the light. (153 to 500).
     *     @var string $alert   One of three values.
     *                          "none"    The light is not performing an alert effect.
     *                          "select"  The light is performing on breathe cycle.
     *                          "lselect" The light is performing breathe cycles for 15 seconds or
     *                                    until a "none" alert is sent.
     *     @var string $effect  The dynamic effect of the light, "none" and "colorloop" are supported.
     *     @var int    $bri_inc Increments/Decrements the brightness (1.7+ Ignored if $bri passed). (-254 to 254).
     *     @var int    $sat_inc Increments/Decrements the saturation (1.7+ Ignored if $sat passed). (-254 to 254).
     *     @var int    $hue_inc Increments/Decrements the hue (1.7+ Ignored if $hue passed). (-254 to 254).
     *     @var int    $ct_int  Increments/Decrements the value of the ct (1.7+ Ignored if $ct passed) (-65534 to 65534).
     *     @var int    $xy_inc  Increments/Decrements the value of xy (1.7+ Ignored if $xy passed) (-0.5 to 0.5).
     *     @var string $scene   Scene identifier.
     *     @var int    $transitiontime The duration of the transition. (Multiples of 100ms).
     * }
     *
     * @return mixed Confirmation array on success.
     */
    public function setGroupState($group_id, $state)
    {
        $this->throttle();
        return $this->curl->put($this->baseUrl. "groups/{$group_id}/action", json_encode($state));
    }

    /**
     * Gets a list of all sensors that have been added to the Bridge.
     *
     * @return mixed Array of sensor information.
     */
    public function getSensors()
    {
        return $this->curl->get($this->baseUrl. 'sensors');
    }

    /**
     * Gets a list of all rules that are in the bridge.
     *
     * @return mixed Array of rules.
     */
    public function getRules()
    {
        return $this->curl->get($this->baseUrl. 'rules');
    }

    /**
     * Returns a rule object with id matching <id> or an error 3 if <id> is not available.
     *
     * @param int $rule_id Rule Identifier.
     *
     * @return mixed Confirmation array on success.
     */
    public function getRule($rule_id)
    {
        return $this->curl->get($this->baseUrl. "rules/{$rule_id}");
    }

    /**
     * Delete a rule with specified ID.
     *
     * @param mixed $rule_id Rule Indentifier.
     *
     * @return mixed Confirmation array on success.
     */
    public function deleteRule($rule_id)
    {
        $this->throttle();
        return $this->curl->get($this->baseUrl. "rules/{$rule_id}");
    }

    /**
     * Creates a new rule.
     *
     * Creates a new rule in the bridge rule engine. A rule must contain at least 1 condition (max 8)
     * and at least 1 action (max 8). All conditions must evaluate to true for the action to be performed.
     *
     * @param string $name      Rule name.
     * @param array $conditions {
     *     @var string $address  Path to an attribute of a sensor resource.
     *     @var string $operator eq, gt, lt, dx (equals, greater than, less than or value has changed).
     *     @var string $value    The resource attribute is compared to this value using the given operator.
     *                           The value is cast to the data type of the resource attribute.
     * }
     * @param array $action {
     *     @var string $address  Path to an attribute of a sensor resource.
     *     @var string $method   The HTTP method used to send the body to the given address POST,PUT,DELETE for
     *                           local addresses.
     *     @var string $body     JSON string to be sent to the relevant resource.
     * }
     *
     * @return mixed Confirmation array on success.
     */
    public function createRule($name, $conditions, $actions)
    {
        $params['name'] = $name;
        $params['conditions'] = $conditions;
        $params['actions'] = $actions;

        $this->throttle();
        return $this->curl->post($this->baseUrl. 'rules', json_encode($params));
    }

    /**
     * Creates a new rule.
     *
     * Creates a new rule in the bridge rule engine. A rule must contain at least 1 condition (max 8)
     * and at least 1 action (max 8). All conditions must evaluate to true for the action to be performed.
     *
     * @param int   $rule_id Rule identifier.
     * @param array $attributes['conditions'] {
     *     @var string $address  Path to an attribute of a sensor resource.
     *     @var string $operator eq, gt, lt, dx (equals, greater than, less than or value has changed).
     *     @var string $value    The resource attribute is compared to this value using the given operator.
     *                           The value is cast to the data type of the resource attribute.
     * }
     * @param array $attributes['action'] {
     *     @var string $address  Path to an attribute of a sensor resource.
     *     @var string $method   The HTTP method used to send the body to the given address POST,PUT,DELETE for
     *                           local addresses.
     *     @var string $body     JSON string to be sent to the relevant resource.
     * }
     *
     * @return mixed Confirmation array on success.
     */
    public function updateRule($rule_id, $attributes)
    {
        $this->throttle();
        return $this->curl->put($this->baseUrl. "rules/{$rule_id}", json_encode($attributes));
    }

    /**
     * Gets a list of all schedules that have been added to the bridge.
     *
     * @return mixed Confirmation array on success.
     */
    public function getSchedules()
    {
        return $this->curl->get($this->baseUrl. "schedules");
    }

    /**
     * Allows the user to create new schedules. The bridge can store up to 100 schedules.
     *
     * @param array $attributes {
     *     @var string $name        Name for the new schedule. Defaults to 'schedule'.
     *     @var string $description Description of the new schedule. Defaults to empty.
     *     @var object $command     Command to execute when the scheduled event occurs.
     *     @var string $time        Time when the scheduled event will occur. Time is measured in
     *                              UTC time. Either time or localtime has to be provided.
     *     @var string $localtime   Local time when the scheduled event will occur.
     *     @var string $status      'enabled' or 'disabled'.
     *     @var bool   $autodelete  If set to true, the schedule will be removed automatically if
     *                              expired, if set to false it will be disabled.
     * }
     *
     * @return mixed Confirmation array on success.
     */
    public function createSchedule($attributes)
    {
        /**
         * $arguments['command']->address; Path to light resource, a group resource or any other
         *                                 bridge resources.
         * $arguments['command']->method;  The HTTP method to send the body to the given address.
         *                                 Either 'POST', 'PUT', 'DELETE' for local addresses.
         * $arguments['command']->body;    JSON string to be sent to the relevant resource.
         */
        $this->throttle();
        return $this->curl->post($this->baseUrl. "schedules", json_encode($attributes));
    }

    /**
     * Gets all attributes for a schedule.
     *
     * @param int $schedule_id Schedule Identifier.
     *
     * @see AlphaHue::createScehdule() Attributes array in documentation is same as response.
     *
     * @return mixed Attribute array on succcess.
     */
    public function getSchedule($schedule_id)
    {
        return $this->curl->get($this->baseUrl. "schedules/{$schedule_id}");
    }

    /**
     * Allows the user to create new schedules. The bridge can store up to 100 schedules.
     *
     * @param array $attributes {
     *     @var string $name        Name for the new schedule. Defaults to 'schedule'.
     *     @var string $description Description of the new schedule. Defaults to empty.
     *     @var object $command     Command to execute when the scheduled event occurs.
     *     @var string $time        Time when the scheduled event will occur. Time is measured in
     *                              UTC time. Either time or localtime has to be provided.
     *     @var string $localtime   Local time when the scheduled event will occur.
     *     @var string $status      'enabled' or 'disabled'.
     *     @var bool   $autodelete  If set to true, the schedule will be removed automatically if
     *                              expired, if set to false it will be disabled.
     * }
     *
     * @return mixed Confirmation array on success.
     */
    public function setSchedule($attributes)
    {
        /**
         * $arguments['command']->address; Path to light resource, a group resource or any other
         *                                 bridge resources.
         * $arguments['command']->method;  The HTTP method to send the body to the given address.
         *                                 Either 'POST', 'PUT', 'DELETE' for local addresses.
         * $arguments['command']->body;    JSON string to be sent to the relevant resource.
         */
        $this->throttle();
        return $this->curl->post($this->baseUrl. "schedules", $attributes);
    }

    /**
     * Deletes a schedule from the bridge.
     *
     * @param $schedule_id
     * @return mixed Confirmation array on success.
     */
    public function deleteSchedule($schedule_id)
    {
        $this->throttle();
        return $this->curl->delete($this->baseUrl. "schedules/{$schedule_id}");
    }
}
