# [switchbot](https://github.com/n138-kz/switchbot)

## License

[Copyright (c) 2025 Yuu Komiya (n138), Under MIT License](LICENSE)  

## Official Docs
- [![](https://www.google.com/s2/favicons?size=64&domain=https://blog.switchbot.jp/)【API】新バージョンAPI v1.1を公開しました](https://blog.switchbot.jp/announcement/api-v1-1/)
- [![](https://www.google.com/s2/favicons?size=64&domain=https://github.com/)SwitchBotAPI](https://github.com/OpenWonderLabs/SwitchBotAPI)

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
