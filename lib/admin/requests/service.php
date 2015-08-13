<?php
namespace SandersForPresidentLanding\Wordpress\Admin\Requests;
use WP_Query;

class RequestService {
  const POST_TYPE = 'request';
  const POST_TITLE_KEY = 'organization';
  const POST_CONTENT_KEY = 'message';
  const META_KEY_CAUSE = 'cause';
  const META_KEY_URL = 'url';
  const META_KEY_EMAIL = 'contact_email';
  const META_KEY_NAME = 'contact_name';

  public function getRequests() {
    $requests = [];
    $args = array(
      'post_type' => self::POST_TYPE
    );
    $query = new WP_Query($args);

    while ($query->have_posts()) {
      $query->the_post();
      $request = array ();
      $request['id'] = get_the_ID();
      $request[self::POST_TITLE_KEY] = get_the_title();
      $request[self::POST_CONTENT_KEY] = get_the_content();
      $request[self::META_KEY_CAUSE] = get_post_meta(get_the_ID(), self::META_KEY_CAUSE, true);
      $request[self::META_KEY_URL] = get_post_meta(get_the_ID(), self::META_KEY_URL, true);
      $request[self::META_KEY_EMAIL] = get_post_meta(get_the_ID(), self::META_KEY_EMAIL, true);
      $request[self::META_KEY_NAME] = get_post_meta(get_the_ID(), self::META_KEY_NAME, true);
      $requests[] = $request;
    }
    return $requests;
  }

  public function getRequest($id) {
    return null;
  }

  public function createRequest($request) {
    $postArgs = array(
      'post_title' => $request[self::POST_TITLE_KEY],
      'post_content' => $request[self::POST_CONTENT_KEY],
      'post_type' => self::POST_TYPE
    );
    $postId = wp_insert_post($postArgs);
    if (!is_wp_error($postId)) {
      add_post_meta($postId, self::META_KEY_CAUSE, $request[self::META_KEY_CAUSE]);
      add_post_meta($postId, self::META_KEY_URL, $request[self::META_KEY_URL]);
      add_post_meta($postId, self::META_KEY_EMAIL, $request[self::META_KEY_EMAIL]);
      add_post_meta($postId, self::META_KEY_NAME, $request[self::META_KEY_NAME]);
      return true;
    } else {
      return false;
    }
  }
}
