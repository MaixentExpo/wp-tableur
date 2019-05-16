<?php
/**
 * WIDGET des composants des vues et formulaires
 * 
 */
// Prohibit direct script loading.
defined('ABSPATH') || die('No direct script access allowed!');

class Tableur_Category extends Tableur_Element
{
    /**
     * VUE
     */
    // On charge les items de la colonne une seule fois
    protected $items;
    public function get_items($key) {
        global $wpdb;
        if (empty($this->items) ) {
            $query = "select {$this->get_option_table()}.id, {$this->get_option_table()}.name from {$this->get_option_table()} order by {$this->get_option_table()}.name";
            $array = $wpdb->get_results($wpdb->prepare($query, ''), ARRAY_A);
            tbr_journal($wpdb->last_query, 'dbr');
            $this->items = array();
            foreach ($array as $arr) {
                $this->items[array_values($arr)[0]] = array_values($arr)[1];
            }
        }
        return $this->items;
    }

    public function get_display_simple($item, $key)
    {
        $val = "";
        $cats = explode(',', $item[$key]);
        foreach ($this->get_items($key) as $element => $valeur) {
            $html = '<button disabled style="margin-bottom: 3px;">'.$valeur.'</button>';
            if (in_array($element, $cats)) {
                $val .= empty($val) ? $html : ' ' . $html;
            }
        }
        return ''.$val.'';
    }

    public function get_search($search)
    {
        $where = "";
        $where .= "{$this->table}.id in
        (
            select tbr_categories.link_id
            from tbr_categories
            where tbr_categories.name = '{$this->table}.{$this->key}'
            and tbr_categories.cat_id in
            (
                select {$this->get_option_table()}.id
                from {$this->get_option_table()}
                where {$this->get_option_table()}.name like '%{$search}%'
            )
        )";
        return $where;
    }

    /**
     * FORMULAIRE
     */
    public function display_control($item, $key)
    {
        global $wpdb;
        $query = "select id, name from {$this->get_option_table()} order by name";
        $array = $wpdb->get_results($wpdb->prepare($query, ''), ARRAY_A);
        tbr_journal($wpdb->last_query, 'dbr');
        $elements = array();
        // [{"id":"1","name":"Bovins"},{"id":"2","name":"Tombola"}]
        foreach ($array as $arr):
            $elements[array_values($arr)[0]] = array_values($arr)[1];
        endforeach;
        $i = 1;
        $cats = explode(',', $this->get_value($item));
        foreach ($elements as $element => $value):
        ?>
        <span style="white-space: nowrap;"><input type="checkbox" id="<?php echo $this->key . '-' . $i ?>" name="<?php echo $this->key . '-' . $i ?>" value="<?php echo $element ?>"<?php echo in_array($element, $cats) ? ' checked' : '' ?>><label class="checkbox-title" for="<?php echo $this->key . '-' . $i ?>"><?php echo $value ?>&nbsp;</label></span>
        <?php $i++;
        endforeach;
    }
    public function update_request()
    {
        global $wpdb;
        $total_items = $wpdb->get_var("SELECT MAX(id) FROM {$this->get_option_table()}");
        tbr_journal($wpdb->last_query, 'dbr');
        $val = "";
        for ($i = 0; $i <= $total_items; $i++):
            if (!empty($_REQUEST[$this->key . '-' . $i])):
                $val .= empty($val)
                ? $_REQUEST[$this->key . '-' . $i]
                : ',' . $_REQUEST[$this->key . '-' . $i];
            endif;
        endfor;
        $_REQUEST[$this->key] = $val;
    }
    public function post_update($item, $key)
    {
        global $wpdb;
        // Mise à jour de la table de jointure tbr_categories
        $result = $wpdb->query("DELETE FROM tbr_categories WHERE link_id = {$this->id} and name = '{$this->table}.{$this->key}'");
        tbr_journal($wpdb->last_query, 'dbu');
        if ($result === false) {
            return;
        }
        if ( !empty($this->get_value($item))) :
            $vals = explode(',', $this->get_value($item));
            foreach ($vals as $val) {
                $result = $wpdb->query("INSERT INTO tbr_categories (name,link_id,cat_id) VALUES ('{$this->table}.{$this->key}','{$this->id}','{$val}')");
                tbr_journal($wpdb->last_query, 'dbu');
            }
        endif;

        // mise à jour de toutes les colonnes child
        $column = $this->get_column();
        if ( !empty($column) ) {
            // liste des catégories
            $query = "SELECT distinct cat_id FROM tbr_categories WHERE name = '{$this->table}.{$this->key}'";
            $cats = $wpdb->get_results($wpdb->prepare($query, ''), ARRAY_A);
            tbr_journal($wpdb->last_query, 'dbr');
            // nettoyage de la colonne
            $result = $wpdb->query("UPDATE {$this->get_option_table()} SET {$this->get_column()} = null");
            tbr_journal($wpdb->last_query, 'dbu');
            // boucle d'update sur la table des catégories
            foreach ($cats as $cat):
                // liste des link_id
                $query = "SELECT link_id as 'id' FROM tbr_categories WHERE cat_id = {$cat['cat_id']} and name = '{$this->table}.{$this->key}'";
                $array = $wpdb->get_results($wpdb->prepare($query, ''), ARRAY_A);
                tbr_journal($wpdb->last_query, 'dbr');
                if ($array === false) {
                    return;
                }
                $vals = '';
                foreach ($array as $arr):
                    if (!empty($vals)) $vals .= ',';
                    $vals .= $arr['id'];
                endforeach;
                $result = $wpdb->query("UPDATE {$this->get_option_table()} SET {$this->get_column()} = '{$vals}' WHERE id = {$cat['cat_id']}");
                tbr_journal($wpdb->last_query, 'dbu');
            endforeach;

        }
    }


}
class Tableur_Checkbox extends Tableur_Element
{
    /**
     * VUE
     */

