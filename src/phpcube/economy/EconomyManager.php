<?php

declare(strict_types=1);

namespace phpcube\economy;

use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use phpcube\Main;

class EconomyManager
{
    /** @var Main */
    private Main $plugin;

    /** @var string */
    private string $provider;

    /** @var Plugin|null */
    private ?Plugin $economyPlugin = null;

    /** @var string */
    private string $currencySymbol;

    /**
     * EconomyManager constructor.
     *
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
        $config = $plugin->getConfig();
        $this->provider = strtolower($config->getNested("economy.provider", "economyapi"));
        $this->currencySymbol = $config->getNested("economy.currency_symbol", "$");

        $this->initializeEconomyProvider();
    }

    /**
     * Initialize the economy provider
     */
    private function initializeEconomyProvider(): void
    {
        $server = $this->plugin->getServer();
        $pluginManager = $server->getPluginManager();

        switch ($this->provider) {
            case "economyapi":
                $this->economyPlugin = $pluginManager->getPlugin("EconomyAPI");
                if ($this->economyPlugin === null) {
                    $this->plugin->getLogger()->warning(
                        "EconomyAPI plugin not found. Drug purchases will be disabled."
                    );
                }
                break;

            case "bedrockeconomy":
                $this->economyPlugin = $pluginManager->getPlugin("BedrockEconomy");
                if ($this->economyPlugin === null) {
                    $this->plugin->getLogger()->warning(
                        "BedrockEconomy plugin not found. Drug purchases will be disabled."
                    );
                }
                break;

            case "coinapi":
                $this->economyPlugin = $pluginManager->getPlugin("CoinAPI");
                if ($this->economyPlugin === null) {
                    $this->plugin->getLogger()->warning("CoinAPI plugin not found. Drug purchases will be disabled.");
                }
                break;

            default:
                $this->plugin->getLogger()->warning(
                    "Unknown economy provider: {$this->provider}. Drug purchases will be disabled."
                );
                break;
        }
    }

    /**
     * Check if the economy provider is available
     *
     * @return bool
     */
    public function isEconomyAvailable(): bool
    {
        return $this->economyPlugin !== null;
    }

