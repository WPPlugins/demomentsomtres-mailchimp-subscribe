<?php
/*
  Plugin Name: DeMomentSomTres MailChimp Subscribe
  Plugin URI: http://www.demomentsomtres.com/en/wordpress-plugins/mailchimp-subscribe/
  Description:  MailChimp Subscription Management
  Version: 3.201704281523
  Author: Marc Queralt
  Author URI: http://www.demomentsomtres.com
 */

require_once (dirname(__FILE__) . '/lib/class-tgm-plugin-activation.php');

// Create a helper function for easy SDK access.
function dms3ms_fs() {
    global $dms3ms_fs;

    if (!isset($dms3ms_fs)) {
        // Include Freemius SDK.
        require_once dirname(__FILE__) . '/freemius/start.php';

        $dms3ms_fs = fs_dynamic_init(array(
            'id' => '607',
            'slug' => 'demomentsomtres-mailchimp-subscribe',
            'type' => 'plugin',
            'public_key' => 'pk_0ab1c084442183ecc74630e9d73a2',
            'is_premium' => false,
            'has_addons' => false,
            'has_paid_plans' => false,
            'menu' => array(
                'first-path' => 'plugins.php',
                'account' => false,
                'contact' => false,
                'support' => false,
            ),
        ));
    }

    return $dms3ms_fs;
}

// Init Freemius.
dms3ms_fs();

if (!class_exists("DeMomentSomTresMailchimp")):
    include "mailchimp/demomentsomtres-mailchimp.php";
endif;

$demomentsomtres_mc_subscribe = new DeMomentSomTresMailchimpSubscribe;

class DeMomentSomTresMailchimpSubscribe {

    private $pluginURL;
    private $pluginPath;
    private $langDir;
    private $mcSession;
    private $loadJs = false;

    const OPTIONS = "dmst_mc_subscribe";
    const OPTION_APIKEY = "API";
    const OPTION_DOUBLEOPTIN = "doubleOptin";
    const OPTION_WELCOMEMSG = "welcomeMessage";
    const OPTION_INITIALLIST = "shortcodeInitialList";
    const OPTION_GA = "gaIntegration";
    const OPTION_WIDGETBUTTON = "widgetButton";
    const OPTION_WIDGETERROR = "widgetError";
    const TRANS_LISTS = "dms3subscribeLists";
    const TRANS_TIMESTAMP = "dms3subscribeListTimeStamp";
    const OPTIONSCACHE = "dms3subscribeCategories";
    const DIRECTOPTIONS = "dmst_mc_subscribe_options";

    /**
     * @since 2.0
     */
    function __construct() {
        $this->pluginURL = plugin_dir_url(__FILE__);
        $this->pluginPath = plugin_dir_path(__FILE__);
        $this->langDir = dirname(plugin_basename(__FILE__)) . '/languages';

        add_action('plugins_loaded', array($this, 'plugin_init'));
        add_action('tgmpa_register', array($this, 'required_plugins'));
        add_action('tf_create_options', array($this, 'admin'));
        add_action("tf_save_admin_" . self::OPTIONS, array($this, "tf_save_admin"), 10, 3);
        add_action("init", array($this, "mailchimp_init"), 99);
        add_action("wp_ajax_dms3subscribeLoadLists", array($this, "ajax_loadLists"));
        // You can uncomment this line if you want to debug this web service
        // add_action("wp_ajax_nopriv_dms3subscribeLoadLists", array($this, "ajax_loadLists"));
        add_action("admin_enqueue_scripts", array($this, "jsAdmin"));
        add_action('widgets_init', array($this, 'register_widgets'));
        add_shortcode('demomentsomtres-mc-subscription', array($this, 'shortcode_general'));
        add_action('template_redirect', array($this, 'jsAdd'));
        add_action('wp_print_footer_scripts', array($this, 'jsLoad'));
        add_action('wp_ajax_dms3mcquery', array($this, 'ajax_query'));
        add_action('wp_ajax_nopriv_dms3mcquery', array($this, 'ajax_query'));
        add_action('wp_ajax_nopriv_dms3mcsubscribe', array($this, 'ajax_subscribe'));
        add_action('wp_ajax_dms3mcsubscribe', array($this, 'ajax_subscribe'));
        add_action('wp_ajax_nopriv_dms3mcsubscribebutton', array($this, 'ajax_subscribe_button'));
        add_action('wp_ajax_dms3mcsubscribebutton', array($this, 'ajax_subscribe_button'));
        add_action('wp_ajax_nopriv_dms3mcunsubscribebutton', array($this, 'ajax_unsubscribe_button'));
        add_action('wp_ajax_dms3mcunsubscribebutton', array($this, 'ajax_unsubscribe_button'));
    }

