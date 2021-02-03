<?php

/**
 * Plugin Name: External Markdown
 * Author: Moritz Stueckler
 * Description: Include and parse markdown files from external web sources like GitHub, GitLab, etc.
 * Version: 0.1
 */

function external_markdown_shortcode($atts = array())
{
  extract(shortcode_atts(array(
    'url' => 'https://raw.githubusercontent.com/othneildrew/Best-README-Template/master/BLANK_README.md'
  ), $atts));

  $wp_remote = wp_remote_get($url);
  $response_body = wp_remote_retrieve_body($wp_remote);
  $response_code = wp_remote_retrieve_response_code($wp_remote);

  if ($response_code != 200) {
    return "Error: Could not load external markdown file.";
  }

  $args = array(
    'body' => json_encode(array(
      "text" => $response_body
    )),
    'headers' => array(
      'Content-Type' => 'application/json'
    )
  );

  $response = wp_remote_post("https://api.github.com/markdown", $args);
  $html_body = wp_remote_retrieve_body($response);

  $html_string = '<div class="markdown-body">' . $html_body . '</div>';

  return $html_string;
}

add_action('wp_enqueue_style', function () {
  wp_enqueue_style('external-markdown', plugins_url('css/external-markdown.css', __FILE__));
});

add_shortcode('external_markdown', 'external_markdown_shortcode');
