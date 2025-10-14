<?php
/**
 * Module manager for WPComments
 *
 * @package WPComments
 * @since   2.0.0
 */

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface for WPComments modules
 */
interface WPComments_Module_Interface {
    /**
     * Get module unique identifier
     *
     * @return string Module ID
     */
    public function id(): string;

    /**
     * Check if module is enabled based on settings
     *
     * @param array $settings Plugin settings
     * @return bool Whether module is enabled
     */
    public function is_enabled(array $settings): bool;

    /**
     * Register module hooks and functionality
     *
     * @return void
     */
    public function register(): void;

    /**
     * Get module name for display
     *
     * @return string Module display name
     */
    public function get_name(): string;

    /**
     * Get module description
     *
     * @return string Module description
     */
    public function get_description(): string;
}

/**
 * WPComments Module Manager
 * 
 * Manages all plugin modules and their lifecycle
 */
class WPComments_Module_Manager {
    /** @var WPComments_Module_Interface[] */
    private $modules = [];
    
    /** @var array */
    private $settings = [];
    
    /** @var array */
    private $loaded_modules = [];
    
    /** @var bool */
    private $is_booted = false;

    /** @var self|null */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return self
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a module
     *
     * @param WPComments_Module_Interface $module Module instance
     * @return self
     * @throws \InvalidArgumentException If module ID already exists
     */
    public function register_module(WPComments_Module_Interface $module): self {
        $module_id = $module->id();
        
        if (isset($this->modules[$module_id])) {
            throw new \InvalidArgumentException(
                sprintf('Module with ID "%s" is already registered', $module_id)
            );
        }

        $this->modules[$module_id] = $module;
        
        return $this;
    }

    /**
     * Boot all enabled modules
     *
     * @param array $settings Plugin settings
     * @return void
     */
    public function boot(array $settings): void {
        if ($this->is_booted) {
            return;
        }

        $this->settings = $settings;
        $this->loaded_modules = [];

        foreach ($this->modules as $module) {
            try {
                if ($module->is_enabled($settings)) {
                    $module->register();
                    $this->loaded_modules[$module->id()] = $module;
                }
            } catch (\Exception $e) {
                error_log(
                    sprintf(
                        'WPComments: Failed to load module "%s": %s',
                        $module->id(),
                        $e->getMessage()
                    )
                );
            }
        }

        // Centralized resource enqueuing
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_all']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_all']);

        $this->is_booted = true;
    }

    /**
     * Get a specific module by ID
     *
     * @param string $module_id Module ID
     * @return WPComments_Module_Interface|null
     */
    public function get_module(string $module_id): ?WPComments_Module_Interface {
        return $this->modules[$module_id] ?? null;
    }

    /**
     * Get all registered modules
     *
     * @return WPComments_Module_Interface[]
     */
    public function get_modules(): array {
        return $this->modules;
    }

    /**
     * Get all loaded (enabled) modules
     *
     * @return WPComments_Module_Interface[]
     */
    public function get_loaded_modules(): array {
        return $this->loaded_modules;
    }

    /**
     * Check if a module is loaded
     *
     * @param string $module_id Module ID
     * @return bool
     */
    public function is_module_loaded(string $module_id): bool {
        return isset($this->loaded_modules[$module_id]);
    }

    /**
     * Enqueue frontend assets for all loaded modules
     *
     * @return void
     */
    public function enqueue_frontend_all(): void {
        foreach ($this->loaded_modules as $module) {
            try {
                if (method_exists($module, 'enqueue_frontend')) {
                    $module->enqueue_frontend();
                }
            } catch (\Exception $e) {
                error_log(
                    sprintf(
                        'WPComments: Failed to enqueue frontend assets for module "%s": %s',
                        $module->id(),
                        $e->getMessage()
                    )
                );
            }
        }
    }

    /**
     * Enqueue admin assets for all loaded modules
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_admin_all($hook): void {
        foreach ($this->loaded_modules as $module) {
            try {
                if (method_exists($module, 'enqueue_admin')) {
                    $module->enqueue_admin($hook);
                }
            } catch (\Exception $e) {
                error_log(
                    sprintf(
                        'WPComments: Failed to enqueue admin assets for module "%s": %s',
                        $module->id(),
                        $e->getMessage()
                    )
                );
            }
        }
    }

    /**
     * Reload modules with new settings
     *
     * @param array $settings New settings
     * @return void
     */
    public function reload(array $settings): void {
        $this->is_booted = false;
        $this->boot($settings);
    }

    /**
     * Get module information for admin display
     *
     * @return array
     */
    public function get_modules_info(): array {
        $info = [];
        
        foreach ($this->modules as $module) {
            $info[$module->id()] = [
                'id' => $module->id(),
                'name' => method_exists($module, 'get_name') ? $module->get_name() : $module->id(),
                'description' => method_exists($module, 'get_description') ? $module->get_description() : '',
                'enabled' => $module->is_enabled($this->settings),
                'loaded' => $this->is_module_loaded($module->id()),
            ];
        }
        
        return $info;
    }
}
