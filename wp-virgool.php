<?php
/**
 * Plugin Name:     Wp Virgool
 * Plugin URI:      https://github.com/evokelektrique/wp-virgool/
 * Description:     Display your virgool.io posts in your WordPress websites
 * Author:          EVOKE
 * Author URI:      https://github.com/evokelektrique/
 * Text Domain:     wp-virgool
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Wp_Virgool
 */

require_once __DIR__ . "/vendor/autoload.php";

use Virgoolio\Virgool;

class WP_Virgool {

	private static $instance;
	private $virgool_client;
	private $cache;
	private $username;

	public function __construct() {
		$this->username = "virgool";
		$this->cache = null;
		$this->virgool_client = new Virgool($this->username);

		$this->setup_hooks();
	}

	public static function get_instance() {
		if(!isset(self::$instance)) {
			self::$instance = new WP_Virgool();
		}

		return self::$instance;
	}

	public function get_posts(): array {
		$posts = $this->virgool_client->get_posts();

		return $posts;
	}

	public function get_content(string $mode): string {
		$posts = $this->get_posts();
		$path = plugin_dir_path(__FILE__);
		$file = file_get_contents("{$path}templates/{$mode}.mustache");

		$mustache = new Mustache_Engine([
			"entity_flags" => ENT_QUOTES
		]);

		return $mustache->render($file, ["posts" => $posts]);
	}

	protected function setup_hooks() {
		add_shortcode('wp_virgool', function($args, $content = null) {
			global $wp_virgool;

			$mode = "light";
			$content = $wp_virgool->get_content($mode);

		    return $content;
		});
	}

}

$GLOBALS['wp_virgool'] = WP_Virgool::get_instance();