    public function get_display_simple($item, $key)
    {
        if ($item[$key] == '1'):
            return '<span class="dashicons dashicons-yes"></span>';
        endif;
        return '';
    }
    public function get_display_editable($item, $key)
    {
        $refresh = $this->is_refresh() ? ' data-refresh="true"' : ''; 
        ?>
        <input class="tbr-editable-checkbox" type="checkbox" data-id="<?php echo $item['id'] ?>" name="<?php echo $this->key ?>" value="1" <?php echo ($item[$key] == "1" ? 'checked' : '') ?> <?php echo $refresh; ?>/>
        <?php
    }

    /**
     * FORMULAIRE
     */
    public function display_field($item, $key)
    {
        ?>
        <p>
        <?php $this->display_control($item, $key)?>
        </p>
        <?php
    }
    
    public function display_control($item, $key)
    {
        ?>
        <input type="checkbox" id="<?php echo $this->key ?>" name="<?php echo $this->key ?>"
               value="1" <?php echo ($this->get_value($item) == "1" ? 'checked' : '') ?> />
        <label for="<?php echo $this->key ?>"><?php echo $this->get_label_field() ?></label>
        <?php
    }

}

/**
 * Le pendant d'une rubrique de type "category" ou "foreign_key"
 * du coté de la table fille (child)
 * child_key contiendra les id des enregistrements des tables parentes
 */
class Tableur_Child_Key extends Tableur_Element
{
    /**
     * VUE
     */
    // On charge les items de la colonne une seule fois
    protected $items;
    public function get_items($key) {
        global $wpdb;
        if (empty($this->items) ) {
            $query = "select id, name from {$this->get_option_table()} order by {$this->get_option_table()}.name";
            $array = $wpdb->get_results($wpdb->prepare($query, ''), ARRAY_A);
            tbr_journal($wpdb->last_query, 'dbr');
            $this->items = array();
            foreach ($array as $arr) {
                $this->items[array_values($arr)[0]] = array_values($arr)[1];
            }
        }
        return $this->items;
    }