    /**
     * @since 2.0
     */
    function plugin_init() {
        load_plugin_textdomain('DeMomentSomTres-MailChimp-Subscribe', false, $this->langDir);
    }

    /**
     * Initializes mailchimp
     * @since 2.0
     */
    function mailchimp_init() {
        if (!$this->mcSession):
            if (class_exists("TitanFramework")):
                $titan = TitanFramework::getInstance(self::OPTIONS);
                $api = $titan->getOption(self::OPTION_APIKEY);
                $this->mcSession = DeMomentSomTresMailchimp::MailChimpSession($api);
            endif;
        endif;
    }

    /**
     * @since 2.0
     */
    function required_plugins() {
        $plugins = array(array(
                'name' => 'Titan Framework',
                'slug' => 'titan-framework',
                'required' => true,
            ),
        );
        $config = array(
            'id' => 'demomentsomtres-mailchimp-subscribe', // Unique ID for hashing notices for multiple instances of TGMPA.
            'default_path' => '', // Default absolute path to bundled plugins.
            'menu' => 'tgmpa-install-plugins', // Menu slug.
            'parent_slug' => 'plugins.php', // Parent menu slug.
            'capability' => 'manage_options', // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
            'has_notices' => true, // Show admin notices or not.
            'dismissable' => true, // If false, a user cannot dismiss the nag message.
            'dismiss_msg' => '', // If 'dismissable' is false, this message will be output at top of nag.
            'is_automatic' => false, // Automatically activate plugins after installation or not.
            'message' => '', // Message to output right before the plugins table.
        );

        tgmpa($plugins, $config);
    }

