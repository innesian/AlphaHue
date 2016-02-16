<?php namespace AlphaHue;

class AlphaHue
{
    /** @var string $bridge_address Hostname of the bridge. **/
    public $bridge_address = '';

    /** @var string $bridge_username Username registered with bridge. **/
    public $bridge_username = '';

    /** @var array $room_classes Allowed room classes for Groups of the type 'Room'. **/
    public $room_classes = array(
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
    );

    /**
     * Initializes class with Bridge Address and Username.
     *
     * @see AlphaHue::authorize() For directions on obtaining the $bridge_username.
     *
     * @param string $bridge_address  Host (and optionally port) of the Bridge.
     * @param string $bridge_username Username retrieved from the Bridge through authentication.
     *
     * @return void
     */
    public function __construct($bridge_address, $bridge_username)
    {
        $this->bridge_address  = $bridge_address;
        $this->bridge_username = $bridge_username;

        // Initialize the Rest Client.
        $this->rest = new \PhpRestClient\PhpRestClient("http://{$bridge_address}/api/{$bridge_username}");

        $this->getConfiguration();
    }
    
    /**
     * Returns a registered username with the Bridge.
     *
     * Before connecting to the Bridge and running commands we need to get authorized. Hold the
     * button down on the bridge and execute this function from a script to generate a username. 
     * The Bridge Username is required to instantiate this class.
     *
     * @uses \PhpRestClient\PhpRestClient
     *
     * @param $bridge_address
     * @param $app_name
     * @param $device_name
     *
     * @return mixed Array response from the server or false on failure.
     */
    public static function authorize($bridge_address, $app_name='AlphaHue', $device_name='myServer')
    {
        $rest = new \PhpRestClient\PhpRestClient("http://{$bridge_address}/api");
        $response = $rest->post('', json_encode(array('devicetype'=>"{$app_name}:{$device_name}")));
        return $response;
    }

    /**
     * Saves the Bridge configuration settings.
     *
     * @return void
     */
    public function getConfiguration()
    {
        $response = $this->rest->get('config');
        $this->config = $response;
    }

    /**
     * Lists all supported Bridge timezones.
     *
     * @return mixed Array of supported timezones on success.
     */
    public function getTimezones()
    {
        $response = $this->rest->get('info/timezones');
        return $response;
    }

    /**
     * Checks compatibility of current API version against min and max versions.
     *
     * @param string $min_version Min acceptable version for compatibility to be true. Formatted x.x.x
     * @param string $max_version Max acceptable version for compatibility to be true. Formatted x.x.x
     *
     * @return bool True if compatible with parameters, false if not.
     */
    public function compatible($min_version, $max_version=false)
    {
        $compatible = false;
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
    public function togglePower($light_id, $light_state='on')
    {
        $light_state = ('on' == $light_state); // on=true, off=false
        $response = $this->rest->put("lights/{$light_id}/state", json_encode(array('on'=>$light_state)));
        return $response;
    }

    /**
     * Gets all IDs associated with lights attached to the Bridge.
     * 
     * @return mixed Array of light IDs or boolean false on failure.
     */
    public function getLightIds()
    {
        $response = $this->rest->get('lights');
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
        $response = $this->rest->get("lights/{$light_id}");
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
        $response = $this->rest->get("lights/{$light_id}");
        return $response;
    }

    /**
     * Modifies the state of a specified light.
     *
     * @param int   $light_id   Light Identifier. 
     * @param array $attributes { // All attributes are optional.
     *     @var bool   $on      On/Off state of the light. True=On, False=Off.
     *     @var int    $bri     The brightness value to set the light to. (0 to 60).
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
        $response = $this->rest->put("lights/{$group_id}/state", json_encode($state));
        return $response;
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
        $response = $this->rest->put("lights/{$light_id}", json_encode($attributes));
        return $response;
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
        $response = $this->rest->delete("lights/{$light_id}");
        return $response;
    }

    /**
     * Searches for new lights (v>=1.1 new switches).
     *
     * @return mixed Confirmation array on success.
     */
    public function searchNewDevices()
    {
        $response = $this->rest->post('lights');
        return $response;
    }

    /**
     * Get all Group IDs associated to the Bridge.
     *
     * @return mixed Array of Groups or false on failure. 
     */
    public function getGroups()
    {
        $response = $this->rest->get('groups');
        return $response;
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
    public function createGroup($name, array $lights, $type='LightGroup', $room_class='Other')
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

        $response = $this->rest->post('groups', json_encode($params));
        return $response;
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
        $response = $this->rest->put("groups/{$group_id}", json_encode($attributes));
        return $response;
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
        $response = $this->rest->delete("groups/{$group_id}");
        return $response;
    }

    /**
     * Modifies the state of all lights in a group.
     *
     * @param int   $group_id   Group ID number. Group 0 refers to all lights.
     * @param array $attributes { // All attributes are optional.
     *     @var bool   $on      On/Off state of the light. True=On, False=Off.
     *     @var int    $bri     The brightness value to set the light to. (0 to 60).
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
        $response = $this->rest->put("groups/{$group_id}/action", json_encode($state));
        return $response;
    }

    /**
     * Gets a list of all sensors that have been added to the Bridge.
     *
     * @return mixed Array of sensor information.
     */
    public function getSensors()
    {
        $response = $this->rest->get('sensors');
        return $response;
    }

    /**
     * Gets a list of all rules that are in the bridge.
     *
     * @return mixed Array of rules.
     */
    public function getRules()
    {
        $response = $this->rest->get('rules');
        return $response;
    }

    /**
     * Returns a rule object with id matching <id> or an error 3 if <id> is not available.
     *
     * @param int $rule_id Rule Identifier.
     */
    public function getRule($rule_id)
    {
        $reponse = $this->rest->get("rules/{$rule_id}");
        return $response;
    }  


}












