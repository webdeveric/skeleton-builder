<?php
/*
    @todo UI improvements

        Add WP style tool tips.

    @todo

        Do we want to add support for letting the user choose the post_status of the new content?

    @todo Add i18n support

        http://codex.wordpress.org/I18n_for_WordPress_Developers

    @todo Add multi-site support

        Should this plugin show up only in network admin?
        Have a collapsible checkbox area where the admin user can choose which sites get the skeleton.

        Do we want to have a saved skeletons section? I'm thinking it would be a default site structure for new sites in multi-network.
        I think we can auto create the skeleton whenever a new site is added to the network.

    @todo Add a "deactivate this plugin" button

        Upon success, show a button next to the help button that will send an AJAX request to deactivate the plugin.
        If AJAX is 200, redirect to plugins page.

    @todo sitemap.xml support?

        Do we want to support parsing this format with the JavaScript drag and drop feature?
            - No, let the server do it.

        Let the server parse a remote sitemap.xml file.
            - The sitemap.xml must have clean URLs.

*/

namespace WDE\SkeletonBuilder;

use WDE\PluginLibrary\Plugin;
use WDE\PluginLibrary\PluginRequirements;
// use WDE\PluginLibrary\Autoloader;
use WDE\DI\Container;

class Skeleton_Entry
{
    public $slug;
    public $title;
    public $depth;
    public function __construct($slug, $title = null, $depth = 0)
    {
        $this->slug = sanitize_title_with_dashes($slug, '', 'save');
        if (isset($title)) {
            $this->title = sanitize_title(trim($title));
        } else {
            $this->title = sanitize_title(str_replace('-', ' ', $this->slug));
        }
        $this->depth = (int) $depth;
    }
}

/*
    This function is native to PHP 5.5.
*/
if (! function_exists('json_last_error_msg')) {
    public function json_last_error_msg()
    {
        static $errors = array(
            JSON_ERROR_NONE           => null,
            JSON_ERROR_DEPTH          => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR      => 'Unexpected control character found',
            JSON_ERROR_SYNTAX         => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8           => 'Malformed UTF-8 characters, possibly incorrectly encoded'
       );
        $error = json_last_error();

        return array_key_exists($error, $errors) ? $errors[$error] : "Unknown error ({$error})";
    }
}

class SkeletonBuilderPlugin extends Plugin
{
    const VERSION = '0.3.1';

    private $menu_id;
    private $existing_menu_items;

    private $current_user;
    private $errors;

    private $post_type;
    private $hierarchical;

    public function __construct()
    {
        try {

            parent::__construct(SKELETON_BUILDER_PLUGIN_FILE);

            $this->container->requirements = function (Container $container) {
                $requirements = new PluginRequirements($container->plugin);
                $requirements->wp('3.5')->php('5.3');
                $container->plugin->setRequirements($requirements);

                return $requirements;
            };

            add_theme_support('menus');
            add_action('admin_menu', array(&$this, 'setup_menu'));
            add_action('wp_ajax_skeleton_builder', array(&$this, 'ajax_process'));

        } catch (\Exception $e) {

            var_dump($e);

        }

    }

    public function setup_menu()
    {
        $menu_icon = sprintf('<img src="%1$s" width="13" height="13" alt="" title="" style="vertical-align:middle;" />', plugins_url('/imgs/skeleton-builder-icon.png', SKELETON_BUILDER_PLUGIN_FILE));

        $page_hook = add_management_page('Skeleton Builder', $menu_icon . ' Skeleton Builder', 'edit_pages', 'skeleton-builder', array(&$this, 'admin_page'));

        $this->addMessageScreen($page_hook);

        $this->add_plugin_link(menu_page_url('skeleton-builder', false), $menu_icon . ' Build a Skeleton');

        add_action('admin_print_styles-' . $page_hook, array(&$this, 'admin_page_css'));
        add_action('admin_print_scripts-' . $page_hook, array(&$this, 'admin_page_js'));
        add_action('load-' . $page_hook, array(&$this, 'admin_page_load'));

    }

    public function admin_page_load()
    {
        $screen = get_current_screen();

        $help_sidebar = sprintf(
            '<div id="skeleton-help-sidebar"><p><strong>%1$s</strong></p><p>Version: %2$s<p></div>',
            $this->getPluginName(),
            $this->getPluginVersion()
       );

        $screen->set_help_sidebar($help_sidebar);

        $help = include(__DIR__ . '/views/help.php');

        foreach ($help as $title => $content) {
            $screen->add_help_tab(array(
                'id'      => sanitize_title_with_dashes($title),
                'title'   => $title,
                'content' => $content
           ));
        }
    }