    /**
     * Get player's money
     *
     * @param Player $player
     * @return float
     */
    public function getPlayerMoney(Player $player): float
    {
        if (!$this->isEconomyAvailable()) {
            return 0.0;
        }

        try {
            switch ($this->provider) {
                case "economyapi":
                    return (float) $this->economyPlugin->myMoney($player);

                case "bedrockeconomy":
                    // BedrockEconomy uses async API, so we need to use a synchronous approach
                    $playerName = $player->getName();
                    $xuid = $player->getXuid();

                    // Try to use reflection to access the API
                    try {
                        if (class_exists("\\cooldogedev\\BedrockEconomy\\api\\BedrockEconomyAPI")) {
                            $bedrockEconomyAPI = new \ReflectionClass(
                                "\\cooldogedev\\BedrockEconomy\\api\\BedrockEconomyAPI"
                            );

                            // Try legacy method first
                            if ($bedrockEconomyAPI->hasMethod('legacy')) {
                                $legacyMethod = $bedrockEconomyAPI->getMethod('legacy');
                                $legacyAPI = $legacyMethod->invoke(null);

                                if ($legacyAPI !== null && method_exists($legacyAPI, 'getPlayerBalance')) {
                                    $balance = $legacyAPI->getPlayerBalance($playerName);
                                    if ($balance !== null) {
                                        return (float) $balance;
                                    }
                                }
                            }
                        }

                        // Try to use the plugin's API directly
                        $method = new \ReflectionMethod($this->economyPlugin, 'getAPI');
                        $api = $method->invoke($this->economyPlugin);

                        if ($api !== null) {
                            $method = new \ReflectionMethod($api, 'getPlayerBalance');
                            if ($method !== null) {
                                return (float) $method->invoke($api, $playerName);
                            }
                        }
                    } catch (\Throwable $e) {
                        $this->plugin->getLogger()->debug(
                            "Failed to get balance using reflection: " . $e->getMessage()
                        );
                    }

                    return 0.0;

                case "coinapi":
                    return (float) $this->economyPlugin->myCoin($player);

                default:
                    return 0.0;
            }
        } catch (\Throwable $e) {
            $this->plugin->getLogger()->error("Failed to get balance: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Check if player has enough money
     *
     * @param Player $player
     * @param float $amount
     * @return bool
     */
    public function hasEnoughMoney(Player $player, float $amount): bool
    {
        return $this->getPlayerMoney($player) >= $amount;
    }

    /**
     * Reduce player's money
     *
     * @param Player $player
     * @param float $amount
     * @return bool
     */
    public function reduceMoney(Player $player, float $amount): bool
    {
        if (!$this->isEconomyAvailable() || !$this->hasEnoughMoney($player, $amount)) {
            return false;
        }

        try {
            switch ($this->provider) {
                case "economyapi":
                    return $this->economyPlugin->reduceMoney($player, $amount) === 1;

                case "bedrockeconomy":
                    $playerName = $player->getName();
                    $xuid = $player->getXuid();

                    // Try to use reflection to access the API
                    try {
                        if (class_exists("\\cooldogedev\\BedrockEconomy\\api\\BedrockEconomyAPI")) {
                            $bedrockEconomyAPI = new \ReflectionClass(
                                "\\cooldogedev\\BedrockEconomy\\api\\BedrockEconomyAPI"
                            );

                            // Try legacy method first
                            if ($bedrockEconomyAPI->hasMethod('legacy')) {
                                $legacyMethod = $bedrockEconomyAPI->getMethod('legacy');
                                $legacyAPI = $legacyMethod->invoke(null);

                                if ($legacyAPI !== null && method_exists($legacyAPI, 'subtractFromPlayerBalance')) {
                                    $result = $legacyAPI->subtractFromPlayerBalance($playerName, (int)$amount);
                                    if ($result) {
                                        return true;
                                    }
                                }
                            }

                            // Try closure API
                            if ($bedrockEconomyAPI->hasMethod('CLOSURE')) {
                                $closureMethod = $bedrockEconomyAPI->getMethod('CLOSURE');
                                $closureAPI = $closureMethod->invoke(null);

                                if ($closureAPI !== null && method_exists($closureAPI, 'subtract')) {
                                    $closureAPI->subtract(
                                        $xuid,
                                        $playerName,
                                        (int)$amount,
                                        0,
                                        function () {
                                            // Success callback
                                        },
                                        function () {
                                            // Error callback
                                        }
                                    );
                                    return true;
                                }
                            }
                        }

                        // Try to use the plugin's API directly
                        $method = new \ReflectionMethod($this->economyPlugin, 'getAPI');
                        $api = $method->invoke($this->economyPlugin);

                        if ($api !== null) {
                            $method = new \ReflectionMethod($api, 'subtractFromPlayerBalance');
                            if ($method !== null) {
                                $method->invoke($api, $playerName, (int)$amount);
                                return true;
                            }
                        }
                    } catch (\Throwable $e) {
                        $this->plugin->getLogger()->debug(
                            "Failed to reduce balance using reflection: " . $e->getMessage()
                        );
                    }

                    return false;

                case "coinapi":
                    return $this->economyPlugin->reduceCoin($player, $amount) === 1;

                default:
                    return false;
            }
        } catch (\Throwable $e) {
            $this->plugin->getLogger()->error("Failed to reduce balance: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add money to player
     *
     * @param Player $player
     * @param float $amount
     * @return bool
     */
    public function addMoney(Player $player, float $amount): bool
    {
        if (!$this->isEconomyAvailable()) {
            return false;
        }

        try {
            switch ($this->provider) {
                case "economyapi":
                    return $this->economyPlugin->addMoney($player, $amount) === 1;

                case "bedrockeconomy":
                    $playerName = $player->getName();
                    $xuid = $player->getXuid();

                    // Try to use reflection to access the API
                    try {
                        if (class_exists("\\cooldogedev\\BedrockEconomy\\api\\BedrockEconomyAPI")) {
                            $bedrockEconomyAPI = new \ReflectionClass(
                                "\\cooldogedev\\BedrockEconomy\\api\\BedrockEconomyAPI"
                            );

                            // Try legacy method first
                            if ($bedrockEconomyAPI->hasMethod('legacy')) {
                                $legacyMethod = $bedrockEconomyAPI->getMethod('legacy');
                                $legacyAPI = $legacyMethod->invoke(null);

                                if ($legacyAPI !== null && method_exists($legacyAPI, 'addToPlayerBalance')) {
                                    $result = $legacyAPI->addToPlayerBalance($playerName, (int)$amount);
                                    if ($result) {
                                        return true;
                                    }
                                }
                            }

                            // Try closure API
                            if ($bedrockEconomyAPI->hasMethod('CLOSURE')) {
                                $closureMethod = $bedrockEconomyAPI->getMethod('CLOSURE');
                                $closureAPI = $closureMethod->invoke(null);

                                if ($closureAPI !== null && method_exists($closureAPI, 'add')) {
                                    $closureAPI->add(
                                        $xuid,
                                        $playerName,
                                        (int)$amount,
                                        0,
                                        function () {
                                            // Success callback
                                        },
                                        function () {
                                            // Error callback
                                        }
                                    );
                                    return true;
                                }
                            }
                        }

                        // Try to use the plugin's API directly
                        $method = new \ReflectionMethod($this->economyPlugin, 'getAPI');
                        $api = $method->invoke($this->economyPlugin);

                        if ($api !== null) {
                            $method = new \ReflectionMethod($api, 'addToPlayerBalance');
                            if ($method !== null) {
                                $method->invoke($api, $playerName, (int)$amount);
                                return true;
                            }
                        }
                    } catch (\Throwable $e) {
                        $this->plugin->getLogger()->debug(
                            "Failed to add balance using reflection: " . $e->getMessage()
                        );
                    }

                    return false;

                case "coinapi":
                    return $this->economyPlugin->addCoin($player, $amount) === 1;

                default:
                    return false;
            }
        } catch (\Throwable $e) {
            $this->plugin->getLogger()->error("Failed to add balance: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Format money amount with currency symbol
     *
     * @param float $amount
     * @return string
     */
    public function formatMoney(float $amount): string
    {
        return $this->currencySymbol . number_format($amount, 2);
    }

    /**
     * Get the economy provider name
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return $this->provider;
    }

    /**
     * Get the currency symbol
     *
     * @return string
     */
    public function getCurrencySymbol(): string
    {
        return $this->currencySymbol;
    }
}
