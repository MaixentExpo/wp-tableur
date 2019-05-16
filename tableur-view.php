<?php

/**
 * Affichage des Vues
 * https://wpengineer.com/2426/wp_list_table-a-step-by-step-guide/
 * https://www.sitepoint.com/using-wp_list_table-to-create-wordpress-admin-tables/
 * https://github.com/collizo4sky/WP_List_Table-Class-Plugin-Example/blob/master/plugin.php
 * https://stackoverflow.com/questions/28964085/filtering-wp-list-table-results-to-show-just-pages
 */
// Prohibit direct script loading.
defined('ABSPATH') || die('No direct script access allowed!');

class Tableur_List_Table extends WP_List_Table
{
    private $tableur;
    private $total_records;

    public function __construct($tableur)
    {
        $this->tableur = $tableur;
        parent::__construct(array(
            'singular' => $this->tableur->get_attribut_table($this->tableur->get_table(), 'singular'),
            'plural' => $this->tableur->get_attribut_table($this->tableur->get_table(), 'plural'),
            'ajax' => true,
        ));
        add_action('admin_head', array(&$this, 'custom_column_style'));
    }

    /**
     * Generates content for a single row of the table
     *
     * @since 3.1.0
     *
     * @param object $item The current item
     */
    public function single_row($item)
    {
        echo '<tr id="tbr-' . $item['id'] . '">';
        $this->single_row_columns($item);
        echo '</tr>';
    }

    /**
     * Retourne le contenu de la colonne à afficher dans la liste
     */
    public function column_default($item, $key)
    {
        $rub = $this->tableur->get_rubrique($key);
        return $rub->get_display($item, $key);
    }