    public function get_display_simple($item, $key)
    {
        $val = "";
        $cats = explode(',', $item[$key]);
        foreach ($this->get_items($key) as $element => $valeur) {
            $html = '<a class="button action" style="margin-bottom: 3px;" href="'.get_admin_url($element, 'admin.php?page=' . $this->tableur->get_app() . '-' . $this->get_option_table()).'-vall-fedit&id=' . $element . '&pageback=' . $_REQUEST['page'] . '">'.$valeur.'</a>';
            if (in_array($element, $cats)) {
                $val .= empty($val) ? $html : ' ' . $html;
            }
        }
        return ''.$val.'';
    }

    public function get_search($search)
    {
        $where = "";
        $where .= "{$this->table}.{$this->key} in
        (
            select {$this->get_option_table()}.id
            from {$this->get_option_table()}
            where {$this->get_option_table()}.name like '%{$search}%'
        )";
        return $where;
    }

    /**
     * FORMULAIRE
     */
    public function is_readonly()
    {
        return true;
    }

    public function display_control($item, $key)
    {
        global $wpdb;
        $query = "select id, name from {$this->get_option_table()} order by name";
        $array = $wpdb->get_results($wpdb->prepare($query, ''), ARRAY_A);
        tbr_journal($wpdb->last_query, 'dbr');
        $elements = array();
        foreach ($array as $arr):
            $elements[array_values($arr)[0]] = array_values($arr)[1];
        endforeach;
        $i = 1;
        $cats = explode(',', $this->get_value($item));
        foreach ($elements as $element => $value):
            if ( in_array($element, $cats)): ?>
                <button class="button action" style="margin-bottom: 3px;" disabled><?php echo $value ?></button>
            <?php
            
            endif;
        endforeach;
    }
}

class Tableur_Decimal extends Tableur_Element
{
    /**
     * VUE
     */
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
     * FORMULAIRE
     */
    public function display_control($item, $key)
    {
        ?>
        <input id="<?php echo $this->key ?>" name="<?php echo $this->key ?>" type="text"
            value="<?php echo esc_attr($this->get_value($item)) ?>"
            <?php 
        echo ($this->is_required() ? ' required' : '');
        if (!empty($this->get_pattern())) {
            echo ' pattern="' . $this->get_pattern() . '"';
        }
        if (!empty($this->get_placeholder())) {
            echo ' placeholder="' . $this->get_placeholder() . '"';
        }
        if ( $this->is_protected() ) {
            echo ' disabled="disabled"';
        }
        if (!empty($this->get_size())) {
            echo ' size="' . $this->get_size() . '"';
        }
        if (!empty($this->get_min())) {
            echo ' min="' . $this->get_min() . '"';
        }
        if (!empty($this->get_max())) {
            echo ' max="' . $this->get_max() . '"';
        }
        ?>
        >
        <?php

    }

}

class Tableur_Email extends Tableur_Element
{
    /**
     * VUE
     */

    public function get_display_simple($item, $key)
    {
        return '<span>'.esc_attr($item[$key]).'</span>';
    }
    public function get_display_editable($item, $key)
    {
        $class = "editable tbr-editable";
        $html = '<a data-name="' . $key . '"';
        $html .= ' class="'.$class.'"';
        $html .= ' data-type="email"';
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
     * FORMULAIRE
     */
    public function display_control($item, $key)
    {
        ?>
        <input id="<?php echo $this->key ?>" name="<?php echo $this->key ?>" type="email"
            value="<?php echo esc_attr($this->get_value($item)) ?>"
            <?php echo ($this->is_required() ? ' required' : '') ?>
        >
        <?php
    }

}

class Tableur_Foreign_Key extends Tableur_Element
{
    /**
     * VUE
     */

     // On charge les items de la colonne une seule fois
    protected $items;
    public function get_items($key) {
        global $wpdb;
        if (empty($this->items) ) {
            $query = "select {$this->get_option_table()}.id, {$this->get_option_table()}.name from {$this->get_option_table()} order by {$this->get_option_table()}.name";
            $array = $wpdb->get_results($wpdb->prepare($query, ''), ARRAY_A);
            tbr_journal($wpdb->last_query, 'dbr');
            $this->items = array();
            foreach ($array as $arr) {
                $this->items[array_values($arr)[0]] = array_values($arr)[1];
            }
        }
        return $this->items;
    }

