<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Etienne Mauvais <emauvaisfr@yahoo.fr>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_listeabo extends DokuWiki_Action_Plugin {

    /**
     * return some info
     */
    function getInfo() {
        return array(
                'author' => 'Etienne M.',
                'email'  => 'emauvaisfr@yahoo.fr',
                'date'   => @file_get_contents(DOKU_PLUGIN.'listeabo/VERSION'),
                'name'   => 'listeabo Plugin',
                'desc'   => 'Affiche la liste des abonnements d\'un utilisateur / Displays the subscription list of a user',
                'url'    => 'http://www.dokuwiki.org/fr:plugin:listeabo',
                );
    }

    /**
     * Constructor
     */
    function action_plugin_listeabo() {
      $this->setupLocale();
    }
                              
    /**
     * register the eventhandlers
     */
    function register(&$contr) {
        $contr->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, '_handle_act', array());
        $contr->register_hook('TPL_ACT_UNKNOWN', 'BEFORE', $this, '_handle_tpl_act', array());
    }

    /**
     * catch listeabo action
     *
     * @author Etienne Mauvais <emauvaisfr@yahoo.fr>
     */
    function _handle_act(&$event, $param) {
        if($event->data != 'listeabo') return;
        $event->preventDefault();
    }

    function _handle_tpl_act(&$event, $param) {
        global $lang;
        global $INFO;
        global $conf;
        global $auth;
        $nbAbo = 0;
        $nbPages = 0;
        $nbCat = 0;

        if($event->data != 'listeabo') return;
        $event->preventDefault();

        // Search for *.mlist files in our data/meta-directory
        $pages = $this->list_mlist($conf['savedir'].'/meta',true, $pages);
        
        // Check if current user belongs to the manager group
        if (auth_ismanager($INFO['client'])) {
          $admin = trim($_REQUEST['admin']);  // If set to true, an admin wants to see all subscriptions
          $user = trim($_REQUEST['user']);
        }
        
        if (!$user) $user = $INFO['client'];
        // Fetch fullname of the user 
        $userName = $auth->getUserData($user);
        $userName = $userName['name'];
        // Use loginname if no userdetails were found
        if (!$userName) $userName = $user;

        // Search in every found mlist file
        foreach( (is_array($pages) ? $pages : array()) as $page) {
          // Save filename without extension into $page2
          preg_match("/meta\/(.*).mlist$/", $page, $page2);
          // replace slash with colon namespace seperator
          $page2 = preg_replace("/\//", ":", $page2[1]);
                                                  
          // Read content from file
          foreach(file($page) as $nom) {
            // $nom holds the current line
            $nom = chop($nom);
            
            // Discard digest information of the line
            $digest = strpos($nom, ' ');
            if($digest) $nom = substr($nom, 0, $digest);

            if ($nom == $user || $admin) {
              if (!is_array($mespages) || !in_array($page2, $mespages)) $mespages[] = $page2;
              if ($admin) $abonnes[$page2][] = $nom;
              else break;
            }
          }
        }

        if ($admin) print '<h1>'.$this->getLang('abo_aboactifs').'</h1>' . DOKU_LF;
        else        print '<h1>'.$this->getLang('abo_abode').' '.$userName.'</h1>' . DOKU_LF;

        if(!empty($mespages)) {
          $pagelist = plugin_load('helper', 'pagelist');
          print "<table>";

          foreach($mespages as $page) {
            if (!$pagelist) {
              $titrePage = explode(":",$page);
              $titrePage = $titrePage[sizeof($titrePage) - 1];
              $titrePage = str_replace('_', ' ', $titrePage);
            }
            else {
              $pagelist->page['id'] = $page;
              $pagelist->page['exists'] = 1;
              $pagelist->_meta = NULL;
              $titrePage = $pagelist->_getMeta('title');
              if (!$titrePage) $titrePage = str_replace('_', ' ', noNS($page));
              $titrePage = hsc($titrePage);
            }
            $lien = "<a href='doku.php?id=".$page."' class='wikilink1' style='font-weight: lighter;' title='".$page."'>$titrePage</a>";
            
            print "<tr><td nowrap><ul><li><div class=\"li\">";
            if ($titrePage) {
              print "$lien";
              $nbPages++;
            }
            else {
              print $this->getLang('abo_cat')." \"".preg_replace("/:$/", "", $page)."\"";
              $nbCat++;
            }
            print "</li></div></ul></td>";

            if ($admin) {
              sort($abonnes[$page]);
              $abonnesMEF="";
              foreach ($abonnes[$page] as $abonne) {
                $tmp=$auth->getUserData($abonne);
                if ($tmp) $abonnesMEF[]='<a href="?do=listeabo&user='.$abonne.'" title="'.$this->getLang('abo_abode').' '.$tmp['name'].'">'.$tmp['name'].'</a>';
                else $abonnesMEF[]=$abonne;
                $nbAbo++;
              }
              print "<td nowrap style=\"padding-left:10px;\" valign=\"top\"><div>".join(", ", $abonnesMEF);
            }
            else {
              print "<td nowrap style=\"padding-left:10px;\" align=\"right\"><div class=\"bar\" id=\"bar__bottom\" style=\"border:none;\">";
              if ($titrePage) {
                if ($user == $INFO['client']) 
                  tpl_link(wl($page, 'do=unsubscribe'),
                              $pre.(($inner)?$inner:$lang['btn_unsubscribe']).$suf,
                              'class="action unsubscribe" rel="nofollow"');
              }
              else {
                if ($user == $INFO['client'])
                  tpl_link(wl($page,'do=unsubscribens'),
                              $pre.(($inner)?$inner:$lang['btn_unsubscribens']).$suf,
                              'class="action unsubscribens" rel="nofollow"');
              }
              $nbAbo++;
            }
            print "</div></td></tr>";
          }
          print "</table>";

          print "$nbAbo ".$this->getLang('abo_cptabo')." ".$this->getLang('abo_cptdans')." $nbPages ".$this->getLang('abo_cptpages')." ".$this->getLang('abo_cptet')." $nbCat ".$this->getLang('abo_cptcat').".<br /><br />";
        }
        else {
            print '<div class="level1"><p>'.$this->getLang('abo_noabo').'.</p></div>';
            print '<div class="level1"><p>'.$this->getLang('abo_whoto').'</p></div>';
        }
        
        if (auth_ismanager($INFO['client'])) {
          if (!$admin) print $this->getLang('abo_estadmin').'<a href="?do=listeabo&admin=true" class="wikilink">'.$this->getLang('abo_voirtousabo').'</a>.<br />';
          if ($admin || $user != $INFO['client']) print '<a href="?do=listeabo" class="wikilink">'.$this->getLang('abo_voirvosabo').'</a>.';
        }
    }

    function list_mlist($dir, $recursive=false, $files=0) {
      static $files;

      if(is_dir($dir)) {
        if($dh = opendir($dir)) {
          while(($file = readdir($dh)) !== false) {
            if($file != "." && $file != "..") {
              if (is_dir($dir."/".$file)) 
                $this->list_mlist($dir."/".$file, $recursive, $files);
              else if (preg_match("/\.mlist$/", $file)) 
                $files[] =$dir."/".$file;
            }
          }
          closedir($dh);
        }
      }
      return $files;
    }

}

// vim:ts=4:sw=4:et:
