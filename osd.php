<?php 
/*
Plugin Name: Open Search Document Maker
Plugin URI: http://wordpress.org/extend/plugins/open-search-document/
Description: Create an Open Search Document for your blog.
Version: 1.0
Author: XBA, Matthias Pfefferle
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
}

class OpenSearchDocument {
	function add_query_vars( $vars ) {
		$vars[] = 'opensearch';
		return $vars;
	}
	
	function rewrite_add_rule( $wp_rewrite ) {
		global $wp_rewrite;
		$new_rules = array(
			'osd.xml'		 => 'index.php?opensearch=true',
			'opensearch.xml' => 'index.php?opensearch=true',
      		'opensearch' 	 => 'index.php?opensearch=true',
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
	
	
	function execute_request() {
		global $wp;
		#$wp->parse_request();
		if( $wp->query_vars['opensearch'] ) { 
			header('Content-Type: application/opensearchdescription+xml');
			header('Encoding:utf-8');
			echo '<?xml version="1.0" encoding="UTF-8"?>';
	?>
	<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
	<ShortName><?php bloginfo('name'); ?></ShortName>
	<Description><?php bloginfo('description'); ?></Description>
	<Url type="text/html" method="get" template="<?php bloginfo('url'); ?>/?s={searchTerms}"></Url>
	<Url type="application/atom+xml" template="<?php bloginfo('atom_url'); ?>?s={searchTerms}"/>
   	<Url type="application/rss+xml" template="<?php bloginfo('rss2_url'); ?>?s={searchTerms}"/>
	<Contact><?php bloginfo('admin_email'); ?></Contact>
	<LongName>Search through <?php bloginfo('name'); ?></LongName>
	<Tags>wordpress blog</Tags>
	<Image height="16" width="16" type="image/x-icon"><?php bloginfo('url'); ?>/favicon.ico</Image>
	<Image height="16" width="16" type="image/vnd.microsoft.icon"><?php bloginfo('url'); ?>/favicon.ico</Image>
	<Query role="example" searchTerms="blog"/>
	<Developer>XSD, Matthias Pfefferle</Developer>
	<Attribution>
	    Search data copyright 2009, <?php bloginfo('name'); ?>, Some Rights Reserved. CC by-nc 2.5.
	</Attribution>
	<Language><?php bloginfo('language'); ?></Language>
	<OutputEncoding>UTF-8</OutputEncoding>
	<InputEncoding><?php bloginfo('charset'); ?></InputEncoding>
	</OpenSearchDescription><?php
			exit;
		}
		else return;
	}
	function display_in_header() {
		echo '<link rel="search" type="application/opensearchdescription+xml" title="'. get_bloginfo('name') .'" href="'.OpenSearchDocument::get_url().'" />'."\n";
	}

	function head_profile($profiles) {
		$profiles[] = 'http://a9.com/-/spec/opensearch/1.1/';
		return $profiles;
	}
	
	/**
	 * Contribute the Search to XRDS-Simple.
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
}
?>