    /**
     * @since 2.0
     */
    function admin() {
        $titan = TitanFramework::getInstance(self::OPTIONS);
        $panel = $titan->createAdminPanel(array(
            'name' => __("DeMomentSomTres - Mailchimp Subscribe", 'DeMomentSomTres-Mailchimp-Subscribe'),
            'id' => "dms3mailchimpsubscribe",
            'title' => __("Mailchimp Subscribe", 'DeMomentSomTres-Mailchimp-Subscribe'),
            'parent' => 'options-general.php',
        ));
        $tabConfig = $panel->createTab(array(
            'name' => __('General Options', 'DeMomentSomTres-Mailchimp-Subscribe'),
            'title' => __('General Options', 'DeMomentSomTres-Mailchimp-Subscribe'),
            'id' => 'configuration'
        ));
        $tabConfig->createOption(array(
            'name' => __("Mailchimp API Key", 'DeMomentSomTres-Mailchimp-Subscribe'),
            'id' => self::OPTION_APIKEY,
            'type' => "text",
        ));
//        $tabConfig->createOption(array(
//            'name' => __("Subscription Double Optin", 'DeMomentSomTres-Mailchimp-Subscribe'),
//            'id' => self::OPTION_DOUBLEOPTIN,
//            'type' => 'checkbox',
//            'desc' => "<p>" . __('If checked double opt-in is used to subscribe users to Lists (not groups).', 'DeMomentSomTres-MailChimp-Subscribe')
//            . "<br/><strong>"
//            . __('WARNING: It is not recommended to use this feature as the user would not be able to subscribe to any group until the confirmation instructions are followed.', 'DeMomentSomTres-MailChimp-Subscribe')
//            . "</strong></p>",
//        ));
//        $tabConfig->createOption(array(
//            'name' => __("Subscription Welcome Message", 'DeMomentSomTres-Mailchimp-Subscribe'),
//            'id' => self::OPTION_WELCOMEMSG,
//            'type' => 'checkbox',
//            'desc' => "<p>" . __('Determines if a welcome message is sent by MailChimp after a user is subscribed to a List.', 'DeMomentSomTres-MailChimp-Subscribe')
//            . "<br/><strong>"
//            . __('WARNING: It does not send a message if the user subscribes to a group that is not the first of the list.', 'DeMomentSomTres-MailChimp-Subscribe')
//            . "</strong>"
//            . "</p>",
//        ));
        $tabConfig->createOption(array(
            'name' => __("Show Initial List", 'DeMomentSomTres-Mailchimp-Subscribe'),
            'id' => self::OPTION_INITIALLIST,
            'type' => 'checkbox',
            'desc' => "<p>" . __('Determines if an initial list is shown in the shortcode page before the user enters an email address', 'DeMomentSomTres-MailChimp-Subscribe') . "</p>",
        ));
        $tabConfig->createOption(array(
            'name' => __("Google Analytics Integration", 'DeMomentSomTres-Mailchimp-Subscribe'),
            'id' => self::OPTION_GA,
            'type' => 'checkbox',
            'desc' => "<p>" . __('Each one of the buttons sends its own event to Google Analytics.', 'DeMomentSomTres-MailChimp-Subscribe')
            . "<br/><strong>"
            . __('WARNING: It only works with analytics.js. It does not work with ga.js.', 'DeMomentSomTres-MailChimp-Subscribe')
            . "</strong>"
            . "</p>",
        ));
        $tabConfig->createOption(array(
            'type' => "save",
            'save' => __("Save Changes", 'DeMomentSomTres-Mailchimp-Subscribe'),
            'use_reset' => false
        ));
        $tabGrups = $panel->createTab(array(
            'name' => __('Lists and groups', 'DeMomentSomTres-Mailchimp-Subscribe'),
            'title' => __('Lists and groups', 'DeMomentSomTres-Mailchimp-Subscribe'),
            'id' => 'grups',
        ));
        $tabGrups->createOption(array(
            'type' => "ajax-button",
            "id" => "loadLists",
            "desc" => __("This button will reload lists from MailChimp", 'DeMomentSomTres-MailChimp-Subscribe') . "<br/>"
            . $this->getListsLastUpdate() .
            "<pre style='display:none;'>" . print_r($this->getLists(), true) . "</pre>",
            "action" => "dms3subscribeLoadLists",
            "label" => __("Load lists and groups from MailChimp", 'DeMomentSomTres-MailChimp-Subscribe'),
            "wait_label" => __("Loading...", 'DeMomentSomTres-MailChimp-Subscribe'),
            "success_callback" => "dms3subscribeListsLoaded",
        ));
        $lists = $this->getLists();
        if ($lists):
            foreach ($lists as $l):
                $tabGrups->createOption(array(
                    "type" => "heading",
                    'name' => $l["name"] . " (" . $l["id"] . "-0-0)",
                ));
                $tabGrups->createOption(array(
                    "type" => "note",
                    "name" => __("Subscribers", 'DeMomentSomTres-MailChimp-Subscribe'),
                    'desc' => $l["subscribers"],
                    "id" => "subscribers-" . $l["id"] . "-0-0",
                ));
                $tabGrups->createOption(array(
                    "type" => "checkbox",
                    "name" => __("Can subscribe", 'DeMomentSomTres-MailChimp-Subscribe'),
                    "id" => "canSubscribe-" . $l["id"] . "-0-0",
                    "default" => false,
                ));
                $tabGrups->createOption(array(
                    "type" => "text",
                    "name" => __("Name to show", 'DeMomentSomTres-MailChimp-Subscribe'),
                    "id" => "displayName-" . $l["id"] . "-0-0",
                    "default" => $l["name"],
                ));
                $tabGrups->createOption(array(
                    "type" => "text",
                    "name" => __("Widget title", 'DeMomentSomTres-MailChimp-Subscribe'),
                    "id" => "widgetTitle-" . $l["id"] . "-0-0",
                    "default" => "",
                ));
                $tabGrups->createOption(array(
                    "type" => "select-categories",
                    "name" => __("Link to categories", 'DeMomentSomTres-MailChimp-Subscribe'),
                    "id" => "categories-" . $l["id"] . "-0-0",
                    "multiple" => true,
                ));
                $tabGrups->createOption(array(
                    'type' => "save",
                    'save' => __("Save Changes", 'DeMomentSomTres-Mailchimp-Subscribe'),
                    'use_reset' => false
                ));
                if (isset($l["interest-groups"])):
                    foreach ($l["interest-groups"] as $g):
                        foreach ($g["interests"] as $i):
                            $id = $l["id"] . "-" . $g["id"] . "-" . $i["id"];
                            $tabGrups->createOption(array(
                                "type" => "heading",
                                'name' => $l["name"] . "-" . $g["name"] . "-" . $i[name] . " (" . $id . ")",
                            ));
                            $tabGrups->createOption(array(
                                "type" => "note",
                                "name" => __("Subscribers", 'DeMomentSomTres-MailChimp-Subscribe'),
                                'desc' => $i["subscribers"],
                                "id" => "subscribers-" . $id,
                            ));
                            $tabGrups->createOption(array(
                                "type" => "checkbox",
                                "name" => __("Can subscribe", 'DeMomentSomTres-MailChimp-Subscribe'),
                                "id" => "canSubscribe-" . $id,
                                "default" => false,
                            ));
                            $tabGrups->createOption(array(
                                "type" => "text",
                                "name" => __("Name to show", 'DeMomentSomTres-MailChimp-Subscribe'),
                                "id" => "displayName-" . $id,
                                "default" => $l["name"],
                            ));
                            $tabGrups->createOption(array(
                                "type" => "text",
                                "name" => __("Widget title", 'DeMomentSomTres-MailChimp-Subscribe'),
                                "id" => "widgetTitle-" . $id,
                                "default" => "",
                            ));
                            $tabGrups->createOption(array(
                                "type" => "select-categories",
                                "name" => __("Link to categories", 'DeMomentSomTres-MailChimp-Subscribe'),
                                "id" => "categories-" . $id,
                                "multiple" => true,
                            ));
                            $tabGrups->createOption(array(
                                'type' => "save",
                                'save' => __("Save Changes", 'DeMomentSomTres-Mailchimp-Subscribe'),
                                'use_reset' => false
                            ));
                        endforeach;
                    endforeach;
                endif;
            endforeach;
        endif;
        $tabWidget = $panel->createTab(array(
            'name' => __('Widget', 'DeMomentSomTres-Mailchimp-Subscribe'),
            'title' => __('Widget', 'DeMomentSomTres-Mailchimp-Subscribe'),
            'id' => 'widget'
        ));
        $tabWidget->createOption(array(
            'name' => __("Widget button text", 'DeMomentSomTres-Mailchimp-Subscribe'),
            'id' => self::OPTION_WIDGETBUTTON,
            'type' => "text",
            "default" => "Subscribe",
        ));
        $tabWidget->createOption(array(
            'name' => __("Widget Error Message", 'DeMomentSomTres-Mailchimp-Subscribe'),
            'id' => self::OPTION_WIDGETERROR,
            'type' => "editor",
            "default" => "",
            "wpautop" => false,
            "media_buttons" => false,
            "rows" => 3,
        ));
        $tabWidget->createOption(array(
            'type' => "save",
            'save' => __("Save Changes", 'DeMomentSomTres-Mailchimp-Subscribe'),
            'use_reset' => false
        ));
    }