    public function get_display_simple($item, $key)
    {
     $val = "";
        $cats = explode(',', $item[$key]);
        foreach ($this->get_items($key) as $element => $valeur) {
            $html = '<a class="button action" style="margin-bottom: 3px;" href="'.get_admin_url($element, 'admin.php?page=' . $this->tableur->get_app() . '-' . $this->get_option_table()).'-vall-fedit&id=' . $element . '&pageback=' . $_REQUEST['page'] . '">'.$valeur.'</a>';
            if (in_array($element, $cats)) {
                $val .= empty($val) ? $html : ' ' . $html;
            }
        }
        return ''.$val.'';
    }

    public function get_display_editable($item, $key)
    {
        $val = "";
        $cats = explode(',', $item[$key]);
        foreach ($this->get_items($key) as $element => $valeur) {
            if (in_array($element, $cats)) {
                $val .= empty($val) ? $valeur : ', ' . $valeur;
            }
        }
        $sources = '{'.$sources.'}';
        $class = "editable tbr-editable-items";
        $html = '<a data-name="' . $key . '"';
        $html .= ' class="'.$class.'"';
        $html .= ' data-type="select"';
        $html .= ' data-pk="' . $item['id'] . '"';
        $html .= ' data-value="' . $item[$key] . '"';
        $html .= ' data-title="' . $this->get_label_column() . '"';
        $html .= $this->is_refresh() ? ' data-refresh="true"' : '';
        $html .= " data-source='/wp-admin/admin-ajax.php?name=" . $key . "'";
        $html .= ' href="#"';
        $html .= '>' . $val . '</a>';
        return $html;
    }

    public function get_search($search)
    {
        $where = "";
        $where .= "{$this->table}.{$this->key} in
        (
            select {$this->get_option_table()}.id
            from {$this->get_option_table()}
            where {$this->get_option_table()}.name like '%{$search}%'
        )";
        return $where;
    }

    public function xget_column_select_sql() {
        return $this->get_option_table() . '.name as ' . $this->key;
    }
    public function get_column_join_sql() {
        return " LEFT OUTER JOIN " . $this->get_option_table() . " ON " . $this->get_option_table() . ".id = " . $this->tableur->get_table() . "." . $this->key;
    }
    public function get_orderby_sql() {
        return $this->get_option_table() . '.name';
    }

    /**
     * FORMULAIRE
     */
    public function display_control($item, $key)
    {
        global $wpdb;
        $query = "select id, name from {$this->get_option_table()} order by name";
        $array = $wpdb->get_results($wpdb->prepare($query, ''), ARRAY_A);
        tbr_journal($wpdb->last_query, 'dbr');
        $elements = array();
        foreach ($array as $arr):
            $elements[array_values($arr)[0]] = array_values($arr)[1];
        endforeach;
        $i = 1;
        $cats = explode(',', $this->get_value($item));
        foreach ($elements as $element => $value):
        ?>
        <span style="white-space: nowrap;">
            <input type="radio" id="<?php echo $this->key.'-'.$element ?>" name="<?php echo $this->key ?>"
            value="<?php echo $element ?>" <?php echo in_array($element, $cats) ? 'checked' : '' ?> >
            <label for="<?php echo $this->key.'-'.$element ?>"><?php echo $value ?></label>&nbsp;
        </span>
        <?php
        endforeach;
    }
    public function post_update($item, $key)
    {
        global $wpdb;
        // liste des bovins de l'éleveur
        $query = "SELECT id, name FROM {$this->table} WHERE {$this->key} = {$this->value}";
        $array = $wpdb->get_results($wpdb->prepare($query, ''), ARRAY_A);
        tbr_journal($wpdb->last_query, 'dbr');
        if ($array === false) {
            return;
        }
        $vals = '';
        foreach ($array as $arr):
            if (!empty($vals)) $vals .= ',';
            $vals .= $arr['id'];
        endforeach;
        $result = $wpdb->query("UPDATE {$this->get_option_table()} SET {$this->get_column()} = '{$vals}' WHERE id = {$this->value}");
        tbr_journal($wpdb->last_query, 'dbu');
    }

}


class Tableur_Html extends Tableur_Element
{
    /**
     * VUE
     */
    public function get_display_simple($item, $key)
    {
        return '<span>'.($item[$key]).'</span>';
    }

