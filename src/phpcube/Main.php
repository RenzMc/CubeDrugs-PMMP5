<?php

namespace phpcube;

use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\command\{Command, CommandSender};
use pocketmine\player\Player;
use phpcube\form\SimpleForm;
use phpcube\form\CustomForm;
use phpcube\economy\EconomyManager;

class Main extends PluginBase
{
    private $economyManager;
    private $config;
    private $drugs = [];
    private $prefix;

    public function onEnable(): void
    {
        // Create resources directory if it doesn't exist
        @mkdir($this->getDataFolder() . "resources");

        // Save default config if it doesn't exist
        $this->saveDefaultConfig();

        // Load config
        $this->config = $this->getConfig();

        // Initialize economy manager
        $this->economyManager = new EconomyManager($this);

        // Load settings from config
        $this->prefix = $this->config->getNested("settings.prefix", "§7(§cSubstances§7)§r §f");

        // Load drugs from config
        $this->loadDrugsFromConfig();

        $this->getLogger()->info("Substances Plugin Loaded Successfully");

        // Check if economy is available
        if (!$this->economyManager->isEconomyAvailable()) {
            $this->getLogger()->warning("No compatible economy plugin found. Drug purchases will be disabled.");
        } else {
            $this->getLogger()->info("Using economy provider: " . $this->economyManager->getProviderName());
        }
    }

    /**
     * Load all drugs from the config file
     */
    private function loadDrugsFromConfig(): void
    {
        $drugsConfig = $this->config->get("drugs", []);

        foreach ($drugsConfig as $id => $drugData) {
            $this->drugs[$id] = $drugData;
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if ($command->getName() == 'drugs') {
            if (!$sender instanceof Player) {
                $sender->sendMessage("§cThis command can only be used by players!");
                return true;
            }
            $this->showSubstancesUI($sender);
        }
        return true;
    }

    public function showSubstancesUI(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, $data = null) {
            if ($data === null) {
                return;
            }

            // Get the drug ID from the index
            $drugIds = array_keys($this->drugs);
            if (!isset($drugIds[$data])) {
                return;
            }

            $drugId = $drugIds[$data];
            $drug = $this->drugs[$drugId];

            $price = $drug['price'] ?? 0;
            $actionText = $drug['action-text'] ?? "used {$drug['name']}";

            // Check if player has enough money
            if ($this->economyManager->hasEnoughMoney($player, $price)) {
                // Apply effects
                if (isset($drug['effects']) && is_array($drug['effects'])) {
                    if ($drugId === "clear_effects") {
                        // Special case for clearing effects
                        $player->getEffects()->clear();
                    } else {
                        // Apply all configured effects
                        foreach ($drug['effects'] as $effectData) {
                            $effect = $this->getEffectByName($effectData['effect']);
                            if ($effect !== null) {
                                $duration = ($effectData['duration'] ?? 15) * 20; // Convert seconds to ticks
                                $amplifier = $effectData['amplifier'] ?? 1;
                                $player->getEffects()->add(new EffectInstance($effect, $duration, $amplifier));
                            }
                        }
                    }
                }

                // Deduct money
                $this->economyManager->reduceMoney($player, $price);

                // Format price with currency symbol
                $formattedPrice = $this->economyManager->formatMoney($price);

                // Send purchase message
                $purchaseMsg = "{price} has been deducted from your account. You §e{action}";
                $message = $this->config->getNested("settings.purchase-message", $purchaseMsg);
                $message = str_replace(["{price}", "{action}"], [$formattedPrice, $actionText], $message);
                $player->sendMessage($this->prefix . $message);
            } else {
                // Send not enough money message
                $notEnoughMsg = "You don't have enough money to §e{action}";
                $message = $this->config->getNested("settings.not-enough-money", $notEnoughMsg);
                $message = str_replace("{action}", $actionText, $message);
                $player->sendMessage($this->prefix . $message);
            }
        });

        // Set form title from config
        $title = $this->config->getNested("settings.ui-title", "§7(§cSubstances§7)§r §bEntertainment Menu");
        $form->setTitle($title);

        // Add buttons for each drug
        foreach ($this->drugs as $id => $drug) {
            $price = $drug['price'] ?? 0;
            $formattedPrice = $this->economyManager->formatMoney($price);

            // Replace price placeholder in button text if it exists
            $buttonText = $drug['button-text'] ?? "§e{$drug['name']} §f(§c{price}§f)";
            $buttonText = str_replace("{price}", $formattedPrice, $buttonText);

            $form->addButton($buttonText);
        }

        $player->sendForm($form);
    }

    /**
     * Get effect instance by name
     *
     * @param string $name The effect name
     * @return Effect|null The effect instance or null if not found
     */
    private function getEffectByName(string $name)
    {
        switch (strtolower($name)) {
            case "speed":
                return VanillaEffects::SPEED();
            case "slowness":
                return VanillaEffects::SLOWNESS();
            case "haste":
                return VanillaEffects::HASTE();
            case "mining_fatigue":
                return VanillaEffects::MINING_FATIGUE();
            case "strength":
                return VanillaEffects::STRENGTH();
            case "instant_health":
                return VanillaEffects::INSTANT_HEALTH();
            case "instant_damage":
                return VanillaEffects::INSTANT_DAMAGE();
            case "jump_boost":
                return VanillaEffects::JUMP_BOOST();
            case "nausea":
                return VanillaEffects::NAUSEA();
            case "regeneration":
                return VanillaEffects::REGENERATION();
            case "resistance":
                return VanillaEffects::RESISTANCE();
            case "fire_resistance":
                return VanillaEffects::FIRE_RESISTANCE();
            case "water_breathing":
                return VanillaEffects::WATER_BREATHING();
            case "invisibility":
                return VanillaEffects::INVISIBILITY();
            case "blindness":
                return VanillaEffects::BLINDNESS();
            case "night_vision":
                return VanillaEffects::NIGHT_VISION();
            case "hunger":
                return VanillaEffects::HUNGER();
            case "weakness":
                return VanillaEffects::WEAKNESS();
            case "poison":
                return VanillaEffects::POISON();
            case "wither":
                return VanillaEffects::WITHER();
            case "health_boost":
                return VanillaEffects::HEALTH_BOOST();
            case "absorption":
                return VanillaEffects::ABSORPTION();
            case "saturation":
                return VanillaEffects::SATURATION();
            case "levitation":
                return VanillaEffects::LEVITATION();
            case "fatal_poison":
                return VanillaEffects::FATAL_POISON();
            case "conduit_power":
                return VanillaEffects::CONDUIT_POWER();
            case "slow_falling":
                return VanillaEffects::SLOW_FALLING();
            default:
                return null;
        }
    }
}