    /**
     * @since 2.0
     */
    function saveListsLastUpdate() {
        set_transient(self::TRANS_TIMESTAMP, current_time("mysql"));
    }

    /**
     * since 2.0
     * @return string A message showing the time since last lists update
     */
    function getListsLastUpdate() {
        if (false === $timestamp = get_transient(self::TRANS_TIMESTAMP)) {
            return __("As far as we know, lists have never been loaded. Please load lists and groups", 'DeMomentSomTres-MailChimp-Subscribe');
        }
        return __("List last update:", 'DeMomentSomTres-MailChimp-Subscribe') . " " . mysql2date("r", $timestamp, true);
    }

    /**
     * @since 2.0
     */
    function saveLists($lists) {
        //set_transient(self::TRANS_LISTS, json_encode($lists));
        set_transient(self::TRANS_LISTS, $lists);
    }

    /**
     * since 2.0
     * @return object the lists from mailchimp stored in WordPress
     */
    function getLists() {
        return get_transient(self::TRANS_LISTS);
    }

    /**
     * Loads the lists from mailchimp
     * @since 2.0
     */
    function ajax_loadLists() {
        $this->mailchimp_init();
        $lists = DeMomentSomTresMailchimp::MailChimpGetLists($this->mcSession, true);
        if ($lists):
            $this->saveListsLastUpdate();
            $this->saveLists($lists);
            wp_send_json_success(__("Lists loaded", 'DeMomentSomTres-MailChimp-Subscribe'));
        else:
            wp_send_json_error(__("An error happened", 'DeMomentSomTres-MailChimp-Subscribe'));
        endif;
    }

