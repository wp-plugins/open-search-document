<?php 
/*
Plugin Name: OSD Maker
Plugin URI: http://www.john-noone.com
Description: Create an Open Search Document for your blog.
Version: 0.1
Author: XBA
Author URI: http://www.john-noone.com
*/

/*  Copyright 2006  XBA  (email : clint_northwood@the-fastest.net)

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

$xba_osd = new xba_osd();

class xba_osd {
	function xba_osd() {
		add_filter('query_vars', array(&$this, 'add_query_vars'));
		add_filter('rewrite_rules_array', array(&$this, 'rewrite_add_rule'));
		add_action('template_redirect', array(&$this, 'execute_request'));
		add_action('wp_head', array(&$this, 'display_in_header'));
		add_filter('head_profile', array(&$this, 'head_profile'));
	}

	function add_query_vars( $vars ) {
		$vars[] = 'request_osd';
		return $vars;
	}
	
	function rewrite_add_rule( $rules ) {
		global $wp_rewrite;
		$url = get_bloginfo('url');
		$rules['osd[.]xml$'] = 'index.php?request_osd=true';
		return $rules;
	}
	
	function execute_request() {
		global $wp;
		#$wp->parse_request();
		if( $wp->query_vars['request_osd'] ) { 
			header('Content-Type: text/xml');
			header('Encoding:utf-8');
			echo '<?xml version="1.0" encoding="UTF-8"?>';
	?>
	<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearchdescription/1.1/">
	<ShortName><?php bloginfo('name'); ?></ShortName>
	<Description>Chercher des informations sur le site <?php bloginfo('name'); ?></Description>
	<Url type="text/html" method="get" template="<?php bloginfo('url'); ?>/?s={searchTerms}"></Url>
	<Contact><?php bloginfo('admin_email'); ?></Contact>
	<LongName>Chercher sur <?php bloginfo('name'); ?></LongName>
	<Tags>wordpress blog</Tags>
	<Image height="32" width="32" type="image/png"><?php bloginfo('url'); ?>/favicon.png</Image>
	<Image height="16" width="16" type="image/png"><?php bloginfo('url'); ?>/favicon.png</Image>
	<Image height="32" width="32" type="image/vnd.microsoft.icon"><?php bloginfo('url'); ?>/favicon.ico</Image>
	<Image height="16" width="16" type="image/vnd.microsoft.icon"><?php bloginfo('url'); ?>/favicon.ico</Image>
	<Query role="example" searchTerms="blog"/>
	<Developer>XBA</Developer>
	<Attribution>
	    Search data copyright 2007, <?php bloginfo('name'); ?>, Some Rights Reserved. CC by-nc 2.5.
	</Attribution>
	<SyndicationRight>open</SyndicationRight>
	<AdultContent>false</AdultContent>
	<Language>fr</Language>
	<OutputEncoding>UTF-8</OutputEncoding>
	<InputEncoding><?php bloginfo('charset'); ?></InputEncoding>
	</OpenSearchDescription><?php
			exit;
		}
		else return;
	}
	function display_in_header() {
		echo '<link rel="search" type="application/opensearchdescription+xml" title="'. get_bloginfo('name') .'" href="'. get_bloginfo('url') .'/osd.xml" />'."\n";
	}
	function head_profile($profiles) {
		$profiles[] = 'http://a9.com/-/spec/opensearch/1.1/';
		return $profiles;
	}
}

if(!function_exists('head_profile')) {
	function head_profile() {
		$profiles[] = 'http://gmpg.org/xfn/11';
		$profiles = apply_filters('head_profile', $profiles);
		echo implode(" ", $profiles);
	}
}
?>