    /**
     * FORMULAIRE
     */
    public function display_control($item, $key)
    {
        $settings = array();
        $settings['wpautop'] = false;
        wp_editor($this->get_value($item), $this->key, $settings);
    }

}

class Tableur_Mail extends Tableur_Element
{
    /**
     * VUE
     */
    public function get_display_simple($item, $key)
    {
        $html = '<button id="'. $this->key . '" name="'. $this->key . '" >'. $this->get_label_column().'</button>';
        return $html;
    }

    /**
     * FORMULAIRE
     */
    public function display_field($item, $key) {
        // rien à afficher
    }

    /**
     * Ajout du bouton "Envoyer le mail"
     */
    public function add_action($item, $key)
    {
        ?>
        <button type="submit" id="<?php echo $this->key ?>" name="<?php echo $this->key ?>" value="<?php echo $this->get_label_field() ?>" class="button-primary" ><?php echo esc_attr($this->get_label_field()) ?></button>
        <?php

    }

    /**
     * Envoi du mail
     */
    public function do_action(&$item, $key)
    {
        $confirm = Helper::macro($this->get_option('confirm'), $item);
        if ( empty($confirm)) {
            $this->tableur->add_message(__('Message not sended', 'tbr'));
            return;
        }
        $headers = array('Content-Type: text/html; charset=UTF-8');
        if ( !empty($this->get_option('from'))) {
            $headers[] = "From: " . Helper::macro($this->get_option('from'), $item) . "\r\n";
        }
        if ( !empty($this->get_option('reply'))) {
            $headers[] = "Reply-To: " . Helper::macro($this->get_option('reply'), $item) . "\r\n";
        }

        $to = '';
        $copy = '';
        $cci = '';
        $subject = 'no object';
        $body = 'no body';
        if ( !empty($this->get_option('to'))) {
            $to = Helper::macro($this->get_option('to'), $item);
        }
        if ( !empty($this->get_option('copy'))) {
            $copy = Helper::macro($this->get_option('copy'), $item);
            $headers[] = "Cc: " . $copy . "\r\n";
        }
        if ( !empty($this->get_option('cci'))) {
            $cci = Helper::macro($this->get_option('cci'), $item);
            //$headers[] = "Bcc: " . $cci . "\r\n";
        }
        if ( !empty($this->get_option('subject'))) {
            $subject = Helper::macro($this->get_option('subject'), $item);
        }
        if ( !empty($this->get_option('body'))) {
            $body = Helper::macro($this->get_option('body'), $item);
        }
        $result = true;
        // si cci boucle d'envoi des mails individuellement avec fusion du body
        if ( !empty($cci) ) {
            // MAILING
            // éclatement des emails de cci
            $emails = explode(',', $cci);
            foreach ($emails as $email) {
                // fusion des variables du body
                $pos = strpos(trim($email), '<'); 
                if ( $pos === false) {
                    $names = explode('@', trim($email));
                    $item['mailing_name'] = trim($names[0]);    
                    $item['mailing_email'] = trim($email);
                } else {
                    $item['mailing_name'] = substr(trim($email), 0, $pos);
                    $item['mailing_email'] = substr(trim($email), $pos+1, strlen(trim($email))-$pos-2);
                }
                $bodym = Helper::macro($body, $item);
                $result = wp_mail( trim($email), $subject, $bodym, $headers );
                if ($result) {
                    $this->tableur->add_message($email . ': ' .  __('Message sended', 'tbr'));
                    tbr_journal("{$subject} : {$email}", "MAIL");
                } else {
                    $this->tableur->add_error($email . ': ' .  __('Message not sended', 'tbr'));
                    tbr_journal("{$subject} : {$email}", "ERROR MAIL");
                }
            }
            // maj de l'historique
            if ( !empty($this->get_option('histo'))) {
                $item[$this->get_option('histo')] = "\r\n" . current_time( 'mysql') . " mailing\r\n" . $cci  . "\r\n" . $item[$this->get_option('histo')];
            }
        } else {
            $result = wp_mail( $to, $subject, $body, $headers );
            if ($result) {
                $this->tableur->add_message($to . ': ' .  __('Message sended', 'tbr'));
                tbr_journal("{$subject} : {$to}", "MAIL");
            } else {
                $this->tableur->add_error($to . ': ' .  __('Message not sended', 'tbr'));
                tbr_journal("{$subject} : {$to}", "ERROR MAIL");
            }
            if ( !empty($this->get_option('histo'))) {
                $item[$this->get_option('histo')] = "\r\n" . current_time( 'mysql') . " mail à\r\n" . $to  . "\r\n" . $item[$this->get_option('histo')];
            }
        }
        if ( !empty($this->get_option('date'))) {
            $item[$this->get_option('date')] = current_time( 'mysql');
        }
        // on force l'enregistrement
        $_REQUEST['submit'] = $key;
    }
}

class Tableur_Note extends Tableur_Element
{
    /**
     * VUE
     */