    /**
     * Since 2.0
     * @param type $container
     * @param type $activeTab
     * @param type $params
     * Saves a new options calculated from current options but optimized to read by categories
     */
    function tf_save_admin($container, $activeTab, $params) {
        $options = maybe_unserialize(get_option(DeMomentSomTresMailchimpSubscribe::DIRECTOPTIONS));
        $cats = array();
        foreach ($options as $k => $v):
            $prefix = substr($k, 0, 11);
            $suffix = str_replace($prefix, "", $k);
            if ($prefix === "categories-"):
                foreach ($v as $r):
                    if ($r !== ""):
                        if ($options["canSubscribe-" . $suffix]):
                            $cat = array(
                                "id" => $r,
                                "name" => str_replace("\'", "'", $options["displayName-" . $suffix]),
                                "widgetTitle" => str_replace("\'", "'", $options["widgetTitle-" . $suffix]),
                                "widgetButton" => str_replace("\'", "'", $options[DeMomentSomTresMailchimpSubscribe::OPTION_WIDGETBUTTON]),
                                "list" => $suffix,
                            );
                            $cats[$r] = $cat;
                        endif;
                    endif;
                endforeach;
            endif;
        endforeach;
        update_option(DeMomentSomTresMailchimpSubscribe::OPTIONSCACHE, $cats);
    }

    /**
     * @since 2.0
     * @return boolean
     */
    function register_widgets() {
        return register_widget("DeMomentSomTresMailchimpSubscribeWidget");
    }

    /**
     * @since 2.0
     * @return array the lists to which users can subscribe in format 'id' => 'name'
     */
    public static function validLists() {
        $lists = get_option(self::OPTIONSCACHE);
        $validLists = array();
        foreach ($lists as $id => $list):
            $validLists[$list["list"]] = $list['name'];
        endforeach;
        return $validLists;
    }

    /**
     * @since 2.0
     */
    public function jsRequired() {
        $this->loadJs = true;
    }

    /**
     * @since 2.0
     */
    function jsAdd() {
        $titan = TitanFramework::getInstance(self::OPTIONS);
        $ga = $titan->getOption(self::OPTION_GA);
        wp_enqueue_script('dms3mcsubscribe', plugin_dir_url(__FILE__) . 'js/dms3MCsubcribe.js', array('jquery'), '', true);
        $protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
        $params = array(
            'ajaxurl' => admin_url('admin-ajax.php', $protocol),
            'ga' => $ga,
        );
        wp_localize_script('dms3mcsubscribe', 'dms3mcsubscribe', $params);
    }

    /**
     * @since 2.0
     */
    function jsLoad() {
        if (!$this->loadJs):
            wp_deregister_script('dms3mcsubscribe');
        endif;
    }

    /**
     * @since 2.0
     */
    function jsAdmin() {
        wp_enqueue_script('dms3mcsubscribeadm', $this->pluginURL . 'js/admin.js', array('jquery'), '', true);
        wp_enqueue_style('dms3mcsubscribe', $this->pluginURL . 'css/admin.css');
    }

    /**
     * @since 1.0
     */
    function ajax_subscribe() {
        $email = $_REQUEST['email'];
        $id = $_REQUEST['id'];
        $result = $this->subscribe($email, $id);
        echo $result['message'];
        die();
    }

    /**
     * @since 2.0
     */
    function ajax_query_tr_subscribed($id, $name, $message = '') {
        $result .= "<td class='name'>$name</td>";
        $result .= "<td>" . __('Subscribed', 'DeMomentSomTres-MailChimp-Subscribe');
        if ('' != $message):
            $result .= "<span class='alert alert-danger'>$message.</span>";
        endif;
        $result .= "</td>";
        $result .= "<td>";
        $result .= "<div class='spinner fa fa-pulse fa-spinner' style='display:none;'></div>";
        $result .= "<input id='$id' class='btn btn-primary unsubscribe' type ='button' value='" . __('Unsubscribe', 'DeMomentSomTres-MailChimp-Subscribe') . "' />";
        $result .= "</td>";
        return $result;
    }

