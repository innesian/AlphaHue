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
    public function setLightOnStatus($light_id, $light_state='on')
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
     * Gets the name, light memembership and last command for a given group.
     * 
     * @param int $group_id Group ID number.
     *
     * @return mixed Array of attributes on success, false on failure.
     */
    public function getGroupAttributes($group_id)
    {
        $response = $this->rest->get("groups/{$group_id}");
        return $response;
    }
}
