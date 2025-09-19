# CubeDrugs Plugin

A configurable drugs plugin for PocketMine-MP servers.

## Features

- Fully configurable drugs and effects
- Customizable prices
- Customizable messages
- Easy to use UI
- Flexible economy system (supports EconomyAPI, BedrockEconomy, and CoinAPI)
- Currency symbol customization

## Installation

1. Download the plugin
2. Place it in your server's `plugins` folder
3. Restart your server
4. Configure the plugin in `plugins/CubeDrugs/config.yml`

## Configuration

The plugin is fully configurable through the `config.yml` file. You can customize:

### Settings

```yaml
settings:
  # Plugin prefix shown in messages
  prefix: "§7(§cSubstances§7)§r §f"
  # Title shown in the UI
  ui-title: "§7(§cSubstances§7)§r §bEntertainment Menu"
  # Message shown when player doesn't have enough money
  not-enough-money: "You don't have enough money to §e{action}"
  # Message shown when player buys a drug
  purchase-message: "{price} has been deducted from your account. You §e{action}"
```

### Economy Settings

```yaml
economy:
  # Economy provider to use (economyapi, bedrockeconomy, coinapi)
  provider: "economyapi"
  # Currency symbol to use in messages
  currency_symbol: "$"
```

The plugin supports multiple economy plugins:
- **EconomyAPI**: The default economy provider
- **BedrockEconomy**: Alternative economy provider
- **CoinAPI**: Another alternative economy provider

### Drugs

Each drug can be configured with:
- Name: The name of the drug
- Price: How much it costs
- Button Text: The text shown on the button in the UI
- Action Text: The text shown in the message when the drug is used
- Effects: A list of effects applied when the drug is used

Example drug configuration:

```yaml
vape:
  name: "Vape"
  price: 300
  button-text: "§eUse Vape §f(§c{price}§f)"
  action-text: "used a vape"
  effects:
    - effect: "poison"
      duration: 15
      amplifier: 1
    - effect: "haste"
      duration: 30
      amplifier: 1
    - effect: "jump_boost"
      duration: 30
      amplifier: 1
    - effect: "regeneration"
      duration: 30
      amplifier: 1
```

Note: You can use `{price}` in the button text to automatically insert the formatted price with the currency symbol.

### Adding New Drugs

To add a new drug, simply add a new entry to the `drugs` section in the config.yml file:

```yaml
new_drug:
  name: "New Drug"
  price: 500
  button-text: "§eUse New Drug §f(§c{price}§f)"
  action-text: "used a new drug"
  effects:
    - effect: "speed"
      duration: 60
      amplifier: 2
    - effect: "jump_boost"
      duration: 60
      amplifier: 2
```

### Available Effects

- speed
- slowness
- haste
- mining_fatigue
- strength
- instant_health
- instant_damage
- jump_boost
- nausea
- regeneration
- resistance
- fire_resistance
- water_breathing
- invisibility
- blindness
- night_vision
- hunger
- weakness
- poison
- wither
- health_boost
- absorption
- saturation
- levitation
- fatal_poison
- conduit_power
- slow_falling

## Commands

- `/drugs` - Opens the drugs menu

## Permissions

- `cube.drugs` - Permission to use the `/drugs` command (default: true)

## Dependencies

- One of the following economy plugins:
  - EconomyAPI
  - BedrockEconomy
  - CoinAPI

## Credits

- Original authors: PHPCube, Renz
- Configurable version: [Your Name]