    /**
     * @since 2.0
     */
    function ajax_query_tr_notsubscribed($id, $name, $message = '') {
        $result .= "<td class='name'>$name</td>";
        $result .= "<td>" . __('Not subscribed', 'DeMomentSomTres-MailChimp-Subscribe');
        if ('' != $message):
            $result .= "<span class='alert alert-danger'>$message.</span>";
        endif;
        $result .= "</td>";
        $result .= "<td>";
        $result .= "<div class='spinner fa fa-pulse fa-spinner' style='display:none;'></div>";
        $result .= "<input id='$id' class='btn btn-primary subscribe' type ='button' value='" . __('Subscribe', 'DeMomentSomTres-MailChimp-Subscribe') . "' />";
        $result .= "</td>";
        return $result;
    }

    /**
     * @since 2.0
     */
    function ajax_query() {
        $email = $_REQUEST['email'];
        $lists = $this->validLists();
        $result = '';
        if ('' == trim($email)):
            echo "<p class='alert alert-danger'>";
            echo __('Email cannot be empty', 'DeMomentSomTres-MailChimp-Subscribe');
            echo "</p>";
            die();
        endif;
        $result .= "<h2>" . sprintf(__('Lists subscriptions for %s', 'DeMomentSomTres-MailChimp-Subscribe'), $email) . "</h2>";
        $result .= "<table class='table table-striped table-bordered table-hover'>";
        $result .= "<thead>";
        $result .= "<th>" . __('List', 'DeMomentSomTres-MailChimp-Subscribe') . "</th>";
        $result .= "<th>" . __('Status', 'DeMomentSomTres-MailChimp-Subscribe') . "</th>";
        $result .= "<th>" . __('Actions', 'DeMomentSomTres-MailChimp-Subscribe') . "</th>";
        $result .= "</thead><tbody>";
        foreach ($lists as $id => $name):
            $result .= "<tr id='$id' key='$email'>";
            list($listid, $groupingid, $groupid) = explode("-", $id);
            if ($this->userSubscribed($email, $listid, $groupingid, $groupid)):
                $result .= $this->ajax_query_tr_subscribed($id, $name);
            else:
                $result .= $this->ajax_query_tr_notsubscribed($id, $name);
            endif;
            $result .= "</tr>";
        endforeach;
        $result .= "</tbody></table>";
        echo $result;
        die();
    }

    /**
     * @since 2.0
     */
    function userSubscribed($email, $listid, $groupingid, $groupid) {
        $subscription = DeMomentSomTresMailchimp::MailChimpGetEmailListSubscription($this->mcSession, $email, $listid, TRUE);
        if (!$subscription->subscribedToList):
            $result = FALSE;
        else:
            if ($groupingid === "0"):
                $result = $subscription->subscribedToList;
            else:
                $result = array_key_exists($groupid, $subscription->interests);
            endif;
        endif;
        return $result;
    }

    /**
     * @since 2.0
     */
    function ajax_subscribe_button() {
        $email = $_REQUEST['email'];
        $id = $_REQUEST['id'];
        $name = stripslashes($_REQUEST['name']);
        $answer = $this->subscribe($email, $id);
        if ($answer['status'] == DeMomentSomTresMailchimp::MAILCHIMP_SUCCESS):
            $result = $this->ajax_query_tr_subscribed($id, $name);
        else:
            $result = $this->ajax_query_tr_notsubscribed($id, $name, $answer['message']);
        endif;
        echo $result;
        die();
    }

    /**
     * @since 2.0
     */
    function ajax_unsubscribe_button() {
        $email = $_REQUEST['email'];
        $id = $_REQUEST['id'];
        $name = stripslashes($_REQUEST['name']);
        $answer = $this->unsubscribe($email, $id);
        if ($answer['status'] == DeMomentSomTresMailchimp::MAILCHIMP_SUCCESS):
            $result = $this->ajax_query_tr_notsubscribed($id, $name);
        else:
            $result = $this->ajax_query_tr_subscribed($id, $name, $answer['message']);
        endif;
        echo $result;
        die();
    }

