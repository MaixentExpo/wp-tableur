<?php

/**
 * Classes des éléments manipulés dans le dictionnaire
 *
 */
// Prohibit direct script loading.
defined('ABSPATH') || die('No direct script access allowed!');

class Tableur_Element
{
    protected $tableur;
    protected $key;
    protected $value;
    protected $id;
    protected $table;
    public function __construct($key, &$tableur)
    {
        $this->key = $key;
        $this->tableur = $tableur;
    }

    public static function new_instance(&$tableur, $key, $type) {
        $rub = array();
        switch ($type) {
            case 'category':
                $rub = new Tableur_Category($key, $tableur);
                break;
            case 'checkbox':
                $rub = new Tableur_Checkbox($key, $tableur);
                break;
            case 'decimal':
                $rub = new Tableur_Decimal($key, $tableur);
                break;
            case 'email':
                $rub = new Tableur_Email($key, $tableur);
                break;
            case 'html':
                $rub = new Tableur_Html($key, $tableur);
                break;
            case 'number':
                $rub = new Tableur_Number($key, $tableur);
                break;
            case 'foreign_key':
                $rub = new Tableur_Foreign_Key($key, $tableur);
                break;
            case 'child_key':
                $rub = new Tableur_Child_Key($key, $tableur);
                break;
            case 'mail':
                $rub = new Tableur_Mail($key, $tableur);
                break;
            case 'note':
                $rub = new Tableur_Note($key, $tableur);
                break;
            case 'text':
                $rub = new Tableur_Text($key, $tableur);
                break;
            case 'textarea':
                $rub = new Tableur_Textarea($key, $tableur);
                break;
            case 'radio':
                $rub = new Tableur_Radio($key, $tableur);
                break;
            case 'select':
                $rub = new Tableur_Select($key, $tableur);
                break;
        }
        
        return $rub;
    }

    /**
     * Type of fields
     */
    public function is_type_category()
    {
        return $this->get_type() == 'category' ? true : false;
    }
    public function is_type_checkbox()
    {
        return $this->get_type() == 'checkbox' ? true : false;
    }
    public function is_type_decimal()
    {
        return $this->get_type() == 'decimal' ? true : false;
    }
    public function is_type_email()
    {
        return $this->get_type() == 'email' ? true : false;
    }
    public function is_type_foreign_key()
    {
        return $this->get_type() == 'foreign_key' ? true : false;
    }
    public function is_type_int()
    {
        return $this->get_type() == 'int' ? true : false;
    }
    public function is_type_child_key()
    {
        return $this->get_type() == 'child_key' ? true : false;
    }
    public function is_type_mail()
    {
        return $this->get_type() == 'mail' ? true : false;
    }
    public function is_type_number()
    {
        return $this->get_type() == 'number' ? true : false;
    }
    public function is_type_note()
    {
        return $this->get_type() == 'note' ? true : false;
    }
    public function is_type_textarea()
    {
        return $this->get_type() == 'textarea' ? true : false;
    }
    public function is_type_text()
    {
        return $this->get_type() == 'text' ? true : false;
    }
    public function is_type_radio()
    {
        return $this->get_type() == 'radio' ? true : false;
    }
    public function is_type_select()
    {
        return $this->get_type() == 'select' ? true : false;
    }

    /**
     * IS
     */
    public function is_editable()
    {
        return $this->tableur->get_attribut_element($this->key, 'editable', false);
    }
    public function is_temporary() {
        return strpos($this->key, '_') === 0 ? true : false;
    }
    public function is_sortable()
    {
        return $this->tableur->get_attribut_element($this->key, 'sortable', false);
    }
    public function is_hidden()
    {
        return $this->tableur->get_attribut_element($this->key, 'hide', false);
    }
    public function is_next_column()
    {
        return $this->tableur->get_attribut_element($this->key, 'next_column', false);
    }
    public function is_readonly()
    {
        return $this->tableur->get_attribut_element($this->key, 'readonly', false);
    }
    public function is_refresh()
    {
        return $this->tableur->get_attribut_element($this->key, 'refresh', false);
    }
    public function is_required()
    {
        return $this->tableur->get_attribut_element($this->key, 'required', false);
    }
    public function is_protected()
    {
        return $this->tableur->get_attribut_element($this->key, 'protected', false);
    }