    public function admin_page_js()
    {
        $nested_sortable   = WP_DEBUG ? '/js/jquery.mjs.nestedSortable.js' : '/js/jquery.mjs.nestedSortable.min.js';
        $skeleton          = WP_DEBUG ? '/js/skeleton.js'                  : '/js/skeleton.min.js';
        $spin              = WP_DEBUG ? '/js/spin.js'                      : '/js/spin.min.js';
        $jquery_spin       = WP_DEBUG ? '/js/jquery.spin.js'               : '/js/jquery.spin.min.js';
        $autogrow_textarea = WP_DEBUG ? '/js/jquery.autogrow-textarea.js'  : '/js/jquery.autogrow-textarea.min.js';

        wp_enqueue_script(
            'spin',
            plugins_url($spin, SKELETON_BUILDER_PLUGIN_FILE),
            array(),
            self::VERSION,
            true
       );

        wp_enqueue_script(
            'jquery-spin',
            plugins_url($jquery_spin, SKELETON_BUILDER_PLUGIN_FILE),
            array('jquery', 'spin'),
            self::VERSION,
            true
       );

        wp_enqueue_script(
            'nested-sortable',
            plugins_url($nested_sortable, SKELETON_BUILDER_PLUGIN_FILE),
            array('jquery', 'jquery-ui-core', 'jquery-ui-sortable'),
            self::VERSION
       );
        wp_enqueue_script(
            'autogrow-textarea',
            plugins_url($autogrow_textarea, SKELETON_BUILDER_PLUGIN_FILE),
            array('jquery'),
            self::VERSION
       );
        wp_enqueue_script(
            'skeleton',
            plugins_url($skeleton, SKELETON_BUILDER_PLUGIN_FILE),
            array('jquery', 'nested-sortable', 'jquery-spin'),
            self::VERSION,
            true // Load in footer after the rest of the skeleton form has been added to the DOM.
       );
    }

    public function admin_page_css()
    {
        wp_enqueue_style('skeleton-builder', plugins_url('/css/skeleton-builder.css', SKELETON_BUILDER_PLUGIN_FILE), array(), self::VERSION);
    }