    /**
     * @since 2.0
     */
    function subscribe($email, $id) {
        $titan = TitanFramework::getInstance(self::OPTIONS);
        $status = DeMomentSomTresMailchimp::MAILCHIMP_SUCCESS;
        $result = '';
        if ('' == trim($email)):
            $result .= __('Email cannot be empty', 'DeMomentSomTres-MailChimp-Subscribe');
            $status = DeMomentSomTresM::MAILCHIMP_ERROR;
        else:
            $error = $titan->getOption(self::OPTION_WIDGETERROR);
            if (!$error):
                $error = sprintf(__('Error while trying to subscribe %s,', 'DeMomentSomTres-MailChimp-Subscribe'), $email);
            endif;
            list($listid, $groupingid, $groupid) = explode("-", $id);
            $interests = [];
            if ($groupid != 0):
                $interests[$groupid] = true;
            endif;
            $subscription = DeMomentSomTresMailchimp::SubscribeToList($this->mcSession, $listid, $email, $interests);
            if ($groupid == 0):
                if (isset($subscription->id)):
                    $result .= sprintf(__('The email address %s has been subscribed', 'DeMomentSomTres-MailChimp-Subscribe'), $email);
                else:
                    $result .= $error;
                    $status = DeMomentSomTresMailchimp::MAILCHIMP_ERROR;
                endif;
            else:
                if (!$subscription->interests->$groupid):
                    $result .= $error;
                    $status = DeMomentSomTresMailchimp::MAILCHIMP_ERROR;
                else:
                    $result .= sprintf(__('The email address %s has been subscribed', 'DeMomentSomTres-MailChimp-Subscribe'), $email);
                endif;
            endif;
        endif;
        return array(
            'status' => $status,
            'message' => $result
        );
    }

    /**
     * @since 2.0
     */
    function unsubscribe($email, $id) {
        $status = DeMomentSomTresMailchimp::MAILCHIMP_SUCCESS;
        $result = '';
        if ('' == trim($email)):
            $result .= __('Email cannot be empty', 'DeMomentSomTres-MailChimp-Subscribe');
            $status = DeMomentSomTresMailchimp::MAILCHIMP_ERROR;
        else:
            list($listid, $groupingid, $groupid) = explode("-", $id);
            if ($groupid == "0"):
                // Unsubscribe from a list
                $unsubscribe = DeMomentSomTresMailchimp::UnsubscribeFromList($this->mcSession, $listid, $email);
                if ($unsubscribe == DeMomentSomTresMailchimp::MAILCHIMP_SUCCESS):
                    $result .= __('Unsubscribed', 'DeMomentSomTres-MailChimp-Subscribe');
                else:
                    $result .= __('An error happened while unsubscribing', 'DeMomentSomTres-MailChimp-Subscribe');
                    $status = DeMomentSomTresMailchimp::MAILCHIMP_ERROR;
                endif;
            else:
                // Unsubscribe from a group
                $interests = [];
                $interests[$groupid] = false;
                $subscription = DeMomentSomTresMailchimp::SubscribeToList($this->mcSession, $listid, $email, $interests);
                if ($subscription->interests->$groupid):
                    $result .= __('An error happened while unsubscribing', 'DeMomentSomTres-MailChimp-Subscribe');
                    $status = DeMomentSomTresMailchimp::MAILCHIMP_ERROR;
                else:
                    $result .= __('Unsubscribed', 'DeMomentSomTres-MailChimp-Subscribe');
                endif;
            endif;
        endif;
        return array(
            'status' => $status,
            'message' => $result
        );
    }

