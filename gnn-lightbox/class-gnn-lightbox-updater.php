<?php
/**
 * GNN Lightbox GitHub Updater
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GNN_Lightbox_Updater {

    private $owner = 'BigDesigner';
    private $repo = 'gnn-lightbox';
    private $plugin_slug = 'gnn-lightbox/gnn-lightbox.php';
    private $slug = 'gnn-lightbox';
    private $github_api_url = '';
    private $transient_key = 'gnn_lightbox_update_info';

    public function __construct() {
        $this->github_api_url = "https://api.github.com/repos/{$this->owner}/{$this->repo}/releases/latest";

        add_action( 'admin_init', array( $this, 'manual_update_check' ) );
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
        add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );
    }

    public function manual_update_check() {
        if ( isset( $_GET['gnn_lightbox_check_update'] ) && $_GET['gnn_lightbox_check_update'] == '1' ) {
            check_admin_referer( 'gnn_lightbox_manual_update' );

            if ( ! current_user_can( 'update_plugins' ) ) {
                return;
            }

            delete_site_transient( $this->transient_key );
            delete_site_transient( 'update_plugins' );
            wp_safe_redirect( admin_url( 'update-core.php?force-check=1' ) );
            exit;
        }
    }

    private function get_github_release_info() {
        $release_info = get_site_transient( $this->transient_key );
        if ( false === $release_info ) {
            $response = wp_remote_get( $this->github_api_url, array(
                'timeout' => 10,
                'headers' => array(
                    'Accept' => 'application/vnd.github.v3+json',
                )
            ) );

            if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
                return false;
            }

            $release_info = json_decode( wp_remote_retrieve_body( $response ) );
            set_site_transient( $this->transient_key, $release_info, 12 * HOUR_IN_SECONDS );
        }

        return $release_info;
    }

    /**
     * GitHub's auto-generated source code zipball (zipball_url) packages the
     * repo root as-is, leaving the plugin file nested one level deeper than
     * expected — which makes WordPress report "No valid plugin found" during
     * the update. We use the properly structured .zip asset uploaded to the
     * release via Actions instead.
     */
    private function get_package_url( $release_info ) {
        if ( ! empty( $release_info->assets ) && is_array( $release_info->assets ) ) {
            foreach ( $release_info->assets as $asset ) {
                if ( isset( $asset->browser_download_url, $asset->name ) && preg_match( '/\.zip$/i', $asset->name ) ) {
                    return $asset->browser_download_url;
                }
            }
        }

        return isset( $release_info->zipball_url ) ? $release_info->zipball_url : '';
    }

    public function check_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $release_info = $this->get_github_release_info();

        if ( $release_info && isset( $release_info->tag_name ) ) {
            $version = ltrim( $release_info->tag_name, 'v' );
            
            if ( version_compare( GNN_LIGHTBOX_VERSION, $version, '<' ) ) {
                $obj = new stdClass();
                $obj->slug = $this->plugin_slug;
                $obj->plugin = $this->plugin_slug;
                $obj->new_version = $version;
                $obj->url = $release_info->html_url;
                $obj->package = $this->get_package_url( $release_info );
                $obj->icons = array(); 
                $obj->banners = array(); 

                $transient->response[ $this->plugin_slug ] = $obj;
            }
        }

        return $transient;
    }

    public function plugin_info( $res, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $res;
        }

        if ( $this->slug !== $args->slug ) {
            return $res;
        }

        $release_info = $this->get_github_release_info();

        if ( $release_info ) {
            $res = new stdClass();
            $res->name = 'GNN Lightbox';
            $res->slug = $this->slug;
            $res->version = ltrim( $release_info->tag_name, 'v' );
            $res->author = 'BigDesigner';
            $res->homepage = $release_info->html_url;
            $res->requires = '5.0';
            $res->tested = '6.4';
            $package_url = $this->get_package_url( $release_info );
            $res->download_link = $package_url;
            $res->trunk = $package_url;
            $res->last_updated = $release_info->published_at;
            $res->sections = array(
                'description' => 'Modern, lightweight and fast lightbox plugin built with vanilla JS and CSS3.',
                'changelog' => nl2br( esc_html( $release_info->body ) )
            );
            return $res;
        }

        return $res;
    }

    public function post_install( $response, $hook_extra, $result ) {
        global $wp_filesystem;

        if ( isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] === $this->plugin_slug ) {
            // Install directory is usually like {repo}-{commit-hash}
            $plugin_folder = $result['destination'];
            
            // Expected folder
            $expected_destination = dirname( $plugin_folder ) . '/gnn-lightbox';

            if ( $plugin_folder !== $expected_destination ) {
                $wp_filesystem->move( $plugin_folder, $expected_destination );
                $result['destination'] = $expected_destination;
            }
        }

        return $result;
    }
}
