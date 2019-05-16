<?php
// Prohibit direct script loading.
defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Gestion des formulaires
 */
class Tableur_Form
{
    private $tableur;

    public function __construct($tableur)
    {
        $this->tableur = $tableur;
    }

    /**
     * Formulaire de saisie
     * http://www.responsive-mind.fr/developper-pour-wordpress-wpdb-et-les-requetes-sql/
     * $_REQUEST page id
     */
    public function form_handler()
    {
        $item = $this->read_write_db();
        $this->display_form($item);
    }

    function read_write_db() {
        global $wpdb;

        $default = array(
            'id' => 0,
        );
        foreach ($this->tableur->get_rubriques() as $key => $rub) {
            $default[$key] = $rub->get_default();
            $rub->update_request();
        }
        if (isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) :
            // tbr_journal($_REQUEST, "REQUEST");
            $item = shortcode_atts($default, stripslashes_deep($_REQUEST));
            $item_valid = $this->validate($item);
            if ($item_valid === true) :
                if ( ! $this->tableur->is_with_error()) :
                    // Déclenchement des actions
                    foreach ($this->tableur->get_rubriques() as $key => $rub) :
                        if ( isset($_REQUEST[$key])) {
                            $rub->do_action($item, $key);
                        }
                    endforeach;
                endif;
                // Copie de item dans itemdb on ne gardant que les champs à mettre à jour
                $itemdb = array();
                foreach ($this->tableur->get_rubriques() as $key => $rub) {
                    // on en profite pour valoriser les champs protégés avec leur valeur par défaut
                    // if ( $rub->is_protected() ) {
                    //     $rub->set_value('');
                    //     $item[$key] = $rub->get_value($item);
                    // }
                    // valorisation des rubriques avec les valeurs saisies
                    $rub->set_value($item[$key]);
                    // on enlève les champs qui ne devront pas être mis à jour
                    if ($rub->is_readonly() or $rub->is_temporary() ) {
                        continue;
                    }
                    $itemdb[$key] = $item[$key];                                        
                }
                if ( ! $this->tableur->is_with_error()) :
                    if ( isset($_REQUEST['submit']) ) :
                        if ($item['id'] == 0) :
                            // Insertion d'un nouvel article
                            $result = $wpdb->insert($this->tableur->get_table(), $itemdb);
                            tbr_journal($wpdb->last_query, "dbu");
                            $item['id'] = $wpdb->insert_id;
                            if ($result !== false) {
                                foreach ($this->tableur->get_rubriques() as $key => $rub) {
                                    $rub->post_update($item, $key);
                                    $rub->set_value($item[$key])->set_id($item['id']);
                                }
                                $this->tableur->add_message(__('Item was successfully saved', 'tbr'));
                            } else {
                                $this->tableur->add_error(__('There was an error while saving item', 'tbr'));
                                $this->tableur->add_error($wpdb->last_error, 'tbr');
                            }
                        else:
                            // Mise à jour d'un article
                            $result = $wpdb->update($this->tableur->get_table(), $itemdb, array('id' => $item['id']));
                            tbr_journal($wpdb->last_query, "dbu");
                            if ($result !== false) {
                                foreach ($this->tableur->get_rubriques() as $key => $rub) {
                                    if ( isset($item[$key])) {
                                        $rub->set_value($item[$key])->set_id($item['id']);
                                        $rub->post_update($item, $key);
                                    }
                                }
                                $this->tableur->add_message(__('Item was successfully updated', 'tbr'));
                            } else {
                                $this->tableur->add_error(__('There was an error while updating item', 'tbr'));
                                $this->tableur->add_error($wpdb->last_error, 'tbr');
                            }
                        endif;
                    endif; // submit
                endif; // no error
            else:
                // Le formulare n'est pas valide
                $this->tableur->add_error($item_valid, 'tbr');
            endif;
        else:
            // Lecture et affichage d'un article
            $item = $default;
            if (isset($_REQUEST['id']) and $_REQUEST['id'] != 0) {
                $query = "SELECT * FROM {$this->tableur->get_table()} WHERE id = {$_REQUEST['id']}";
                $item = $wpdb->get_row($wpdb->prepare($query, ''), ARRAY_A);
                tbr_journal($wpdb->last_query, "dbr");
                if (!$item) {
                    $item = $default;
                    $this->tableur->add_error(__('Item not found', 'tbr'));
                }
                foreach ($this->tableur->get_rubriques() as $key => $rub) {
                    $rub->set_value($item[$key]);
                }
            }
        endif; // fin traitement request

        return $item;
    }

