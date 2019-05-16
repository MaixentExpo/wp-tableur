<?php
/**
 * Accesseur au dictionnaire tableur
 * Dictionnaire des Tables
 * décomposé en :
 * - table
 * - view (tableau des colonnes de la table)
 * - form (formulaire de saisie d'un enregistrement)
 * - élément (colonne de la tables, champs de formulaire)
 *
 * Avertissements :
 * - les tables devront comportées une colonne "id" et une colonne "name"
 * - les clés (nom des tables, des views, des forms et des éléments) ne devront pas comporter de tiret
 *
 */

// Prohibit direct script loading.
defined('ABSPATH') || die('No direct script access allowed!');

class Tableur
{
    private $app;
    private $page;
    private $table;
    private $view;
    private $form;
    private $json;
    private $rubriques = array();
    private $messages = array(); // message à afficher
    private $errors = array(); // message d'erreur à afficher

    public function __construct($json_file_path)
    {
        try {
            $jsondata = file_get_contents($json_file_path);
            $this->json = json_decode($jsondata, true);
            if ( json_last_error() ) {
                tbr_alert(__('Json error', 'tbr'), 'ERROR');    
            }
        } catch(Exception $e) {
            tbr_alert($e->getMessage(), 'ERROR');
        }

    }

    public static function load_tableurs($tableurs) {
        // $json_path="https://raw.githubusercontent.com/pbillerot/memodoc/gh-pages/dico.json";
        // chargement des dictionnaires du répertoire dico
        try {
            $tableur_directory = dirname(__FILE__) . '/dico/enabled/';
            if ($handle = opendir($tableur_directory)) {
                while (false !== ($entry = readdir($handle))) {
                    if ($entry != "." && $entry != "..") {
                        $ext = pathinfo($entry, PATHINFO_EXTENSION);
                        $filename = pathinfo($entry, PATHINFO_FILENAME);
                        if ( $ext == "json") {
                            $json_path = dirname(__FILE__) . '/dico/enabled/' . $entry;
                            $tableur = new Tableur($json_path);
                            if ( ! json_last_error() ) {
                                $tableurs[$filename] = $tableur;
                            }
                        }
                    }
                }
                closedir($handle);
            }
        } catch(Exception $e) {
            tbr_alert($e->getMessage(), 'ERROR');
        }
        return $tableurs;
    }

    public function init()
    {
        $this->page = $_REQUEST['page'];
        // tbr_journal($this->page, "tableur.init()");

        // ?page=app-table-view-form
        $param = explode('-', $this->page);

        if (isset($param[0])) {
            $this->app = $param[0];
        }

        if (isset($param[1])) {
            $this->table = $param[1];
        }

        if (isset($param[2])) {
            $this->view = $param[2];
        }

        if (isset($param[3])) {
            $this->form = $param[3];
        }

        // fusion des attributs de colonnes ou de champs dans les elements
        $this->merge_attributs();

        // Remplissage du tableau des rubriques instanciées en fonction du type
        $this->add_rubriques();       

    }
    
    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function is_view()
    {
        return empty($this->form) ? true : false;
    }
    public function is_form()
    {
        return empty($this->form) ? false : true;
    }

    /**
     * Chargement des éléments dans le tableau rubriques
     */
    public function add_rubriques()
    {
        foreach ($this->get_elements() as $key => $element):
            $rub = Tableur_Element::new_instance($this, $key, $element['type']);
            $rub->set_table($this->table);
            $this->rubriques[$key] = $rub;
        endforeach;
    }
    public function get_rubriques()
    {
        return $this->rubriques;
    }
    public function get_rubrique($key)
    {
        return $this->rubriques[$key];
    }

    public function get_app()
    {
        return $this->app;
    }
    public function get_application_title()
    {
        return $this->json['application_title'];
    }
    public function get_application_description()
    {
        return $this->json['application_description'];
    }
    public function get_db_version()
    {
        return $this->json['db_version'];
    }
    public function get_tables()
    {
        return $this->json['tables'];
    }
    public function get_attribut_table($table, $attribut)
    {
        return $this->json['tables'][$table][$attribut];
    }
    public function get_table()
    {
        return $this->table;
    }

    public function get_views($table)
    {
        return $this->json['tables'][$table]['views'];
    }
    public function get_attribut_view_current($attribut)
    {
        return $this->json['tables'][$this->table]['views'][$this->view][$attribut];
    }
    public function get_attribut_view($table, $view, $attribut)
    {
        return $this->json['tables'][$table]['views'][$view][$attribut];
    }
    public function get_view()
    {
        return $this->view;
    }

    public function get_forms($table)
    {
        return $this->json['tables'][$table]['forms'];
    }
    public function get_attribut_form($table, $form, $attribut)
    {
        return $this->json['tables'][$table]['forms'][$form][$attribut];
    }
    public function get_attribut_form_current($attribut, $default = false)
    {
        if ( isset($this->json['tables'][$this->table]['forms'][$this->form][$attribut])) {
            return $this->json['tables'][$this->table]['forms'][$this->form][$attribut];
        } else {
            return $default;
        }
    }
    public function get_form()
    {
        return $this->form;
    }

    public function get_elements()
    {
        $elements = array();
        if ($this->is_view()) {
            foreach ($this->json['tables'][$this->table]['views'][$this->view]['elements'] as $key => $val) {
                $elements[$key] = $this->json['tables'][$this->table]['elements'][$key];
            }
        } else {
            foreach ($this->json['tables'][$this->table]['forms'][$this->form]['elements'] as $key => $val) {
                $elements[$key] = $this->json['tables'][$this->table]['elements'][$key];
            }
        }
        return $elements;
    }

    public function get_elements_view_current()
    {
        return $this->json['tables'][$this->table]['views'][$this->view]['elements'];
    }
    public function get_elements_view($table, $view)
    {
        return $this->json['tables'][$table]['views'][$view]['elements'];
    }
    public function get_elements_form_current()
    {
        return $this->json['tables'][$this->table]['forms'][$this->form]['elements'];
    }
    public function get_attribut_element($element, $attribut, $default = false)
    {
        return $this->json['tables'][$this->table]['elements'][$element][$attribut];
    }

    public function merge_attributs() {
        if ( $this->is_view() ) {
            foreach($this->get_elements_view_current() as $key => $attributs ) {
                foreach($attributs as $katt => $value) {
                    $this->json['tables'][$this->table]['elements'][$key][$katt] = $value;
                }             
            }
        } else {
            foreach($this->get_elements_form_current() as $key => $attributs ) {
                foreach($attributs as $katt => $value) {
                    $this->json['tables'][$this->table]['elements'][$key][$katt] = $value;
                }             
            }
        }
    }

    public function add_message($message) {
        if ( empty($message)) return;
        $this->messages[] = $message;
        tbr_journal($message, 'GUI');
    }
    public function get_messages() {
        return $this->messages;
    }
    public function add_error($message) {
        if ( empty($message)) return;
        $this->errors[] = $message;
        tbr_journal($message, 'ERROR');
    }
    public function get_errors() {
        return $this->errors;
    }
    public function is_with_error() {
        return count($this->errors) > 0 ? true: false;
    }
    
    public function update_column($table, $id, $column, $value)
    {
        $item = array();
        $item['id'] = $id;
        $item[$column] = $value;

        global $wpdb;
        $result = $wpdb->update($table, $item, array('id' => $item['id']));
        tbr_journal($wpdb->last_query, "dbu");
        if ($result == false) {
            $this->add_error(__('There was an error while updating item', 'tbr'));
            $this->add_error($wpdb->last_error);
            $this->add_error($wpdb->last_query);
        }
    }
}