    /**
     * Actions sur un item (row)
     */
    public function column_name($item)
    {
        $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : 1;
        $search = isset($_REQUEST['s']) ? "&s={$_REQUEST['s']}" : "";
        $page_current = $this->tableur->get_app() . '-' . $this->tableur->get_table() . '-' . $this->tableur->get_view();
        // Actions sur chaques lignes
        $actions = array();
        $form_edit = $page_current . '-' . $this->tableur->get_attribut_view_current('form_edit');
        if ( $form_edit ) {
            $actions['edit'] = sprintf(
                '<a href="?page=%s&id=%s&paged=%s%s">%s</a>',
                $form_edit,
                $item['id'],
                $paged,
                $search,
                __('Edit', 'tbr')
            );
        }
        if ( $this->tableur->get_attribut_view_current('deletable') ) {
            $actions['delete'] = sprintf(
                '<a href="?page=%s&action=delete&id=%s">%s</a>',
                $page_current,
                $item['id'],
                __('Delete', 'tbr')
            );
        }

        if ($this->tableur->get_attribut_view_current('actions')):
            foreach ($this->tableur->get_attribut_view_current('actions') as $action => $form_action):
                $slug = sanitize_title($action);
                // TODO: tester la présence des paramètres
                $table = Helper::macro($form_action['table'], $item);
                $view = Helper::macro($form_action['view'], $item);
                $form = Helper::macro($form_action['form'], $item);
                $id = Helper::macro($form_action['id'], $item);
                $p1 = Helper::macro($form_action['p1'], $item);
                $page = sprintf("%s-%s-%s-%s", $this->tableur->get_app(), $table, $view, $form);
                $form_edit = $page_current . '-' . $form_action;
                $actions[$slug] = sprintf(
                    '<a href="?page=%s&id=%s&p1=%s&paged=%s%s">%s</a>',
                    $page,
                    $id,
                    $p1,
                    $paged,
                    $search,
                    $action);
            endforeach;
        endif;

        return sprintf(
            '%s %s',
            // $item['name'],
            $this->column_default($item, 'name'),
            $this->row_actions($actions)
        );
    }

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    /**
     * Header des colonnes
     */
    public function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
        );
        foreach ($this->tableur->get_rubriques() as $key => $rub) {
            $columns[$key] = $rub->get_label_column();
        }
        return $columns;
    }

    public function get_sortable_columns()
    {
        $sortable_columns = array();
        /* @var $rub Tableur_Column */
        foreach ($this->tableur->get_rubriques() as $key => $rub) {
            if ($rub->is_sortable()) {
                $sortable_columns[$key] = array($key, true);
            }
        }
        return $sortable_columns;
    }

    /**
     * Style des colonnes
     */
    public function custom_column_style()
    {
        // tbr_journal($this->tableur, "custom_column_style");
        echo '<style type="text/css">';
        foreach ($this->tableur->get_rubriques() as $key => $rub):
            $style = $rub->get_col_style();
            if ($style != false):
                echo '.wp-list-table .column-' . $key . ' {' . $style . '} ';
            endif;
        endforeach;
        echo '</style>';
    }

    /**
     * Filtres de la vue
     */
    protected function get_views()
    {
        global $wpdb;
        $views = array();

        $url = "admin.php?page={$this->tableur->get_app()}-{$this->tableur->get_table()}-{$this->tableur->get_view()}";

        $this->total_records = $wpdb->get_var("SELECT COUNT(id) FROM {$this->tableur->get_table()}");
        tbr_journal($wpdb->last_query, "dbr");

        $current_menu = empty($_REQUEST['filter']) ? 'all' : $_REQUEST['filter'];
        if ($this->tableur->get_attribut_view_current('filters')):
            $class_current = $current_menu == 'all' ? " class='current'" : "";
            $views['all'] = "<a href='{$url}'{$class_current}>" . __('All', 'tbr') . " <span class='count'>($this->total_records)</span></a>";
            foreach ($this->tableur->get_attribut_view_current('filters') as $filter => $where):
                $count = $wpdb->get_var("SELECT COUNT(id) FROM {$this->tableur->get_table()} WHERE {$where};");
                tbr_journal($wpdb->last_query, "dbr");
                $slug = sanitize_title($filter);
                $url .= "&filter=" . $slug;
                $class_current = $current_menu == $slug ? " class='current'" : "";
                $views[$slug] = "<a href='{$url}'{$class_current}>$filter <span class='count'>($count)</span></a>";
            endforeach;
        endif;
        return $views;
    }

    public function get_bulk_actions()
    {
        $actions = array(
            'delete' => __('Delete', 'tbr'),
        );
        return $actions;
    }

    public function process_bulk_action()
    {
        global $wpdb;
        $table_name = $this->tableur->get_table();

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) {
                $ids = implode(',', $ids);
            }

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN({$ids})");
                tbr_journal($wpdb->last_query, "dbu");
            }
        }
    }

    public function prepare_items($search = '')
    {
        global $wpdb;

        $app = $this->tableur->get_app();
        $table_name = $this->tableur->get_table();
        $view_name = $this->tableur->get_view();

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        //$this->_column_headers = array($columns, $hidden, $sortable);
        // tbr_journal($this->_column_headers, " columns_header ------------");
        // tbr_journal($this->get_column_info(), "column info------------");
        $this->_column_headers = $this->get_column_info();

        $this->process_bulk_action();

        // Obtenir le nombre de lignes par page per_page
        $user = get_current_user_id();
        $screen = get_current_screen();
        $option = $screen->get_option('per_page', 'option');
        $per_page = get_user_meta($user, $option, true);
        if (empty($per_page) || $per_page < 1) {
            $per_page = $screen->get_option('per_page', 'default');
        }

        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        // $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;

        $orderby = "";
        if ( isset($_REQUEST['orderby'])) :
            $rub = $this->tableur->get_rubrique($_REQUEST['orderby']);
            $orderby = " ORDER BY " . $rub->get_orderby_sql();
            $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';
            $orderby .= ' ' . $order;
        endif;

        if (empty($orderby)) {
            $order = $this->tableur->get_attribut_view_current('orderby', '');
            if ( !empty($order)) $orderby = " ORDER BY " . $order;
        }

        // ajout jointure des foreign_keys
        $selects = "{$table_name}.id";
        $joins = "";
        foreach ($this->tableur->get_rubriques() as $key => $rub) {
            $selects .= ", ";
            $selects .= $rub->get_column_select_sql();
            $joins .= $rub->get_column_join_sql();
        }
        $query = "SELECT " . $selects . " FROM " . $table_name . $joins;
        $where = "";
        if (isset($_REQUEST['filter']) and $this->tableur->get_attribut_view_current('filters')):
            foreach ($this->tableur->get_attribut_view_current('filters') as $filter => $where_filter):
                $slug = sanitize_title($filter);
                if ($_REQUEST['filter'] == $slug):
                    $where = " WHERE (" . $where_filter . ")";
                endif;
            endforeach;
        endif;

        if (!empty($_REQUEST['s'])) {
            if (empty($where)) {
                $where .= " WHERE (";
            } else {
                $where .= " AND (";
            }
            $debut = true;
            foreach ($this->tableur->get_rubriques() as $key => $rub) {
                $where_search = $rub->get_search($_REQUEST['s']);
                if (!empty($where_search)) {
                    if (!$debut) {
                        $where .= " OR ";
                    }
                    $where .= $where_search;
                    $debut = false;
                }
            }
            $where .= ")";
        }
        $where_wiew = $this->tableur->get_attribut_view_current('where');
        if (!empty($where_wiew)) {
            if (empty($where)) {
                $where .= " WHERE ";
            } else {
                $where .= " AND ";
            }
            $where .= " (" . $where_wiew . ")";
        }
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM {$table_name} {$where}");
        tbr_journal($wpdb->last_query, 'dbr');

        $query .= $where;
        $query .= "$orderby LIMIT {$per_page} OFFSET {$offset}";

        $this->items = $wpdb->get_results($wpdb->prepare($query, ''), ARRAY_A);
        tbr_journal($wpdb->last_query, 'dbr');
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ));
    }

    public function view_header()
    {
        $page_current = $_REQUEST['page'];
        ?>
        <div id="tableur-header" class="nav-tab-wrapper">
        <h1 class="wp-heading-inline" style="float: left; padding: 5px 0 0; line-height: inherit;"><?php echo $this->tableur->get_application_title(); ?></h1>
    <?php
        foreach ($this->tableur->get_tables() as $table => $value) {
            foreach ($this->tableur->get_views($table) as $view => $vvalue) {
                $page = $this->tableur->get_app() . '-' . $table . '-' . $view;
                $title = $this->tableur->get_attribut_view($table, $view, 'title');
                ?>
            <a href="admin.php?page=<?php echo $page ?>" class="nav-tab<?php echo $page_current == $page ? ' nav-tab-active' : '' ?>"><?php echo $title ?></a>
            <?php

            }
        }
        ?>
        <!--
        <a href="admin.php?page=tableur_about" class="nav-tab<?php echo $page_current == 'tableur_about' ? ' nav-tab-active' : '' ?>" style="margin-left: 2em"><?php _e('About', 'tbr')?></a>
        -->
    </div><!-- /nav-tab-wrapper -->
    <?php

    }

}

function tableur_about()
{
    $readme = file_get_contents(dirname(__FILE__) . '/readme.txt');
    add_meta_box("tableur_metabox_about", __('About', 'tbr'), 'tableur_postbox_text', 'tableur_screen_about', 'normal', 'default');
    ?>
<div class="wrap">
    <?php //tableur_page_view_header()?>
    <div class="metabox-holder" id="poststuff">
        <div id="post-body">
            <div id="post-body-content">
            <?php do_meta_boxes("tableur_screen_about", 'normal', phpinfo());?>
            </div>
        </div>
    </div>
</div>
<?php

}
function tableur_postbox_text($content)
{
    ?>
<pre><?php echo $content; ?></pre>
<?php

}
