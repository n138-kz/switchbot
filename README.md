# [switchbot](https://github.com/n138-kz/switchbot)

## License

[Copyright (c) 2025 Yuu Komiya (n138), Under MIT License](LICENSE)  

## Example

### [Get device list](https://github.com/OpenWonderLabs/SwitchBotAPI?tab=readme-ov-file#php-example-code) (example: [example_device-list.php](example_device-list.php))

```http
GET https://api.switch-bot.com/v1.1/devices
```

### [Get device status](https://github.com/OpenWonderLabs/SwitchBotAPI?tab=readme-ov-file#get-device-status) (example: [example_device-status.php](example_device-status.php))

```http
GET /v1.1/devices/{deviceId}/status
```

### [Get Scenes list](https://github.com/OpenWonderLabs/SwitchBotAPI?tab=readme-ov-file#scenes) (example: [example_scenes-list.php](example_scenes-list.php))

```http
GET /v1.1/scenes
```

#### Execute manual scenes

```http
POST /v1.1/scenes/{sceneId}/execute
```
