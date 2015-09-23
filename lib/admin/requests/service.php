<?php

namespace SandersForPresidentLanding\Wordpress\Admin\Requests;
use WP_Query;
use WP_Post;
use ns_cloner;

class RequestService {
  const POST_TYPE = 'request';
  const POST_TITLE_KEY = 'organization';
  const POST_CONTENT_KEY = 'message';
  const POST_STATUS_APPROVED = 'approved';
  const POST_STATUS_REJECTED = 'rejected';
  const META_KEY_CAUSE = 'cause';
  const META_KEY_URL = 'url';
  const META_KEY_EMAIL = 'contact_email';
  const META_KEY_NAME = 'contact_name';
  const META_KEY_READ = 'read';
  const META_KEY_ROLE = 'role';
  const META_KEY_TERMS_AGREED = 'terms_agreed';
  const REFERERS_TO_REJECT_PATTERN = "/4chan\.org/i";

  public function getRequests() {
    $query = $this->getQueryRequests();
    return $query['posts'];
  }

  public function getQueryRequests($paged=1, $post_status=null) {
    if (empty($post_status)) {
      $post_status = array('publish', 'draft');
    }
    $requests = array();
    $args = array(
      'post_type' => self::POST_TYPE,
      'paged' => $paged,
      'posts_per_page' => 10,
      'post_status' => $post_status
    );
    $query = new WP_Query($args);

    while ($query->have_posts()) {
      $query->the_post();
      $request = array ();
      $request['id'] = get_the_ID();
      $request['date'] = get_the_time('F d, Y h:ia', get_the_ID());
      $request[self::POST_TITLE_KEY] = get_the_title();
      $request[self::POST_CONTENT_KEY] = get_the_content();
      $request[self::META_KEY_CAUSE] = get_post_meta(get_the_ID(), self::META_KEY_CAUSE, true);
      $request[self::META_KEY_URL] = get_post_meta(get_the_ID(), self::META_KEY_URL, true);
      $request[self::META_KEY_EMAIL] = get_post_meta(get_the_ID(), self::META_KEY_EMAIL, true);
      $request[self::META_KEY_NAME] = get_post_meta(get_the_ID(), self::META_KEY_NAME, true);
      $request[self::META_KEY_ROLE] = get_post_meta(get_the_ID(), self::META_KEY_ROLE, true);
      $request[self::META_KEY_TERMS_AGREED] = get_post_meta(get_the_id(), self::META_KEY_TERMS_AGREED, true);
      $request[self::META_KEY_READ] = get_post_meta(get_the_ID(), self::META_KEY_READ, true);

      $requests[] = $request;
    }

    return array (
      'count' => $query->found_posts,
      'posts' => $requests
    );
  }

  public function getRequest($id) {
    $post = get_post($id);
    if ($post instanceof WP_Post && $post->post_type == self::POST_TYPE) {
      $request = array ();
      $request['id'] = $post->ID;
      $request['date'] = get_the_time('F d, Y h:ia', $post->ID);
      $request['status'] = $post->post_status;
      $request[self::POST_TITLE_KEY] = $post->post_title;
      $request[self::POST_CONTENT_KEY] = $post->post_content;
      $request[self::META_KEY_CAUSE] = get_post_meta($post->ID, self::META_KEY_CAUSE, true);
      $request[self::META_KEY_URL] = get_post_meta($post->ID, self::META_KEY_URL, true);
      $request[self::META_KEY_EMAIL] = get_post_meta($post->ID, self::META_KEY_EMAIL, true);
      $request[self::META_KEY_NAME] = get_post_meta($post->ID, self::META_KEY_NAME, true);
      $request[self::META_KEY_READ] = get_post_meta($post->ID, self::META_KEY_READ, true);
      $request[self::META_KEY_ROLE] = get_post_meta($post->ID, self::META_KEY_ROLE, true);
      $request[self::META_KEY_TERMS_AGREED] = get_post_meta($post->ID, self::META_KEY_TERMS_AGREED, true);
      return $request;
    }
    return null;
  }