    /**
     * FORMULAIRE
     */
    public function display_control($item, $key)
    {
        ?>
        <textarea id="<?php echo $this->key ?>" name="<?php echo $this->key ?>" style="width: 100%" cols="100" rows="5"
        <?php if ( $this->is_protected() ) { echo ' disabled="disabled"'; } ?>
        ><?php echo esc_attr($this->get_value($item)) ?></textarea>
        <?php
    }

}

class Tableur_Number extends Tableur_Element
{
    /**
     * VUE
     */
    public function get_display_editable($item, $key)
    {
        $class = "editable tbr-editable";
        $html = '<a data-name="' . $key . '"';
        $html .= ' class="'.$class.'"';
        $html .= ' data-type="number"';
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
     * FORMULAIRE
     */
    public function display_control($item, $key)
    {
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
            <?php if ( $this->is_protected() ) {
            echo ' disabled="disabled"';
        }
        ?>
            <?php if (!empty($this->get_size())) {
            echo ' size="' . $this->get_size() . '"';
        }
        ?>
            <?php if (!empty($this->get_min())) {
            echo ' min="' . $this->get_min() . '"';
        }
        ?>
            <?php if (!empty($this->get_max())) {
            echo ' max="' . $this->get_max() . '"';
        }
        ?>
        >
        <?php

    }

}

class Tableur_Radio extends Tableur_Element
{
    /**
     * VUE
     */
    public function get_display_simple($item, $key)
    {
        $val = "";
        $cats = explode(',', $item[$key]);
        foreach ($this->get_items($key) as $element => $valeur) {
            if (in_array($element, $cats)) {
                $val .= empty($val) ? $valeur : ', ' . $valeur;
            }
        }
        return $val;
    }

    public function get_display_editable($item, $key)
    {
        $val = "";
        $cats = explode(',', $item[$key]);
        foreach ($this->get_items($key) as $element => $valeur) {
            if (in_array($element, $cats)) {
                $val .= empty($val) ? $valeur : ', ' . $valeur;
            }
        }
        $source = array();
        foreach ($this->get_items($key) as $element => $valeur) {
            $source[] = array('value' => $valeur, 'text' => $element);
        }

        $class = "editable tbr-editable-items";
        $html = '<a data-name="' . $key . '"';
        $html .= ' class="'.$class.'"';
        $html .= ' data-type="select"';
        $html .= ' data-pk="' . $item['id'] . '"';
        $html .= ' data-value="' . $item[$key] . '"';
        $html .= ' data-title="' . $this->get_label_column() . '"';
        $html .= $this->is_refresh() ? ' data-refresh="true"' : '';
        $html .= " data-source='/wp-admin/admin-ajax.php?name=" . $key . "'";        $html .= ' href="#"';
        $html .= '>' . $val . '</a>';
        return $html;
    }

