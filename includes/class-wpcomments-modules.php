<?php
/**
 * Module manager for WPComments
 */

namespace WPComments;

if (!defined('ABSPATH')) {
    exit;
}

interface WPComments_Module_Interface {
    public function id(): string;
    public function is_enabled(array $settings): bool;
    public function register(): void;
}

class WPComments_Module_Manager {
    /** @var WPComments_Module_Interface[] */
    private $modules = [];
    /** @var array */
    private $settings = [];

    public function register_module(WPComments_Module_Interface $module): self {
        $this->modules[$module->id()] = $module;
        return $this;
    }

    public function boot(array $settings): void {
        $this->settings = $settings;
        foreach ($this->modules as $module) {
            if ($module->is_enabled($settings)) {
                $module->register();
            }
        }
        // Centralized resource enqueuing
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_all']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_all']);
    }

    public function enqueue_frontend_all(): void {
        foreach ($this->modules as $module) {
            if ($module->is_enabled($this->settings) && method_exists($module, 'enqueue_frontend')) {
                $module->enqueue_frontend();
            }
        }
    }

    public function enqueue_admin_all($hook): void {
        foreach ($this->modules as $module) {
            if ($module->is_enabled($this->settings) && method_exists($module, 'enqueue_admin')) {
                $module->enqueue_admin($hook);
            }
        }
    }
}
