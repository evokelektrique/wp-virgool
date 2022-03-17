<?php
/**
 * Plugin Name:     Virgoolio
 * Plugin URI:      https://github.com/evokelektrique/virgool/
 * Description:     Display your virgool.io posts in your WordPress websites
 * Author:          EVOKE
 * Author URI:      https://github.com/evokelektrique/
 * Text Domain:     virgool
 * Domain Path:     /languages
 * Version:         0.2.0
 *
 * @package         Virgool Plugin
 */

require_once __DIR__ . "/vendor/autoload.php";

use Virgoolio\Virgool;

class Virgool_WP {

	private static $instance;
	private $virgool_client;
	private $cache;
	private $username;

	public function __construct() {
		$this->cache = [
			"expire" => 2, // Hours
			"key" => "virgool_posts",
		];

		$this->setup_hooks();
	}

	public static function get_instance() {
		if(!isset(self::$instance)) {
			self::$instance = new Virgool_WP();
		}

		return self::$instance;
	}

	public function get_posts(): array {
		$posts = get_transient($this->cache["key"] . $this->username);

		if($posts === false) {
			$posts = $this->virgool_client->get_posts();
			set_transient($this->cache["key"], $posts, $this->cache["expire"] * 60 * 60);
		}

		return $posts;
	}

	public function get_content(string $mode, int $limit): string {
		$posts = $this->get_posts();
		$posts = array_slice($posts, 0, $limit);
		$path = plugin_dir_path(__FILE__);
		$file = file_get_contents("{$path}templates/{$mode}.mustache");

		$mustache = new Mustache_Engine([
			"entity_flags" => ENT_QUOTES
		]);

		return $mustache->render($file, ["posts" => $posts]);
	}

	protected function setup_hooks() {
		// Virgool Plugin shortcode
		add_shortcode('wp_virgool', function($args, $content = null) {
			global $virgool_wp;
			$this->username = $args["user"] ?? false;

			if(!$this->username) {
				return false;
			}

			$this->virgool_client = new Virgool($this->username);

			$limit = $args["limit"] ?? 5;
			$mode = "light";
			$content = $virgool_wp->get_content($mode, $limit);

		    return $content;
		});

		// Load styles
		$application_css = plugin_dir_url( __FILE__ ) . 'dist/app.css';
		wp_enqueue_style('virgool-styles', $application_css, array(), null, false);
	}

}

$GLOBALS['virgool_wp'] = Virgool_WP::get_instance();
