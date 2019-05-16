<?php
/*
 * Plugin Name: WP Tableur
 * Description: Framework de gestion de tables SQL dans Wordpress.
 * Version:     1.3
 * Author:      Billerot
 * Author URI:  https://github.com/pbillerot
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tbr
 * Domain Path: /languages
 */
// Prohibit direct script loading.
defined('ABSPATH') || die('No direct script access allowed!');
define('PBI_VERSION', '1.3.0');

/**
 * Plugin inspiré du modèle
 * https://github.com/collizo4sky/WP_List_Table-Class-Plugin-Example
 * https://www.sitepoint.com/using-wp_list_table-to-create-wordpress-admin-tables/
 *
 */
class Tableur_Plugin
{
    // class instance
    static $instance;

    private $tableurs = array();
    private $tableur;
    private $menus = array();
    private $wp_list_table;

    private $slug_menu;

    // class constructor
    public function __construct()
    {
        // utilisation d'ajax pour le x-editable
        add_action('wp_ajax_tbr_x_editable', [ &$this, 'tbr_x_editable']);
        add_action('wp_ajax_tbr_get_items', [ &$this, 'tbr_get_items']);

        // Traduction
        load_plugin_textdomain('tbr', false, basename(dirname(__FILE__)) . '/languages');

        // Initialisation du log
        $this->create_log();
        // Chargement des tableurs
        $this->load_tableurs();

        // Mise à jour automatique de la base toutes les 2 minutes
        // register_activation_hook(__FILE__, array($this, 'db_update'));
        // Mise à jour forcée au démarrage du plugin
        $this->db_update();

        if (!empty($_REQUEST['page']) and $this->slug_menu == $_REQUEST['page']):
            // tbr_journal("slug[$this->slug_menu]", "set-screen-option");
            add_filter('set-screen-option', [__CLASS__, 'set_screen'], 10, 3);
        endif;

        add_action('admin_enqueue_scripts', [ & $this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'plugin_menu']);

    }

    public function create_log()
    {
        if (strpos(home_url(), 'localhost') > 0):
            if (!file_exists(dirname(__FILE__) . '/log')) {
                mkdir(dirname(__FILE__) . '/log', 0777, true);
                file_put_contents(dirname(__FILE__) . '/log/tableur.log', '** TABLEUR.LOG **', FILE_APPEND | LOCK_EX);
            }
        endif;
    }

    public function enqueue_scripts()
    {
        // Chargment des css
        wp_enqueue_style('poshytip_css', plugin_dir_url(__FILE__) . 'assets/poshytip/tip-yellowsimple/tip-yellowsimple.css', array(), PBI_VERSION, 'all');
        wp_enqueue_style('xeditable_css', plugin_dir_url(__FILE__) . 'assets/jquery-editable/css/jquery-editable.css', array('poshytip_css'), PBI_VERSION, 'all');
        // my plugin
        wp_enqueue_style('style-tbr', plugin_dir_url(__FILE__) . 'assets/css/plugin.css', array('xeditable_css'), PBI_VERSION, 'all');

        // chargement des scripts
        wp_enqueue_script('script-poshytip', plugin_dir_url(__FILE__) . 'assets/poshytip/jquery.poshytip.min.js', array('jquery'), PBI_VERSION, true); // dans le footer
        wp_enqueue_script('script-xeditable', plugin_dir_url(__FILE__) . 'assets/jquery-editable/js/jquery-editable-poshytip.min.js', array('jquery', 'script-poshytip'), "1.5.1", false); // dans le head
        // my plugin
        wp_enqueue_script('script-tbr', plugin_dir_url(__FILE__) . 'assets/js/plugin.js', array('jquery', 'script-xeditable'), PBI_VERSION, true); // dans le footer
        // Ajout de la variable Tableur.page au script plugin.js
        wp_localize_script('script-tbr', 'Tableur', array(
            'page' => $_REQUEST['page'],
            'ajax_nonce' => wp_create_nonce('tbr_secure'),
        ));
    }

    /**
     *    Chargement du dictionnaire des applications
     *    Capture du slug_menu si la page correspond à une vue tableur
     */
    public function load_tableurs()
    {
        try {
            $this->tableurs = Tableur::load_tableurs($this->tableurs);
            foreach ($this->tableurs as $app => $tableur):
                foreach ($tableur->get_tables() as $table => $value):
                    foreach ($tableur->get_views($table) as $view => $vvalue):
                        $slug_menu = $app . '-' . $table . '-' . $view;
                        if (!empty($_REQUEST['page']) and $slug_menu == $_REQUEST['page']):
                            $this->slug_menu = $slug_menu;
                        endif;
                    endforeach;
                endforeach;
            endforeach;
        } catch (Exception $e) {
            tbr_alert($e->getMessage(), 'ERROR');
        }
    }

