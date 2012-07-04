<?php
/*
 Plugin Name: Open Search Document Maker
 Plugin URI: http://wordpress.org/extend/plugins/open-search-document/
 Description: Create an Open Search Document for your blog.
 Version: 1.3
 Author: XBA, pfefferle
 Author URI: http://wordpress.org/extend/plugins/open-search-document/
 */

/*  Copyright 2006  XBA, Matthias Pfefferle

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// register
if (isset($wp_version)) {
  add_filter('query_vars', array('OpenSearchDocument', 'add_query_vars'));
  add_filter('generate_rewrite_rules', array('OpenSearchDocument', 'rewrite_add_rule'));
  add_action('parse_query', array('OpenSearchDocument', 'execute_request'));
  add_action('wp_head', array('OpenSearchDocument', 'display_in_header'));
  add_filter('get_profile_uri', array('OpenSearchDocument', 'head_profile'));
  add_filter('xrds_simple', array('OpenSearchDocument', 'xrds_simple'));
  add_filter('host_meta', array('OpenSearchDocument', 'add_xrd'));
  add_filter('webfinger', array('OpenSearchDocument', 'add_xrd'));
  register_activation_hook(__FILE__, array('OpenSearchDocument', 'activation_hook'));
  
  // add feed autodiscovery
  add_action('atom_ns', array('OpenSearchDocument', 'atom_namespace'));
  add_action('atom_head', array('OpenSearchDocument', 'display_in_atom_header'));
  add_action('rss2_head', array('OpenSearchDocument', 'display_in_rss_header'));

  // add profile-uris
  add_filter('profile_uri', array('OpenSearchDocument', 'profile_uri'));
}

/**
 * open search document for wordpress
 *
 * @author Matthias Pfefferle
 * @author XBA
 */
class OpenSearchDocument {

  /**
   * add open-search query var
   *
   * @param array $vars query vars
   * @return array updated query vars
   */
  function add_query_vars( $vars ) {
    $vars[] = 'opensearch';
    $vars[] = 'opensearch-suggestions';
    return $vars;
  }
  
  /**
   * activation hook
   */
  function activation_hook() {
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
  }

  /**
   * add some open-search rewrite rules
   *
   * @param array $wp_rewrite array of rewrite rules
   */
  function rewrite_add_rule( $wp_rewrite ) {
    global $wp_rewrite;
    $new_rules = array(
      'osd.xml'    => 'index.php?opensearch=true',
      'opensearch.xml' => 'index.php?opensearch=true',
      'opensearch'   => 'index.php?opensearch=true',
    );
    $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
  }

  /**
   * Get the URL for the open-search-document, based on the blog's permalink settings.
   *
   * @return string open-search-document URL
   */
  function get_url() {
    global $wp_rewrite;

    $url = trailingslashit(get_option('home'));
    if($_SERVER['HTTPS'])
      $url = preg_replace('/^http:/', 'https:', $url);

    if ($wp_rewrite->using_permalinks()) {
      if ($wp_rewrite->using_index_permalinks()) {
        return $url . 'index.php/opensearch';
      } else {
        return $url . 'opensearch';
      }
    } else {
      return add_query_arg('opensearch', true, $url);
    }
  }

  /**
   * 
   *
   */
  function execute_request() {
    global $wp;

    if( $wp->query_vars['opensearch'] ) {
      OpenSearchDocument::print_xml();
    } else if ( $wp->query_vars['opensearch-suggestions'] ) {
      $tags = array();
      $output = array();
      foreach (get_tags('search='.$wp->query_vars['opensearch-suggestions']) as $tag) {
        $tags[] = $tag->name;
      }
      
      $output[] = $wp->query_vars['opensearch-suggestions'];
      $output[] = $tags;
      
      header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
      echo json_encode($output);
      exit;
    } else {
      return;
    }
  }
  