    /**
     * GET
     */
    public function get_col_style()
    {
        return $this->tableur->get_attribut_element($this->key, 'col_style', false);
    }
    public function get_column()
    {
        return $this->tableur->get_attribut_element($this->key, 'column', false);
    }
    public function get_default()
    {
        return $this->tableur->get_attribut_element($this->key, 'default');
    }
    public function get_id()
    {
        return $this->id;
    }
    public function get_items($key)
    {
        return $this->tableur->get_attribut_element($key, 'items');
    }
    public function get_label_column()
    {
        return $this->tableur->get_attribut_element($this->key, 'label_column', '');
    }
    public function get_label_field()
    {
        return $this->tableur->get_attribut_element($this->key, 'label_field', __('!!! No label', 'tbr'));
    }
    public function get_mask()
    {
        return $this->tableur->get_attribut_element($this->key, 'mask', '');
    }
    public function get_maxlength()
    {
        return $this->tableur->get_attribut_element($this->key, 'maxlength', false);
    }
    public function get_minlength()
    {
        return $this->tableur->get_attribut_element($this->key, 'minlength', false);
    }
    public function get_min()
    {
        return $this->tableur->get_attribut_element($this->key, 'min', false);
    }
    public function get_max()
    {
        return $this->tableur->get_attribut_element($this->key, 'max', false);
    }
    public function get_pattern()
    {
        return $this->tableur->get_attribut_element($this->key, 'pattern', '');
    }
    public function get_placeholder()
    {
        return $this->tableur->get_attribut_element($this->key, 'placeholder', '');
    }
    public function get_size()
    {
        return $this->tableur->get_attribut_element($this->key, 'size', '');
    }
    public function get_table()
    {
        return $this->table;
    }
    public function get_type()
    {
        return $this->tableur->get_attribut_element($this->key, 'type');
    }
    public function get_value($item)
    {
        if (empty($this->value)) {
            return Helper::macro(Helper::macro($this->get_default(), $item), $item);
        }
        return $this->value;
    }
    public function get_width()
    {
        return $this->tableur->get_attribut_element($this->key, 'width', false);
    }
    public function get_option($option, $default=false)
    {   $options = $this->tableur->get_attribut_element($this->key, 'options', false);
        if ( !empty($options) and isset($options[$option])) {
            return $options[$option];
        }
        return $default;
    }
    public function get_option_table()
    {
        return $this->tableur->get_attribut_element($this->key, 'table', false);
    }

    /**
     * VUE
     */
    public function get_search($search)
    {
        return "{$this->table}.{$this->key} like '%{$search}%'";
    }
    public function get_display($item, $key)
    {
        if ( $this->is_editable() ) :
            return $this->get_display_editable($item, $key);
        else:
            return $this->get_display_simple($item, $key);
        endif;
    }
    public function get_display_simple($item, $key)
    {
        return esc_attr($item[$key]);
    }
    public function get_display_editable($item, $key)
    {
        $class = "editable tbr-editable";        
        $html = '<a data-name="' . $key . '"';
        $html .= ' class="'.$class.'"';
        $html .= ' data-type="text"';
        $html .= ' data-pk="' . $item['id'] . '"';
        $html .= ' data-title="' . $this->get_label_column() . '"';
        $html .= $this->is_refresh() ? ' data-refresh="true"' : '';
        $html .= ' href="#"';
        if (!empty($this->get_pattern()))
            $html .= ' data-tpl="<input type=\'text\' pattern=\''.$this->get_pattern().'\'>"';
        $html .= '>' . $item[$key] . '</a>';
        return $html;
    }
    /**
     * Retourne la partie select sql de la colonne
     */
    public function get_column_select_sql() {
        return $this->tableur->get_table(). '.' . $this->key;
    }
    /**
     * Retourne éventuellement la jointure avec une autre table 
     */
    public function get_column_join_sql() {
        return "";
    }
    public function get_orderby_sql() {
        return $this->tableur->get_table() . "." . $this->key;
    }

    /**
     * SET
     */
    public function set_value($content)
    {
        $this->value = $content;
        return $this;
    }
    public function set_id($value)
    {
        $this->id = $value;
        return $this;
    }
    public function set_table($value)
    {
        $this->table = $value;
        return $this;
    }

    /**
     * FORMULAIRE
     */
    public function display_field($item, $key)
    {
        ?>
        <p>
        <?php $this->display_label($item, $key)?>
        <br/>
        <?php $this->display_control($item, $key)?>
        </p>
        <?php

    }
    public function display_label($item, $key)
    {
        ?>
        <label for="<?php echo $this->key ?>">
        <?php echo ($this->is_required() ? '*' : '') ?>
        <?php echo $this->get_label_field() ?>
        </label>
        <?php

    }
    public function display_control($item, $key)
    {
        if ($this->is_protected()) :
        ?>
        <input id="<?php echo $this->key ?>" name="<?php echo $this->key ?>" type="hidden"
            value="<?php echo esc_attr($this->get_value($item)) ?>">
        <input type="text"
            value="<?php echo esc_attr($this->get_value($item)) ?>"
            disabled="disabled">
        <?php
        else:
            ?>
            <input id="<?php echo $this->key ?>" name="<?php echo $this->key ?>" type="text"
                value="<?php echo esc_attr($this->get_value($item)) ?>"
                <?php echo ($this->is_required() ? ' required' : '') ?>
                <?php if (!empty($this->get_pattern())) {
                echo ' pattern="' . $this->get_pattern() . '"';
            }
            ?>
                <?php if (!empty($this->get_placeholder())) {
                echo ' placeholder="' . $this->get_placeholder() . '"';
            }
            ?>
                <?php if (!empty($this->get_size())) {
                echo ' size="' . $this->get_size() . '"';
            }
            ?>
                <?php if (!empty($this->get_minlength())) {
                echo ' minlength="' . $this->get_minlength() . '"';
            }
            ?>
                <?php if (!empty($this->get_maxlength())) {
                echo ' maxlength="' . $this->get_maxlength() . '"';
            }
            ?>
            >
            <?php    
        endif;

    }

    /**
     * Ajout bouton d'actions en bas du formulaire
     */
    public function add_action($item, $key)
    {

    }

    /**
     * Exécution du traitement associé au bouton
     */
    public function do_action(&$item, $key)
    {

    }


    /**
     * Pour traiter $_REQUEST éclaté en key-n
     */
    public function update_request()
    {

    }
    /**
     * Post insert update
     * pour mettre à jour les tables liées
     */
    public function post_update($item, $key)
    {

    }

    /**
     * VUE
     */

}