    function display_form($item) 
    {
        $paged = isset($_REQUEST['paged']) ? "&paged={$_REQUEST['paged']}" : "";
        $search = isset($_REQUEST['s']) ? "&s={$_REQUEST['s']}" : "";
        if ( empty($_REQUEST['pageback'])) {
            $page_back = $this->tableur->get_app() . '-' . $this->tableur->get_table() . '-' . $this->tableur->get_view() . $paged . $search;
        } else {
            $page_back = $_REQUEST['pageback'];
        }
        add_meta_box('tableur_form_meta_box', $this->tableur->get_attribut_form_current('title'), [$this, 'display_field'], $page_back, 'normal', 'default');
        ?>
    <div class="wrap">
        <div id="tableur-header" class="nav-tab-wrapper">
            <h1 class="wp-heading-inline" style="float: left; padding: 5px 0 10px 0; line-height: inherit;">
                <span class=""><?php echo $this->tableur->get_application_title(); ?></span>
                <span class=""> - <?php echo $this->tableur->get_attribut_form_current('title'); ?></span>
            </h1>
        </div>
        <h2><a class="add-new-h2"
        href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=' . $page_back); ?>"><?php echo __('Back to list', 'tbr')?></a>
        </h2>

        <?php if (!empty($this->tableur->get_errors())): 
            foreach($this->tableur->get_errors() as $error) : ?>
            <div id="notice" class="error below-h2"><p><?php echo $error ?></p></div>
        <?php endforeach; endif;?>
        <?php if (!empty($this->tableur->get_messages())): 
            foreach($this->tableur->get_messages() as $message) : ?>
            <div id="notice" class="updated below-h2"><p><?php echo $message ?></p></div>
        <?php endforeach; endif;?>

        <form id="form" method="POST">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__)) ?>"/>
            <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

            <div class="metabox-holder" id="poststuff">
                <div id="post-body">
                    <div id="post-body-content">

                        <?php do_meta_boxes($page_back, 'normal', $item);?>

                        <?php if (!empty($this->tableur->get_errors())): 
                            foreach($this->tableur->get_errors() as $error) : ?>
                            <div id="notice" class="error below-h2"><p><?php echo $error ?></p></div>
                        <?php endforeach; endif;?>
                        <?php // boutons d'action
                        if ($this->tableur->get_attribut_form_current('submit', true)) : ?>
                            <input type="submit" value="<?php _e('Save', 'tbr')?>" id="submit" name="submit" class="button-primary" name="submit">
                        <?php endif; 
                        foreach ($this->tableur->get_rubriques() as $key => $rub) {
                            $rub->add_action($item, $key);
                        }                        
                        ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php

    }

    function display_field($item)
    {
        ?>
    <tbody >
        <style>
        div.postbox {width: 70%; margin-left: 73px;}
        </style>
        <div class="formdata">
    <?php
        foreach ($this->tableur->get_rubriques() as $key => $rub):
            if ( $key == 'id') continue;
            $rub->display_field($item, $key);
        endforeach;
        ?>
        </div>
    </tbody>
    <?php

    }

    function validate($item)
    {
        $messages = array();

        if (empty($item['name'])) {
            $messages[] = __('Name is required', 'tbr');
        }

        if (empty($messages)) {
            return true;
        }

        return implode('<br />', $messages);
    }

}