    /**
     * FORMULAIRE
     */
    public function display_control($item, $key)
    {
        $cats = explode(',', $item[$key]);
        foreach ($this->get_items($key) as $element => $value):
            ?>
            <span style="white-space: nowrap;">
                <input type="radio" id="<?php echo $key.'-'.$element ?>" name="<?php echo $key ?>"
                value="<?php echo $element ?>" <?php echo $element == $item[$key] ? 'checked' : '' ?> >
                <label for="<?php echo $key.'-'.$element ?>"><?php echo $value ?></label>&nbsp;
            </span>
            <?php
        endforeach;
    
    }

}

/**
 * Listbox simple
 */
class Tableur_Select extends Tableur_Element
{
    /**
     * VUE
     */
    public function get_display_simple($item, $key)
    {
        $val = "";
        $cats = explode(',', $item[$key]);
        foreach ($this->get_items($key) as $element => $valeur) {
            if (in_array($element, $cats)) {
                $val .= empty($val) ? $valeur : ', ' . $valeur;
            }
        }
        return $val;
    }

    public function get_display_editable($item, $key)
    {
        $val = "";
        $cats = explode(',', $item[$key]);
        foreach ($this->get_items($key) as $element => $valeur) {
            if (in_array($element, $cats)) {
                $val .= empty($val) ? $valeur : ', ' . $valeur;
            }
        }
        $source = array();
        foreach ($this->get_items($key) as $element => $valeur) {
            $source[] = array('value' => $valeur, 'text' => $element);
        }

        $class = "editable tbr-editable-items";
        $html = '<a data-name="' . $key . '"';
        $html .= ' class="'.$class.'"';
        $html .= ' data-type="select"';
        $html .= ' data-pk="' . $item['id'] . '"';
        $html .= ' data-value="' . $item[$key] . '"';
        $html .= ' data-title="' . $this->get_label_column() . '"';
        $html .= $this->is_refresh() ? ' data-refresh="true"' : '';
        $html .= " data-source='/wp-admin/admin-ajax.php?name=" . $key . "'";        $html .= ' href="#"';
        $html .= '>' . $val . '</a>';
        return $html;
    }

    public function get_search($search)
    {
        $where = "";
        foreach ($this->get_items($this->key) as $element => $valeur) {
            if ( !empty($where) ) $where .= " OR ";
            $where .= "({$this->key} = '{$element}' AND '{$valeur}' like '%{$search}%')";
        }
        return $where;
    }

    /**
     * FORMULAIRE
     */
    public function display_control($item, $key)
    {
        ?>
    <select id="<?php echo $this->key ?>" name="<?php echo $this->key ?>">
    <?php foreach ($this->get_items($key) as $key => $value): ?>
        <option value="<?php echo $key ?>"<?php echo ($key == $this->get_value($item) ? 'selected' : '') ?>><?php echo esc_attr($value) ?></option>
    <?php endforeach;?>
    </select>
    <?php

    }
    public function update_request()
    {
        $i = 1;
        $val = "";
        while (!empty($_REQUEST[$this->key . '-' . $i])) {
            $val .= empty($val)
            ? $_REQUEST[$this->key . '-' . $i]
            : ',' . $_REQUEST[$this->key . '-' . $i];
            $i++;
        }
        $_REQUEST[$key] = $val;
    }

}

class Tableur_Text extends Tableur_Element
{
    /**
     * VUE
     */

    /**
     * FORMULAIRE
     */

}

class Tableur_Textarea extends Tableur_Element
{
    /**
     * VUE
     */

    /**
     * FORMULAIRE
     */
    public function display_control($item, $key)
    {
        ?>
        <textarea id="<?php echo $this->key ?>" name="<?php echo $this->key ?>" style="width: 100%" cols="100" rows="3" maxlength="240"
        <?php if ( $this->is_protected() ) { echo ' disabled="disabled"'; } ?>
        ><?php echo esc_attr($this->get_value($item)) ?></textarea>
        <?php
    }
}