    public static function set_screen($status, $option, $value)
    {
        tbr_journal("Status[$status] Option[$option] Value[$value]", "set_screen");
        return $value;
    }

    public function db_update()
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach ($this->tableurs as $app => $tableur):
            $version_id = "tableur_db_version_{$app}";
            $installed_ver = get_option($version_id);
            tbr_journal($app . ":" . $installed_ver, "dbv");
            if (get_site_option($version_id) != $tableur->get_db_version()):
                foreach ($tableur->get_tables() as $table => $value):
                    $schema = $tableur->get_attribut_table($table, 'schema', '');
                    if (!empty($schema)):
                        $wpdb->query($wpdb->prepare($schema, ''));
                        tbr_journal($wpdb->last_query, 'dbu');
                    endif;
                    $db_updates = $tableur->get_attribut_table($table, 'db_update', '');
                    if (!empty($db_updates)):
                        foreach ($db_updates as $db_update):
                            $wpdb->query($wpdb->prepare($db_update, ''));
                            tbr_journal($wpdb->last_query, 'dbu');
                        endforeach;
                    endif;
                endforeach;
                // Update des tables internes à Tableur
                $db_update = "CREATE TABLE IF NOT EXISTS tbr_categories (name VARCHAR(50) NOT NULL, cat_id INT NOT NULL, link_id INT NOT NULL);";
                $wpdb->query($wpdb->prepare($db_update, ''));
                tbr_journal($wpdb->last_query, 'dbu');
                add_option($version_id, $tableur->get_db_version());
                update_option($version_id, $tableur->get_db_version());
                tbr_journal($app . ':' . get_option($version_id), "dbv update");
            endif;
        endforeach;
    }

    /**
     *    Chargement des menus
     */
    public function plugin_menu()
    {

        foreach ($this->tableurs as $app => $tableur) {
            $debut = true;
            $slug_menu = "";
            foreach ($tableur->get_tables() as $table => $value):
                foreach ($tableur->get_views($table) as $view => $vvalue):
                    if ($debut):
                        $slug_menu = $app . '-' . $table . '-' . $view;
                        add_menu_page($tableur->get_application_title(), $tableur->get_application_title(), 'activate_plugins', $slug_menu, [$this, 'view_handler'], 'dashicons-index-card');
                    endif;
                    $label = $tableur->get_attribut_view($table, $view, 'title');
                    // Menu List pour associer la page à la fonction qui affichera la liste
                    // le slug parent est vide afin de ne pas afficher le lien dans le menu primaire
                    $slug_submenu = $app . '-' . $table . '-' . $view;
                    $hook = add_submenu_page($slug_menu, $label, $label, 'activate_plugins', $slug_submenu, [$this, 'view_handler']);
                    $this->menus[$slug_submenu] = $hook;
                    add_action("load-$hook", array($this, 'menu_add_option'));
                    // Menu Ajout pour associer la page à la fonction qui affichera le formulaire
                    $form_new = $tableur->get_attribut_view($table, $view, 'form_new', '');
                    if (!empty($form_new)):
                        add_submenu_page('', __('New', 'tbr') . ' ' . $label, __('New', 'tbr') . ' ' . $label, 'activate_plugins', $slug_submenu . '-' . $form_new, [ & $this, 'form_handler']);
                    endif;
                    $form_edit = $tableur->get_attribut_view($table, $view, 'form_edit', '');
                    if (!empty($form_edit)):
                        add_submenu_page('', __('Edit', 'tbr') . ' ' . $label, __('Edit', 'tbr') . ' ' . $label, 'activate_plugins', $slug_submenu . '-' . $form_edit, [ & $this, 'form_handler']);
                    endif;
                    $actions = $tableur->get_attribut_view($table, $view, 'actions', false);
                    if (!empty($actions)):
                        foreach ($actions as $action => $form):
                            add_submenu_page('', $action, $action, 'activate_plugins', $slug_submenu . '-' . $form, [ & $this, "{$form}_handler"]);
                        endforeach;
                    endif;
                    $debut = false;
                endforeach;
            endforeach;
            add_submenu_page($slug_menu, __('About', 'tbr'), __('About', 'tbr'), 'activate_plugins', 'tableur_about', 'tableur_about');
        }
    }

    public function menu_add_option()
    {
        $args = array(
            'label' => "Lignes par page",
            'default' => '10',
            'option' => str_replace('-', '_', $this->slug_menu),
        );
        add_screen_option('per_page', $args);
        $param = explode('-', $this->slug_menu);
        $app = $param[0];

        // $tableur est en global et accessible dans l'objet
        global $tableur;
        $tableur = $this->tableurs[$app];
        $tableur->init();
        $this->tableur = $tableur;

        $hook = $this->menus[$this->slug_menu];
        $this->wp_list_table = new Tableur_List_Table($tableur);
    }

    /**
     *    Gestion de la vue
     */
    public function view_handler()
    {
        // tbr_journal($_REQUEST, "view_handler");
        // Chargement du tableur dans le contexte global
        $param = explode('-', $this->slug_menu);
        $app = $param[0];

        $message = '';
        if ('delete' === $this->wp_list_table->current_action()) {
            $message = '<div class="updated below-h2" id="message"><p>' . __('Delete OK', 'tbr') . '</p></div>';
        }
        ?>
        <div class="wrap">
            <?php $this->wp_list_table->view_header()?>
            <?php if (!empty($this->tableur->get_attribut_view_current('form_new'))):
                $page_new = $app . '-' . $this->tableur->get_table() . '-' . $this->tableur->get_view() . '-' . $this->tableur->get_attribut_view_current('form_new');?>
                <h2><a class="add-new-h2"
                    href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=' . $page_new); ?>"><?php _e('New', 'tbr')?> <?php echo $this->tableur->get_attribut_table($this->tableur->get_table(), 'singular') ?></a>
                </h2>
            <?php endif;?>
            <?php echo $message; ?>
            <?php $this->wp_list_table->views()?>
            <form id="form-tableur" method="POST">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/><?php

        if (isset($_POST['s'])) {
            $this->wp_list_table->prepare_items($_REQUEST['s']);
        } else {
            $this->wp_list_table->prepare_items();
        }
        $this->wp_list_table->search_box(__('Search', 'tbr'), 'search_id');
        $this->wp_list_table->display();
        ?>
            </form>
        </div><!-- /wrap -->
        <?php

        $this->wp_list_table->inline_edit();
    }

    /**
     *    Gestion du formulaire d'édition
     */
    public function form_handler()
    {
        $page_current = $_REQUEST['page'];
        $param = explode('-', $page_current);
        $app = $param[0];

        global $tableur;
        $tableur = $this->tableurs[$app];
        $tableur->init();
        $tableur_form = new Tableur_Form($tableur);
        $tableur_form->form_handler();

    }

    /**
     *    Evénement de x-editable
     */
    public function tbr_x_editable()
    {
        // tbr_journal($_REQUEST, "tbr_x_editable");
        // header('HTTP/1.0 400 Bad Request', true, 400);
        // echo "This field is required!";
        // wp_die();
        check_ajax_referer( 'tbr_secure', 'security' );
        try {
            $page_current = $_REQUEST['page'];
            $param = explode('-', $page_current);
            $app = $param[0];

            global $tableur;
            $tableur = $this->tableurs[$app];
            $tableur->init();

            // Mise à jour de la colonne
            $errors = "";
            $tableur->update_column($tableur->get_table(), $_REQUEST['pk'], $_REQUEST['name'], stripslashes_deep($_REQUEST['value']));
            if ($tableur->is_with_error()) {
                foreach ($tableur->get_errors() as $error) {
                    $errors .= '[' . $error . '] ';
                }
                wp_send_json_error($errors);
            } else { 
                wp_send_json_success($_REQUEST);
            }
        } catch (Exception $e) {
            tbr_alert($e->getMessage(), 'ERROR');
            wp_send_json_error($e->getMessage());
        }
        wp_die();
    }
    public function tbr_get_items()
    {
        // tbr_journal($_REQUEST, "tbr_get_items");
        // header('HTTP/1.0 400 Bad Request', true, 400);
        // echo "This field is required!";
        // wp_die();
        check_ajax_referer( 'tbr_secure', 'security' );
        try {
            $page_current = $_REQUEST['page'];
            $param = explode('-', $page_current);
            $app = $param[0];

            global $tableur;
            $tableur = $this->tableurs[$app];
            $tableur->init();
            // Récupération de la rubrique concernée
            $rub = $tableur->get_rubrique($_REQUEST['name']);
            $items = $rub->get_items($_REQUEST['name']);
            // tbr_journal($items, "tbr_get_items items");
            // wp_send_json_success(json_encode($items));
            echo json_encode($items);

        } catch (Exception $e) {
            tbr_alert($e->getMessage(), 'ERROR');
            wp_send_json_error($e->getMessage());
        }
        wp_die();
    }

    /** Singleton instance */
    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

}