    public function admin_page()
    {
        if ( ! current_user_can('edit_pages')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $skeleton           = $this->post_field('skeleton');
        $skeleton_menu      = $this->post_field('skeleton_menu');
        $skeleton_post_type = $this->post_field('skeleton_post_type', 'page');

        if (filter_has_var(INPUT_POST, 'skeleton_builder_action')) {
            if (wp_verify_nonce($_POST['skeleton_builder_action'], 'build-skeleton')) {
                if ($skeleton != '') {
                    $menu_id = false;
                    $ok = $this->process($skeleton, $skeleton_menu, $menu_id, $skeleton_post_type);
                    if (is_wp_error($ok)) {
                        $this->addErrorMessage($ok);
                    } elseif ($ok == true) {
                        $this->success($skeleton, $skeleton_menu, $menu_id, $skeleton_post_type);
                        return;
                    } else {
                        $this->addErrorMessage('Something went wrong.');
                    }
                } else {
                    $this->addErrorMessage('Skeleton cannot be empty.');
                }
            } else {
                $this->addErrorMessage('NONCE value incorrect');
            }
        }
        $this->form($skeleton, $skeleton_menu, $skeleton_post_type);
    }

    public function get_menu_item_id(&$items, $post_id)
    {
        foreach ($items as $item) {
            if (isset($item->object_id) && (int) $item->object_id == (int) $post_id) {
                return $item->db_id;
            }
        }

        return 0;
    }

    public function success($skeleton, $skeleton_menu, $menu_id, $skeleton_post_type)
    {
        include __DIR__ . '/views/success.php';
    }

    protected function get_post_types()
    {
        $post_types = get_post_types('','objects');
        foreach ($post_types as $name => $type) {
            $post_types[ $name ] = array(
                'name' => $type->labels->singular_name,
                'hierarchical' => $type->hierarchical
           );
        }
        $post_types = array_diff_key(
            $post_types,
            array(
                'attachment'    => false,
                'nav_menu_item' => false,
                'revision'      => false
           )
       );

        return $post_types;
    }

    public function form($skeleton = '', $skeleton_menu = '', $skeleton_post_type = 'page')
    {
        if ($this->hasMessages())
            $this->display_messages();

        $skeleton_post_types = $this->get_post_types();
        uksort($skeleton_post_types, "strnatcasecmp");

        include __DIR__ . '/views/form.php';
    }

    /*
        This method handles the old skeleton format. It is only used if the browser has JavaScript turned off.
    */
    public function process($skeleton, $skeleton_menu = '', &$menu_id = false, $skeleton_post_type = 'page')
    {
        $pt = get_post_type_object($skeleton_post_type);

        if(! isset($pt))
            return new \WP_Error('posttype', 'Invalid post type');

        $hierarchical = isset($pt, $pt->hierarchical) ? $pt->hierarchical : false;
        unset($pt);

        $current_user        = wp_get_current_user();
        $line_items          = preg_split('#(\r|\n|\r\n)+#', $skeleton, -1, PREG_SPLIT_NO_EMPTY);
        $menu_id             = false;
        $content_ids         = array();
        $menu_ids            = array();
        $existing_menu_items = array();
        $depth_identifier    = '#^-*#';

        if ($skeleton_menu != '') {
            $skeleton_menu_obj = wp_get_nav_menu_object($skeleton_menu);
            $menu_id = $skeleton_menu_obj !== false ? $skeleton_menu_obj->term_id : wp_create_nav_menu($skeleton_menu);
            $menu_id = (int) $menu_id;
            $existing_menu_items = wp_get_nav_menu_items($skeleton_menu);
        }

        // Setup Skeleton_Entry objects
        foreach ($line_items as $index => $p) {

            $matches = array();
            $depth   = 0;

            if ($hierarchical) {
                preg_match($depth_identifier, $p, $matches);
                $depth = strlen($matches[0]);
            }

            $parts = explode(':', preg_replace($depth_identifier, '', $p));

            switch (count($parts)) {
                case 1:
                    $slug  = $parts[0];
                    $title = null;
                    break;
                case 2:
                default:
                    list($slug, $title) = $parts;
            }

            $line_items[ $index ] = new Skeleton_Entry($slug, $title, $depth);
        }

        foreach ($line_items as $index => $p) {

            $content = array(
                'post_author'    => $current_user->ID,
                'post_name'      => $p->slug,
                'post_title'     => $p->title,
                'menu_order'     => $index,
                'post_type'      => $skeleton_post_type,
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
                'post_status'    => 'publish'
            );

            if ($p->depth > 0) {
                if(isset($content_ids[ $p->depth - 1 ]))
                    $content['post_parent'] = $content_ids[ $p->depth - 1 ];
            }

            $existing_page = get_page_by_title($p->title, 'OBJECT', $skeleton_post_type);

            if (isset($existing_page) && isset($existing_page->ID)) {
                $content_ids[ $p->depth ] = $existing_page->ID;
            } else {
                $content_ids[ $p->depth ] = wp_insert_post($content);
            }

            if ($menu_id !== false) {

                $menu_item_db_id = $this->get_menu_item_id($existing_menu_items, $content_ids[ $p->depth ]);

                if ($menu_item_db_id == 0) {

                    $args = array(
                        'menu-item-object-id' => $content_ids[ $p->depth ],
                        'menu-item-object'    => $skeleton_post_type,
                        'menu-item-parent-id' => isset($content['post_parent']) && isset($menu_ids[ $content['post_parent'] ]) ? $menu_ids[ $content['post_parent'] ] : 0,
                        'menu-item-position'  => $content['menu_order'],
                        'menu-item-type'      => 'post_type',
                        'menu-item-status'    => 'publish'
                    );

                    $menu_ids[ $content_ids[ $p->depth ] ] = wp_update_nav_menu_item(
                        $menu_id,
                        $menu_item_db_id,
                        $args
                    );

                } else {
                    $menu_ids[ $content_ids[ $p->depth ] ] = $menu_item_db_id;
                }
            }
        }

        return true;
    }

    protected post_field($key, $default = '')
    {
        if (filter_has_var(INPUT_POST, $key))
            return trim(filter_input(INPUT_POST, $key));
        return $default;
    }

    public function ajax_process()
    {
        $this->errors       = array();
        $this->post_type    = 'page';
        $this->hierarchical = true;

        $skeleton_menu = $this->post_field('skeleton_menu');
        $skeleton      = null;

        if (filter_has_var(INPUT_POST, 'skeleton_builder_action') && ! wp_verify_nonce(filter_input(INPUT_POST, 'skeleton_builder_action'), 'build-skeleton'))
            $this->errors['nonce'] = 'empty or invalid nonce value';

        if (filter_has_var(INPUT_POST, 'skeleton_post_type')) {

            $pt = get_post_type_object( $this->post_field('skeleton_post_type') );

            if (isset($pt) && is_object($pt)) {

                if(isset($pt->name))
                    $this->post_type = $pt->name;

                if(isset($pt->hierarchical))
                    $this->hierarchical = $pt->hierarchical;

                unset($pt);

            } else {

                $this->errors['post_type'] = 'Invalid post type';

            }

        }

        if (filter_has_var(INPUT_POST, 'skeleton')) {

            $json = str_replace("\\", "", $this->post_field('skeleton'));

            $skeleton = json_decode($json);

            if (json_last_error() != JSON_ERROR_NONE)
                $this->errors['skeleton_json'] = json_last_error_msg();

            if (isset($skeleton)) {

                $this->menu_id = false;
                $this->existing_menu_items = array();
                $this->current_user = wp_get_current_user();

                if ($skeleton_menu != '') {
                    $skeleton_menu_obj = wp_get_nav_menu_object($skeleton_menu);
                    $this->menu_id = $skeleton_menu_obj !== false ? $skeleton_menu_obj->term_id : wp_create_nav_menu($skeleton_menu);
                    $this->menu_id = (int) $this->menu_id;
                    $this->existing_menu_items = wp_get_nav_menu_items($skeleton_menu);
                }

                $this->process_skeleton($skeleton);

            } else {

                $this->errors['skeleton'] = 'skeleton not set';

            }

        }

        $data = array();

        if (! empty($this->errors)) {
            $data['errors'] = $this->errors;
            wp_send_json_error($data);
            exit;
        }

        $data['notices']   = array();
        $data['notices'][] = sprintf('Your blank entries have been built successfully. Do you want to (<a href="edit.php?post_type=%s">view  them</a>?', $this->post_type);

        if($this->menu_id !== false)
            $data['notices'][] = sprintf('A menu (<a href="nav-menus.php?action=edit&menu=%1$d">%2$s</a>) has been created based on your skeleton.', $this->menu_id, $skeleton_menu);

        wp_send_json_success($data);
        exit;
    }

    protected function process_skeleton(&$skeleton, $parent_id = 0)
    {
        foreach ($skeleton as $index => $item) {

            $post_id = $this->process_item($item, $index, $parent_id);

            if (isset($item->children) && is_array($item->children) && ! empty($item->children)) {
                $this->process_skeleton($item->children, $post_id);
            }

        }

    }

    protected function process_item(&$item, $index, $parent_id = 0)
    {
        static $menu_ids = array();

        $content = array(
            'post_author'    => $this->current_user->ID,
            'post_name'      => $item->permalink,
            'post_title'     => $item->label,
            'post_type'      => $this->post_type,
            'menu_order'     => $index,
            'post_parent'    => $parent_id,
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'post_status'    => 'publish',
       );

        if ( ! $this->hierarchical)
            unset($content['post_parent'], $content['menu_order']);

        $existing_page = get_page_by_title($item->label, 'OBJECT', $this->post_type);

        $post_id = (isset($existing_page, $existing_page->ID)) ? $existing_page->ID : wp_insert_post($content);

        if ($this->menu_id !== false) {

            $menu_item_db_id = $this->get_menu_item_id($this->existing_menu_items, $post_id);

            if ($menu_item_db_id == 0) {

                $args = array(
                    'menu-item-object-id'  => $post_id,
                    'menu-item-object'     => $this->post_type,
                    'menu-item-title'      => $item->label,
                    'menu-item-attr-title' => $item->title,
                    'menu-item-classes'    => $item->cls,
                    'menu-item-parent-id'  => isset($content['post_parent']) && isset($menu_ids[ $content['post_parent'] ]) ? $menu_ids[ $content['post_parent'] ] : 0,
                    'menu-item-position'   => $index + 1,
                    'menu-item-type'       => 'post_type',
                    'menu-item-status'     => 'publish'
               );

                if ( ! $this->hierarchical)
                    unset($args['menu-item-parent-id'], $args['menu-item-position']);

                $menu_ids[ $post_id ] = wp_update_nav_menu_item(
                    $this->menu_id,
                    $menu_item_db_id,
                    $args
               );

            } else {

                $menu_ids[ $post_id ] = $menu_item_db_id;

            }

        }

        return $post_id;

    }

}
SkeletonBuilderPlugin::init();