    /**
     * @since 1.0
     */
    function shortcode_general($atts) {
        $titan = TitanFramework::getInstance(self::OPTIONS);
        $atts = shortcode_atts(array(), $atts);
        $this->jsRequired();
        $form = '';
        $form .= "<form action='#' method='post' id='dms3MCsubscribeGeneral' class='dms3MCsubscribeGeneral'>";
        $form .= "<div class='fase1'>";
        $form .= "    <label for='email'>" . __('Your email:', 'DeMomentSomTres-MailChimp-Subscribe') . "</label>";
        $form .= "    <input class='widefat' id='email' name='email' type='text' value='' placeholder='" . __('Your email', 'DeMomentSomTres-MailChimp-Subscribe') . "'/>";
        $form .= "    <input class='btn btn-primary' type='submit' value='" . __('Verify subscriptions', 'DeMomentSomTres-MailChimp-Subscribe') . "' ></input>";
        $form .= "    <div class='spinner-outer'><div class='spinner fa fa-2x fa-pulse fa-spinner' style='display:none'></div></div>";
        $form .= "</div>";
        $form .= "<p>" . __('If you put your email and click on &apos;Verify subscriptions&apos; you will see all your subscriptions and you could manage them.', 'DeMomentSomTres-MailChimp-Subscribe') . "</p>";
        $form .= "<div class='fase2'>";
        if ($titan->getOption(self::OPTION_INITIALLIST)):
            $lists = $this->validLists();
            $form .= "<h3>" . __('Lists subscriptions', 'DeMomentSomTres-MailChimp-Subscribe') . "</h3>";
            $form .= "<table class='table table-striped table-bordered table-hover'>";
            $form .= "<thead>";
            $form .= "<th>" . __('List', 'DeMomentSomTres-MailChimp-Subscribe') . "</th>";
            $form .= "<th>" . __('Status', 'DeMomentSomTres-MailChimp-Subscribe') . "</th>";
            $form .= "<th>" . __('Actions', 'DeMomentSomTres-MailChimp-Subscribe') . "</th>";
            $form .= "</thead><tbody>";
            foreach ($lists as $id => $name):
                $form .= "<tr id='$id''>";
                $form .= "<td>$name</td>";
                $form .= "<td>&nbsp;</td>";
                $form .= "<td>&nbsp;</td>";
                $form .= "</tr>";
            endforeach;
            $form .= "</tbody></table>";
        endif;
        $form .= "</div>";
        $form .= "</form>";
        return $form;
    }

}

/**
 * @since 1.0
 */
class DeMomentSomTresMailchimpSubscribeWidget extends WP_Widget {

    /**
     * @since 1.0
     */
    function __construct() {
        $widget_ops = array(
            'classname' => 'DMS3-mc-subscribe',
            'description' => __('Manages subscritions to lists and groups', 'DeMomentSomTres-MailChimp-Subscribe')
        );
        $this->WP_Widget('DeMomentSomTresMCSubscribe', __('Subscribe and unsubscribe from a list', 'DeMomentSomTres-MailChimp-Subscribe'), $widget_ops);
    }

    /**
     * @since 1.0
     */
    function form($instance) {
        global $demomentsomtres_mc_subscribe;
        $demomentsomtres_mc_subscribe->jsRequired();
        $title = esc_attr($instance['title']);
        $list = $instance['list'];
        $html = $instance['html'];
        $htmlbottom = $instance['htmlbottom'];
        $button = isset($instance['button']) ? $instance['button'] : __('Subscribe me', 'DeMomentSomTres-MailChimp-Subscribe');
        ?>
        <p>
            <?php echo __("All parameters are set via Settings", TEXTDOMAIN); ?>
        </p>
        <?php
    }

    /**
     * @since 1.0
     */
    function update($new_instance, $old_instance) {
        $new_instance['title'] = strip_tags($new_instance['title']);
        return $new_instance;
    }

    /**
     * @since 1.0
     */
    function widget($args, $instance) {
        global $demomentsomtres_mc_subscribe;
        extract($args);
        extract($instance);
        $options = maybe_unserialize(get_option(DeMomentSomTresMailchimpSubscribe::OPTIONSCACHE));
        $queriedObject = get_queried_object_id();
        if (array_key_exists($queriedObject, $options)):
            $params = $options[$queriedObject];
            echo $before_widget;
            $title = apply_filters('widget_title', $params['widgetTitle']);
            if ($title)
                echo $before_title . $title . $after_title;
            ?>
            <form action="#" method="post" id="form-<?php echo $this->id; ?>" class="dms3MCsubscribe">
                <input type="hidden" name="id" id="<?php echo $this->get_field_id('id'); ?>" value="<?php echo $params["list"]; ?>"/>
                <label for="<?php echo $this->get_field_id('email'); ?>"><?php _e('Your email:', 'DeMomentSomTres-MailChimp-Subscribe'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id('email'); ?>" name="email" type="text" value="" placeholder="<?php _e('Your email', 'DeMomentSomTres-MailChimp-Subscribe'); ?>" />
                <input class="btn btn-primary" type="submit" value="<?php echo $params["widgetButton"]; ?>" />
                <div class="spinner-outer"><div class="spinner fa fa-2x fa-spinner fa-pulse" style="display: none;"></div></div>
                <div class="messages"></div>
            </form>
            <?php
            if ($htmlbottom)
                echo $htmlbottom;
            echo $after_widget;
        endif;
    }

}
?>