if (is_admin()):

    if (!class_exists('WP_List_Table')) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
    }

    require_once dirname(__FILE__) . '/helper.php';
    require_once dirname(__FILE__) . '/tableur.php';
    require_once dirname(__FILE__) . '/tableur-element.php';
    require_once dirname(__FILE__) . '/tableur-widget.php';
    require_once dirname(__FILE__) . '/tableur-view.php';
    require_once dirname(__FILE__) . '/tableur-form.php';

    global $tableur;

    add_action('plugins_loaded', function () {
        Tableur_Plugin::get_instance();
    });

endif;

/**
 *    Fonctions communes au projet
 */
if (!function_exists('tbr_journal')) {
    function tbr_journal($message, $type = "info")
    {
        if (strpos(home_url(), 'localhost') > 0):
            if (is_array($message) or is_object($message)) {
                // $message = json_encode($message);
                $message = print_r($message, true);
            }
            $file = fopen(dirname(__FILE__) . "/log/tableur.log", "a");
            fwrite($file, "\n" . date('Y-m-d h:i:s') . " :: " . $type . " " . $message);
            if ($type == "dbr" or $type == "dbu") {
                global $wpdb;
                if ($wpdb->last_error != '') {
                    fwrite($file, "\n" . date('Y-m-d h:i:s') . " :: " . 'ERROR' . " " . $wpdb->last_error);
                }
            }
            fclose($file);
        endif;
    }
}
if (!function_exists('tbr_alert')) {
    function tbr_alert($message)
    {

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                echo '<div id="notice" class="error below-h2"><p>' . $message . '</p></div>';
                tbr_journal($message, 'ERROR');
                break;
            case JSON_ERROR_DEPTH:
                echo '<div id="notice" class="error below-h2"><p>' . __('JSON_ERROR_DEPTH', 'tbr') . '</p></div>';
                tbr_journal(__('JSON_ERROR_DEPTH', 'tbr'), 'ERROR');
                break;
            case JSON_ERROR_STATE_MISMATCH:
                echo '<div id="notice" class="error below-h2"><p>' . __('JSON_ERROR_STATE_MISMATCH', 'tbr') . '</p></div>';
                tbr_journal(__('JSON_ERROR_STATE_MISMATCH', 'tbr'), 'ERROR');
                break;
            case JSON_ERROR_CTRL_CHAR:
                echo '<div id="notice" class="error below-h2"><p>' . __('JSON_ERROR_CTRL_CHAR', 'tbr') . '</p></div>';
                tbr_journal(__('JSON_ERROR_CTRL_CHAR', 'tbr'), 'ERROR');
                break;
            case JSON_ERROR_SYNTAX:
                echo '<div id="notice" class="error below-h2"><p>' . __('JSON_ERROR_DEPTH', 'tbr') . '</p></div>';
                tbr_journal(__('JSON_ERROR_SYNTAX', 'tbr'), 'ERROR');
                break;
            case JSON_ERROR_UTF8:
                echo '<div id="notice" class="error below-h2"><p>' . __('JSON_ERROR_DEPTH', 'tbr') . '</p></div>';
                tbr_journal(__('JSON_ERROR_UTF8', 'tbr'), 'ERROR');
                break;
            default:
                echo '<div id="notice" class="error below-h2"><p>' . __('JSON_ERROR_DEPTH', 'tbr') . '</p></div>';
                tbr_journal(__('JSON_ERROR_UNKNOW', 'tbr'), 'ERROR');
                break;
        }
    }
}

if (!function_exists('tbr_callers')) {
    function tbr_callers()
    {
        $callers = debug_backtrace();
        foreach ($callers as $caller) {
            if (isset($caller['class'])) {
                tbr_journal($caller['function'] . '()' . ' in ' . $caller['class']);
            }
            if (isset($caller['object'])) {
                tbr_journal($caller['function'] . '()' . ' (' . get_class($caller['object']) . ')');
            }
        }
    }
}