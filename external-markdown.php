<?php

/**
 * Plugin Name: External Markdown
 * Author:      Moritz Stueckler
 * Description: Include and parse markdown files from external web sources like GitHub, GitLab, etc.
 * Plugin URI:  https://github.com/pReya/wordpress-external-markdown
 * Version:     0.0.1
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

function external_markdown_shortcode($atts = array())
{
  $GITHUB_MARKDOWN_API = "https://api.github.com/markdown";
  $MARKDOWN_EXAMPLE = "https://raw.githubusercontent.com/pReya/wordpress-external-markdown/main/README.md";
  // 1 hour = 60s * 60
  $DEFAULT_CACHE_TTL = strval(60 * 60);

  extract(shortcode_atts(array(
    'url' => $MARKDOWN_EXAMPLE,
    'class' => 'external-markdown',
    'ttl' => $DEFAULT_CACHE_TTL
  ), $atts));

  // TTL != 0 means caching is enabled
  if ($ttl !== strval(0)) {
    $cache_key = "external_markdown_" . md5($url . $class . $ttl);
    $cached = get_transient($cache_key);
  }

  // Cache miss or cache disabled
  if (!(isset($cached)) || ($cached === false)) {
    $fetch_content = wp_remote_get($url);
    $content_response_body = wp_remote_retrieve_body($fetch_content);
    $content_response_code = wp_remote_retrieve_response_code($fetch_content);

    if ($content_response_code != 200) {
      return "<strong>Plugin Error:</strong> Could not fetch external markdown source.";
    }

    $args = array(
      'body' => json_encode(array(
        "text" => $content_response_body
      )),
      'headers' => array(
        'Content-Type' => 'application/json'
      )
    );

    $fetch_github = wp_remote_post($GITHUB_MARKDOWN_API, $args);
    $github_response_body = wp_remote_retrieve_body($fetch_github);
    $github_response_code = wp_remote_retrieve_response_code($fetch_github);

    if ($github_response_code != 200) {
      return "<strong>Plugin Error:</strong> Could not fetch converted markdown file.";
    }

    $html_string = '<div class="' . $class . '">' . $github_response_body . '</div>';

    if ($ttl != 0) {
      set_transient($cache_key, $html_string, $ttl);
    }

    return $html_string;
  } else {
    // Cache hit
    return $cached;
  }
}

add_shortcode('external_markdown', 'external_markdown_shortcode');
