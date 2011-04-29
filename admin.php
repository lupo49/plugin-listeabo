<?php
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'admin.php');
 
/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_listeabo extends DokuWiki_Admin_Plugin {
 		var $cmd;

    /**
     * Constructor
     */
    function admin_plugin_listeabo() {
        $this->setupLocale();
    }

    /**
     * return some info
     */
    function getInfo() {
        return array(
            'author' => 'Etienne M.',
            'email'  => 'emauvaisfr@yahoo.fr',
            'date'   => @file_get_contents(DOKU_PLUGIN.'listeabo/VERSION'),
            'name'   => 'Abonnements / Subscriptions',
            'desc'   => 'Affiche tous les abonnements actifs / Displays all active subscriptions',
            'url'    => 'http://www.dokuwiki.org/fr:plugin:listeabo',
        );
    }
 
    /**
     * return prompt for admin menu
     */
    function getMenuText($language) {
      if (!$this->disabled)
        return parent::getMenuText($language);
      return '';
    }
                                                    
    /**
     * return sort order for position in admin menu
     */
    function getMenuSort() {
        return 5000;
    }
 
    /**
     * handle user request
     */
    function handle() {
    }
 
    /**
     * output appropriate html
     */
    function html() {
        //Bourrin... Voir plutot avec la fonction handle(), un jour...
        print '<script>document.location.search="do=listeabo&admin=true";</script>';
    }
}
// vim:ts=4:sw=4:et:enc=utf-8:
