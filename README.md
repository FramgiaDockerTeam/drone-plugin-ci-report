# drone-plugin-ci-report
Plugin for Drone to communicate with CI Report system

## Parameters

- `base_api_url` - Base API URL (Recommend using [Drone Secret](http://readme.drone.io/usage/secrets/))

## Usage

Add following configuration to your `.drone.yml` file:

```YML
notify:
  ci-report:
    image: fdplugins/ci-report
    base_api_url: $$BASE_API_URL
```
