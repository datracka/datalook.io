<?php

// =============================================================================
// FUNCTIONS/GLOBAL/ADMIN/MIGRATION.PHP
// -----------------------------------------------------------------------------
// Handles theme migration.
// =============================================================================

// =============================================================================
// TABLE OF CONTENTS
// -----------------------------------------------------------------------------
//   01. Version Migration
//   02. Pairing Notice
//   03. Version Migration Notice
//   04. Theme Migration
//   05. Term Splitting Migration (WordPress 4.2 Breaking Change)
// =============================================================================

// Version Migration
// =============================================================================

function x_version_migration() {

  $prior = get_option( 'x_version', X_VERSION );

  if ( version_compare( $prior, X_VERSION, '<' ) ) {

    //
    // If $prior is less than 2.2.0.
    //

    if ( version_compare( $prior, '2.2.0', '<' ) ) {

      $mods = get_theme_mods();

      foreach( $mods as $key => $value ) {
        update_option( $key, $value );
      }

    }


    //
    // If $prior is less than 3.1.0.
    //

    if ( version_compare( $prior, '3.1.0', '<' ) ) {

      $stack      = get_option( 'x_stack' );
      $design     = ( $stack == 'integrity' ) ? '_' . get_option( 'x_integrity_design' ) : '';
      $stack_safe = ( $stack == 'icon' ) ? 'integrity' : $stack;

      $updated = array(
        'x_layout_site'               => get_option( 'x_' . $stack . '_layout_site' ),
        'x_layout_site_max_width'     => get_option( 'x_' . $stack . '_sizing_site_max_width' ),
        'x_layout_site_width'         => get_option( 'x_' . $stack . '_sizing_site_width' ),
        'x_layout_content'            => get_option( 'x_' . $stack . '_layout_content' ),
        'x_layout_content_width'      => get_option( 'x_' . $stack_safe . '_sizing_content_width' ),
        'x_layout_sidebar_width'      => get_option( 'x_icon_sidebar_width' ),
        'x_design_bg_color'           => get_option( 'x_' . $stack . $design . '_bg_color' ),
        'x_design_bg_image_pattern'   => get_option( 'x_' . $stack . $design . '_bg_image_pattern' ),
        'x_design_bg_image_full'      => get_option( 'x_' . $stack . $design . '_bg_image_full' ),
        'x_design_bg_image_full_fade' => get_option( 'x_' . $stack . $design . '_bg_image_full_fade' )
      );

      foreach ( $updated as $key => $value ) {
        update_option( $key, $value );
      }

    }


    //
    // If $prior is less than 4.0.0.
    //

    if ( version_compare( $prior, '4.0.0', '<' ) ) {

      $updated = array(
        'x_pre_v4' => true
      );

      foreach ( $updated as $key => $value ) {
        update_option( $key, $value );
      }

    }


    //
    // If $prior is less than 4.0.4.
    //

    if ( version_compare( $prior, '4.0.4', '<' ) ) {

      $stack            = get_option( 'x_stack' );
      $navbar_font_size = get_option( 'x_navbar_font_size', 12 );

      if ( $stack == 'integrity' ) {
        $link_spacing        = round( intval( $navbar_font_size ) * 1.429 );
        $link_letter_spacing = 2;
      } else if ( $stack == 'renew' ) {
        $link_spacing        = intval( $navbar_font_size );
        $link_letter_spacing = 1;
      } else if ( $stack == 'icon' ) {
        $link_spacing        = 5;
        $link_letter_spacing = 1;
      } else if ( $stack == 'ethos' ) {
        $link_spacing        = get_option( 'x_ethos_navbar_desktop_link_side_padding' );
        $link_letter_spacing = 1;
      }

      $updated = array(
        'x_navbar_adjust_links_top_spacing' => $link_spacing,
        'x_navbar_letter_spacing'           => $link_letter_spacing
      );

      foreach ( $updated as $key => $value ) {
        update_option( $key, $value );
      }

    }


    //
    // Update stored version number.
    //

    update_option( 'x_version', X_VERSION );


    //
    // Turn on the version migration notice.
    //

    update_option( 'x_version_migration_notice', true );

  }

}

add_action( 'admin_init', 'x_version_migration' );



// Pairing Notice
// =============================================================================

