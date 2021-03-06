# PHP Firebase Cloud Messaging Helper

Quickly and easily send a push notification using Firebase to a client using cordova-plugin-fcm from your PHP backend.

## Methods

Set the title, message, and recipient as shown below
Optionally, set the badge number and associated data

|Property    |Method      | Parameter | Note                      |
|------------|------------|-----------|---------------------------|
| `+` title     | withTitle  |  string   |                           |
| `+` message   | withMessage|  string   |                           |
| `~` recipient |  to        |  string   | user device token         |
| `~` recipient |  topic     |  string   | message topic (user group) |
|  badge     |  withBadge |  number   | iOS - App icon badge      |
|  data      |  withData* |  array    | Data to attach to message |

`+` Required
`~` Only one needed
`*` All other method calls starting with 'with' will be added to the data i.e. `withFoo('bar')` sets `data['foo'] = 'bar'`

### `save()`

- Saves the current push notification request in the queue.

### `send()`

- Sends all requests in the queue to the Firebase server

- Returns an array mapping each token to a boolean indicating whether the push was successful.

## Setup

To send the push notification you will need your API (Server) Key which can be found under the `Cloud messaging` tab in your Firebase dashboard settings.

This can be set:

- while constructing a new `PushNotification` object
- by calling the method `setApiKey`
- within `src/config.ini` (see `src/config.ini.sample`)

## Usage

### Setting the data field implicitly

```php
$push = new PushNotification('_YOUR_API_KEY');
$push->to('adsfasdf:adsfadsfadf')
     ->withTitle('hello')
     ->withMessage('there')
     ->withLink('/msgevents')
     ->withBadge(1)
     ->save()
     ->send();
```

### Setting the data field explicitly

```php
$push = new PushNotification();
$push->to('adsfasdf:adsfadsfadf')
     ->withTitle('hello')
     ->withMessage('there')
     ->withData(['link' => '/msgevents'])
     ->withBadge(1)
     ->save()
     ->setApiKey('_YOUR_API_KEY')
     ->send();
```

- Both requests above will yield the following body being sent to the FCM server.

```json
{
    "notification": {
        "body":"there",
        "title":"hello",
        "sound":"default",
        "click_action":"FCM_PLUGIN_ACTIVITY",
        "icon":"fcm_push_icon",
        "badge":1
    },
    "data":{
        "body":"there",
        "title":"hello",
        "link":"/msgevents"
    },
    "to":"adsfasdf:adsfadsfadfadsasd"
}"
```

### Without data

```php
$push = new PushNotification('_YOUR_API_KEY');
$push->to('adsfasdf:adsfadsfadf')
     ->withTitle('hello')
     ->withMessage('there')
     ->save()
     ->send();
```

### Sending to multiple recipients

```php
$messages = [
    [
        "token" => 'abc:1234',
        "title" => "Hello",
        "body" => "there",
        "link" => "/msgevents",
        "badge" => 5
    ],
    [
        "token" => 'def:5678',
        "title" => "World",
        "body" => "where",
        "link" => "/messages"
    ],
];

// Create new push notification object
// API Key to be read from config.ini
$push = new PushNotification();

// Add all messages to the queue
foreach ($messages as $message) {
    extract($message);
    $push->to($token)
         ->withTitle($title)
         ->withMessage($body)
         ->withLink($link)
         ->save();
}

// Send off queue
$result = $push->send();

// var_dump($result);
// array(2) {
//   ["abc:1234"]=>
//   bool(true)
//   ["def:5678"]=>
//   bool(true)
// }
```