  /**
   * function to render the open-search document
   *
   */
  function print_xml() {
    global $wp_rewrite;
    
    if ($wp_rewrite->using_permalinks()) {
      $joiner = "?";
    } else {
      $joiner = "&amp;";
    }

    header('Content-Type: application/opensearchdescription+xml');
    header('Encoding:utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
  ?>
  <OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
    <ShortName><?php bloginfo('name'); ?></ShortName>
    <Description><?php bloginfo('description'); ?></Description>
    <Url
      type="text/html" method="get"
      template="<?php bloginfo('url'); ?>/?s={searchTerms}"></Url>
    <Url
      type="application/atom+xml"
      template="<?php bloginfo('atom_url'); ?><?php echo $joiner ?>s={searchTerms}" />
    <Url
      type="application/rss+xml"
      template="<?php bloginfo('rss2_url'); ?><?php echo $joiner ?>s={searchTerms}" />
    <Url
      type="application/x-suggestions+json"
      template="<?php bloginfo('url'); ?>/?opensearch-suggestions={searchTerms}"/>
    <Contact><?php bloginfo('admin_email'); ?></Contact>
    <LongName>Search through <?php bloginfo('name'); ?></LongName>
    <Tags>wordpress blog</Tags>
    <Image height="16" width="16" type="image/x-icon"><?php bloginfo('url'); ?>/favicon.ico</Image>
    <Image height="16" width="16" type="image/vnd.microsoft.icon"><?php bloginfo('url'); ?>/favicon.ico</Image>
    <Query role="example" searchTerms="blog" />
    <Developer>XSD, Matthias Pfefferle</Developer>
    <Language><?php bloginfo('language'); ?></Language>
    <OutputEncoding>UTF-8</OutputEncoding>
    <InputEncoding><?php bloginfo('charset'); ?></InputEncoding>
  </OpenSearchDescription>
  <?php
    exit;
  }

  /**
   * contribute the open-search autodiscovery-header
   *
   */
  function display_in_header() {
    echo '<link rel="search" type="application/opensearchdescription+xml" title="'. get_bloginfo('name') .'" href="'.OpenSearchDocument::get_url().'" />'."\n";
  }

  /**
   * contribute the open-search atom-autodiscovery header
   *
   */
  function display_in_atom_header() {
    echo '<link rel="search"
           href="'.OpenSearchDocument::get_url().'"
           type="application/opensearchdescription+xml"
           title="Content Search" />';
  }

  /**
   * contribute the open-search rss-autodiscovery header
   *
   */
  function display_in_rss_header() {
    echo '<atom:link rel="search"
           href="'.OpenSearchDocument::get_url().'"
           type="application/opensearchdescription+xml"
           title="Content Search" />';
  }

  /**
   * Contribute the open-search atom-namespace
   *
   */
  function atom_namespace() {
    echo ' xmlns:opensearch="http://a9.com/-/spec/opensearch/1.1/" '."\n";
  }

  /**
   * Contribute the open-search rss-namespace
   *
   */
  function rss_namespace() {
    echo ' xmlns:atom="http://www.w3.org/2005/Atom" '."\n";
  }

  /**
   * contribute the OpenSearch to XRDS-Simple.
   *
   * @param array $xrds current XRDS-Simple array
   * @return array updated XRDS-Simple array
   */
  function xrds_simple($xrds) {
    $xrds = xrds_add_service($xrds, 'main', 'OpenSearchDocument',
      array(
        'Type' => array( array('content' => 'http://a9.com/-/spec/opensearch/1.1/') ),
        'MediaType' => array( array('content' => 'application/opensearchdescription+xml') ),
        'URI' => array( array('content' => OpenSearchDocument::get_url()) )
      )
    );
    return $xrds;
  }

  /**
   * contribute the open-search profile uri
   *
   */
  function profile_uri($profiles) {
    $profiles[] = ' http://a9.com/-/spec/opensearch/1.1/ ';
    return $profiles;
  }
  
  /**
   * add the host meta information
   */
  function add_xrd($array) {     
    $array["links"][] = array("rel" => "http://a9.com/-/spec/opensearch/1.1/", "href" => OpenSearchDocument::get_url(), "type" => "application/opensearchdescription+xml");

    return $array;
  }
}
?>