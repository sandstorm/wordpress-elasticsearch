<?php
/*
Plugin Name: Elasticsearch
Plugin URI: http://sandstorm/elasticsearch
Description: Elastic Search integration plugin, pushing your blog articles to ElasticSearch
Version: 0.2
Author: Sebastian Kurfürst
Author URI: http://sandstorm-media.de
License: GPL2
*/
/*  Copyright 2011 Sebastian Kurfürst

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once(__DIR__ . '/guzzle.phar');
require_once(__DIR__ . '/ob_settings.php');

class SandstormMedia_ElasticSearch {
	protected static $fieldsToIndex = array(
		'post_date' => 'date',
		'post_content' => 'content',
		'post_title' => 'title',
		'post_excerpt' => 'excerpt'
	);

	protected static $elasticSearchConnection = NULL;

	protected static function getElasticSearchConnection() {
		if (self::$elasticSearchConnection === NULL) {
			$elasticSearchUrlSetting = get_option('sandstormmedia_elasticsearch_elasticSearchUri');

			self::$elasticSearchConnection = new \Guzzle\Service\Client($elasticSearchUrlSetting['text_string']);
		}
		return self::$elasticSearchConnection;
	}

	public static function indexPost($postId) {
		$documentToIndex = array();
		if (is_object($postId)) {
			$post = $postId;
			$postId = $post->ID;
		} else {
			$post = get_post($postId);
		}

		foreach (self::$fieldsToIndex as $fieldName => $nameInIndex) {
			$documentToIndex[$nameInIndex] = $post->$fieldName;
		}

		$documentToIndex['uri'] = get_permalink($postId);

		$request = self::getElasticSearchConnection()->put('{{postId}}', array('postId' => $postId));
		$request->setBody(json_encode($documentToIndex));
		$request->send();
	}

	public static function indexAll() {
		$posts = get_posts();
		foreach ($posts as $post) {
			self::indexPost($post);
		}
	}
}
add_action('publish_post', array('SandstormMedia_ElasticSearch', 'indexPost'));

if (get_option('sandstormmedia_elasticsearch_rebuild')) {
	SandstormMedia_ElasticSearch::indexAll();
	update_option('sandstormmedia_elasticsearch_rebuild', '');
}
?>