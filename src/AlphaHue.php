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
        $response = $rest->post('', json_encode('devicetype'=>"{$app_name}:{$device_name}"));
        return $response;
    }

    public function getConfiguration()
    {
        $response = $this->rest('config');
        $this->config = $response;
    }

    /**
     * Turns a light On or Off depending on Light ID.
     *
     * @param int    $light_id    ID number of the light attached to the Bridge.
     * @param string $light_state 'On' or 'Off' turns the light on or off. Defaults to 'On'.
     *
     * @return void
     */
    public function setLightOnStatus($light_id, $light_state='on')
    {
        $light_state = ('on' == $light_state); // On=true, Off=false
        $response = $this->rest("lights/{$lightId}/state", json_encode(array('on'=>$light_state)));
        return $response;
    }

    /**
     * Gets all IDs associated with lights attached to the Bridge.
     * 
     * @return mixed Array of light IDs or boolean false on failure.
     */
    public function getLightsIds()
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
     * @param string $type   LightGroup or Room.
     * @param array  $lights Array of Light IDs assigned to the Group.
     *
     */
    public function createGroup($name, $type='LightGroup', array $lights)
    {
        $params['name'] = $name;

        /**
         * Options for $type are currently limited to LightGroup and Room.
         *
         * LightGroup and Room are similar except for the following:
         * 1: Room groups can contain 0 lights.
         * 2: A light can be in only 1 Room group.
         * 3: A Room isn't automatically deleted when all lights in it are.
         *
         * @see http://developers.meethue.com/documentation/groups-api#21_get_all_groups
         */
        $params['type'] = $type;
        $params['lights'] = $lights;

        $response = $this->rest->post('groups', json_encode($params));
        return $response;
    }
}





