//
// Define current version of plugin and prompt for plugin update if:
//
// 1. Plugin doesn't specify current theme constant (i.e. is too old).
// 2. Plugin is older than what the theme desires it to be
//

define( 'X_CORNERSTONE_CURRENT', '1.0.0' );

function x_pairing_notice() {

  if ( x_plugin_cornerstone_exists() && class_exists('CS') ) {
    if ( ! defined( 'X_CURRENT' ) || version_compare( CS()->version(), X_CORNERSTONE_CURRENT, '<' ) ) { ?>

      <div class="updated x-notice warning">
        <p><strong>IMPORTANT: Please update Cornerstone</strong>. You are using a newer version of X that may not be compatible. After updating, please ensure that you have cleared out your browser cache and any caching plugins you may be using. This message will self destruct upon updating Cornerstone.</p>
      </div>

    <?php }
  }

}

add_action( 'admin_notices', 'x_pairing_notice' );



// Version Migration Notice
// =============================================================================

//
// 1. Output notice.
// 2. Dismiss notice.
//

function x_version_migration_notice() { // 1

  if ( get_option( 'x_version_migration_notice' ) == true ) { ?>

    <div class="updated x-notice dismissible">
      <a href="<?php echo esc_url( add_query_arg( array( 'x-dismiss-notice' => true ) ) ); ?>" class="dismiss"><span class="dashicons dashicons-no"></span></a>
      <p>Congratulations, you've successfully updated X! Be sure to <a href="//theme.co/changelog/" target="_blank">check out the release notes and changelog</a> for this latest version to see all that has changed, especially if you're utilizing any additional plugins or have made modifications to your website via a child theme.</p>
    </div>

  <?php }

}

add_action( 'admin_notices', 'x_version_migration_notice' );


function x_version_migration_notice_dismiss() { // 2

  if ( isset( $_GET['x-dismiss-notice'] ) ) {
    update_option( 'x_version_migration_notice', false );
  }

}

add_action( 'admin_init', 'x_version_migration_notice_dismiss' );



// Theme Migration
// =============================================================================

function x_theme_migration( $new_name, $new_theme ) {

  if ( $new_theme == 'X' || $new_theme->get( 'Template' ) == 'x' ) {
    return false;
  }

  include_once( ABSPATH . '/wp-admin/includes/plugin.php' );

  $plugins   = get_plugins();
  $x_plugins = array();

  foreach ( (array) $plugins as $plugin => $headers ) {
    if ( ! empty( $headers['X Plugin'] ) ) {
      $x_plugins[] = $plugin;
    }
  }

  deactivate_plugins( $x_plugins );

}

add_action( 'switch_theme', 'x_theme_migration', 10, 2 );



// Term Splitting Migration (WordPress 4.2 Breaking Change)
// =============================================================================

function x_split_shared_term_migration( $term_id, $new_term_id, $term_taxonomy_id, $taxonomy ) {

  //
  // Ethos filterable index categories.
  //

  if ( $taxonomy == 'category' ) {

    $setting = array_map( 'trim', explode( ',', get_option( 'x_ethos_filterable_index_categories' ) ) );

    foreach ( $setting as $index => $old_term ) {
      if ( $old_term == (string) $term_id ) {
        $setting[$index] = (string) $new_term_id;
      }
    }

    update_option( 'x_ethos_filterable_index_categories', implode(', ', $setting) );

  }


  //
  // Portfolio categories.
  //

  if ( $taxonomy == 'portfolio-category' ) {

    $post_ids = get_posts( array(
      'fields'       => 'ids',
      'meta_key'     =>  '_x_portfolio_category_filters',
      'meta_value'   => '',
      'meta_compare' => '!='
    ) );

    foreach ( $post_ids as $post_id ) {

      $post_terms = get_post_meta( $post_id, '_x_portfolio_category_filters', true );

      if ( is_array( $post_terms ) ) {
        foreach ( $post_terms as $index => $old_term ) {
          if ( $term_id == $old_term) {
            $post_terms[$index] = $new_term_id;
          }
        }
      }

      update_post_meta( $post_id, '_x_portfolio_category_filters', $post_terms );

    }
  }

}

add_action( 'split_shared_term', 'x_split_shared_term_migration' );