  public function createRequest($request) {
    $postArgs = array(
      'post_title' => $request[self::POST_TITLE_KEY],
      'post_content' => $request[self::POST_CONTENT_KEY],
      'post_type' => self::POST_TYPE
    );
    if(preg_match(self::REFERERS_TO_REJECT_PATTERN, $request['referer'])){
      $postArgs['post_status'] = self::POST_STATUS_REJECTED;
    }
    $postId = wp_insert_post($postArgs);
    if (!is_wp_error($postId)) {
      add_post_meta($postId, self::META_KEY_CAUSE, $request[self::META_KEY_CAUSE]);
      add_post_meta($postId, self::META_KEY_URL, $request[self::META_KEY_URL]);
      add_post_meta($postId, self::META_KEY_EMAIL, $request[self::META_KEY_EMAIL]);
      add_post_meta($postId, self::META_KEY_NAME, $request[self::META_KEY_NAME]);
      add_post_meta($postId, self::META_KEY_ROLE, $request[self::META_KEY_ROLE]);
      add_post_meta($postId, self::META_KEY_TERMS_AGREED, $request[self::META_KEY_TERMS_AGREED]);
      add_post_meta($postId, self::META_KEY_READ, false);
      return true;
    } else {
      return false;
    }
  }

  public function markAsRead($id) {
    update_post_meta($id, self::META_KEY_READ, true);
  }

  public function approve($id) {

    // the request as submitted by the user
    $post = get_post($id);

    // dynamic seed site FTW
    $clone_site = array_filter(wp_get_sites(), function($site){
                      return preg_match('/^seed\./', $site['domain']);
                    })[0];

    // setup the cloner
    $ns_cloner = new ns_cloner();
    $ns_cloner->set_up_request(array(
                    'action' => 'process',
                    'clone_mode' => 'core',
                    'source_id' => $clone_site->blog_id,  // might be site_id?
                    'target_name' => get_post_meta($post->ID, self::META_KEY_URL, true),
                    'target_title' => $post->post_title,
                    'disable_addons' => 1
                  ));

    // not super clean but..
    add_filter( 'ns_cloner_pipeline_steps', array($ns_cloner, 'register_create_site_step'), 100 );
    add_filter( 'ns_cloner_pipeline_steps', array($ns_cloner, 'register_clone_tables_step'), 200 );   
    add_filter( 'ns_cloner_pipeline_steps', array($ns_cloner, 'register_copy_files_step'), 300 );

    // gogogo
    $ns_cloner->process();

    // update titles and things.
    update_blog_option($ns_cloner->target_id, 'blogdescription', get_post_meta($post->ID, self::META_KEY_CAUSE, true));

    // create (or reference existing) superuser based on who submitted the request
    $email_address = get_post_meta($post->ID, self::META_KEY_EMAIL, true);
    if($user = get_user_by('email', $email_address)){
      $user_id = $user->ID;
    } else {
      $password = wp_generate_password( 12, false );
      $username = strtolower(str_replace(' ', '', get_post_meta($post->ID, self::META_KEY_NAME, true)));
      $user_id = wp_create_user( $username, $password, $email_address );
      // Email the user
      wp_mail( $email_address, 'Welcome!', 'Your Password: ' . $password );
    }

    add_user_to_blog( $ns_cloner->target_id, $user_id, 'Administrator');

    switch_to_blog($ns_cloner->target_id);

    // do we need that extra character? not sure v
    if($page = get_page_by_title('STATE_NAME for Bernie Sanders 2016')){
      $page->post_title = $post->post_title;
      $page->post_body = str_replace('STATE_NAME', $post->post_title, $post->post_body);
      $page->save();
    }

    // Update `Options Site State Abbreviation` with the state abbreviation (if applicable)
    // Update `Is Default State Site`
    // update Yoast

    restore_current_blog();

    // mark this thing donezo
    wp_update_post(array(
      'ID' => $id,
      'post_status' => self::POST_STATUS_APPROVED
    ));

    return true;
  }

  public function reject($id) {
    return wp_update_post(array(
      'ID' => $id,
      'post_status' => self::POST_STATUS_REJECTED
    ));
  }
}
