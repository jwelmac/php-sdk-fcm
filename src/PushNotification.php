<?php

class PushNotification
{
    private $apiKey;
    private $url = "https://fcm.googleapis.com/fcm/send";
    private $badge;
    private $title;
    private $message;
    private $data = [];
    private $to;
    private $topic;
    private $methodPrefix = 'with';
    private $recipients = ['to', 'topic'];
    private $queue = [];

    /**
     * Create a new push notification object and 
     * load configuration from file
     */
    public function __construct() 
    {
        $ini_vars = parse_ini_file("config.ini");
        $this->apiKey = $ini_vars['apiKey'];
    }

    /**
     * Handle setting private variables in a chain
     */
    public function __call($method, $args) {
        // Check if method called started with expected prefix
        // OR not expected to have a prefix
        if(strpos($method, $this->methodPrefix) === 0) {
            $varname = strtolower(substr($method, 4));
        }
        else if(in_array($method, $this->recipients)) {
            $varname = $method;
        }

        // Set property directly if exists
        // or add it to the data
        $value = $args[0];
        property_exists($this, $varname)
            ? $this->$varname = $value
            : $this->data[$varname] = $value;

        //Return object for method chaining
        return $this;
    }

    /**
     * Save a push entry to the queue for processing when send method called
     */
    public function save()
    {
        $this->queue[] = $this->prepareBody();
    }

    /**
     * Send notification to device with token or topic
     * Note: token takes precedence over device
     * Adapted from: http://bit.ly/2jadby4
     */
    public function send() 
    {
        $results = [];
        
        foreach ($this->queue as $fields) {
            $headers = array (
                'Authorization: key=' . $this->apiKey,
                'Content-Type: application/json'
            );
            
            $ch = curl_init ();
            curl_setopt ( $ch, CURLOPT_URL, $this->url );
            curl_setopt ( $ch, CURLOPT_POST, true );
            curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
            curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt ( $ch, CURLOPT_POSTFIELDS, json_encode($fields) );
            
            $response = json_decode(curl_exec ( $ch ));

            curl_close ( $ch );
            $results[$fields['to']] = $response->success == 1;
        }

        return $results;
    }

    /**
     * Prepare the notification body to send to server
     * @return json Notification fields
     */
    private function prepareBody() {
        $display = [
            "body" => $this->message,
            "title" => $this->title
        ];

        $fields = array (
            'notification' => array (
                "sound" => "default",
                "click_action" => "FCM_PLUGIN_ACTIVITY",
                "icon" => "fcm_push_icon",
                "priority" => "high"
            ),
            'data' => $this->data
        );

        // Add badge if set
        if (isset($this->badge)) {
            $fields['notification']['badge'] = $this->badge;
        }

        // set the title and message for display in background and foreground
        $keys = ['data', 'notification'];
        foreach ($keys as $key) {
            $fields[$key] = array_merge($fields[$key], $display);
        }

        // Get the first set recipient 
        // Note: to will take precedence over topic if both set
        foreach ($this->recipients as $value) {
            if (!is_null($this->{$value})) {
                $fields['to'] = ($value === 'topic' ? '/topics/' : '') . $this->{$value};
                break;
            }
        }
        return $fields;
    }

}
