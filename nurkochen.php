<?php
/**
 * Plugin Name: NurKochen Plugin
 * Plugin URI: http://nurkochen.de/partner.html
 * Description: Dieses Plugin macht deine Rezepte bestellbar. Verwende es in Beiträgen mit Rezepten.
 * Version: 1.0.2
 * Author: nurkochen.de
 * Author URI: http://nurkochen.de
 * Developer: appetites.de
 */

class Nurkochen {
    public function __construct()
    {
        add_action('admin_menu', array($this, 'nurkochen_menu'));
        add_action('after_setup_theme', array($this, 'nurkochen_after_setup_theme'));
        add_action('admin_init', array($this, 'nurkochen_settings' ));
        add_action('wp_head', array($this, 'nurkochen_add_header_data'));
        add_action('add_meta_boxes', array($this, 'nurkochen_dropdown_metabox' ));
        
        add_shortcode( 'nurkochen', array($this, 'nurkochen_shortcode_func') );

        if ( is_admin() ){
          add_action('admin_head', array( $this, 'nurkochen_admin_head') );
          add_action( 'admin_print_footer_scripts', array( $this, 'nurkochen_add_quicktags') );
        }
    }

    public function nurkochen_menu() {
        add_menu_page('NurKochen Einstellungen', 'NurKochen Einstellungen', 'administrator', 'nurkochen-settings', array($this, 'nurkochen_settings_page'), '');
    }

    public function nurkochen_plugin_settings_link( $links ) {
        $url = get_admin_url() . 'admin.php?page=nurkochen-settings';
        $settings_link = '<a href="' . $url . '">' . __('Einstellungen', 'textdomain') . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    public function nurkochen_after_setup_theme() {
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'nurkochen_plugin_settings_link'));
    }


    public function nurkochen_settings_page() {
        ?>
            <div class="wrap">
            <h2>Nur Kochen Einstellungen</h2>

            <form method="post" action="options.php">
              <?php

              settings_fields( 'nurkochen-settings-group' );
              do_settings_sections( 'nurkochen-settings-group' );

              wp_enqueue_script('wp-color-picker');
              wp_enqueue_style( 'wp-color-picker' );

              ?>

              <?php if( isset($_GET['settings-updated']) ) { ?>
                  <div id="message" class="updated">
                      <p><strong>Wurde gespeichert</strong></p>
                  </div>
              <?php } ?>

              <table class="form-table">
                  <tr valign="top">
                  <th scope="row">Benutzer-Id</th>
                  <td><input type="text" name="user_id" value="<?php echo esc_attr( get_option('user_id') ); ?>" /></td>
                  <td>Deine Benutzer-ID von Nurkochen.de. Wenn du noch keine Benutzer-ID hast kannst Du sie <a href="http://nurkochen.de/partner.html">hier abrufen</a>.</td>
                  </tr>

                  <tr valign="top">
                  <th scope="row">Button-Farbe</th>
                  <td><input type="text" class="jscolor" name="button_color" data-default-color="#9f2562" value="<?php echo esc_attr( get_option('button_color') ); ?>" /></td>
                  </tr>

                  <tr valign="top">
                  <th scope="row">Button-Textfarbe</th>
                  <td><input type="text" class="jscolor" name="button_text_color" data-default-color="#ffffff" value="<?php echo esc_attr( get_option('button_text_color') ); ?>" /></td>
                  </tr>
              </table>

                  <script type="text/javascript">
              jQuery(document).ready(function($) {
                  $('.jscolor').wpColorPicker();
              });
              </script>


              <?php submit_button(); ?>


                  <?php
                    $content =
                            "<script type=\"text/javascript\">
                                var nk_config = {};
                                nk_config.userId = '7';
                                nk_config.buttonColor = '".get_option('button_color')."';
                                nk_config.buttonTextColor = '".get_option('button_text_color')."';
                                jQuery(document).ready(function($) {
                                  (function() {
                                      var nk = document.createElement('script'); nk.type = 'text/javascript'; nk.async = \"true\"; nk.src = 'http://nurkochen.de/external/core.js'; var h = document.getElementsByTagName(\"head\")[0]; h.appendChild(nk);
                                  })();
                                });
                            </script>
                            ";
                    echo $content;
                  ?>
              <h4>Vorschau (Speichern um zu aktualisieren)</h4>
              <div class="nurkochen_order_wrapper" data-rezeptId="309" data-portionen="4"></div>

            </form>
            </div>
        <?php
    }


    public function nurkochen_settings() {
        register_setting( 'nurkochen-settings-group', 'user_id' );
        register_setting( 'nurkochen-settings-group', 'button_color' );
        register_setting( 'nurkochen-settings-group', 'button_text_color' );
    }

    public function nurkochen_add_header_data() {
          $content =
          "<script type=\"text/javascript\">
              var nk_config = {};
              nk_config.userId = '".get_option('user_id')."';
              nk_config.buttonColor = '".get_option('button_color')."';
              nk_config.buttonTextColor = '".get_option('button_text_color')."';
              jQuery(document).ready(function($) {
                (function() {
                    var nk = document.createElement('script'); nk.type = 'text/javascript'; nk.async = \"true\"; nk.src = 'http://nurkochen.de/external/core.js'; var h = document.getElementsByTagName(\"head\")[0]; h.appendChild(nk);
                })();
              });
          </script>
          ";
        echo $content;
    }

    // function that creates the new metabox that will show on post
    public function nurkochen_dropdown_metabox() {
        add_meta_box(
            'nk_dropdown',  // unique id
            __( 'NurKochen', 'mytheme_textdomain' ),  // metabox title
            array($this, 'nurkochen_dropdown_display'),  // callback to show the dropdown
            'post'   // post type
        );
    }

    public function nurkochen_dropdown_display($post) {
        $userId = esc_attr( get_option('user_id', 0) );
        if($userId == 0){
          // Link zur Einstellungsseite, wenn keine user_id gesetzt ist

          echo 'Bitte fülle die <a href="'.admin_url( 'admin.php?page=nurkochen-settings').'">Plugin-Einstellungen</a> aus, damit das NurKochen-Plugin funktioniert';
          return;
        }
        
        // Use nonce for verification
        wp_nonce_field( basename( __FILE__ ), 'nk_dropdown_nonce' );

        ?>
          <div style="margin-bottom: 20px;">Durch Klick auf "Einf&uuml;gen" fügst du einen Bestellbutton für die Zutaten des Rezepts in Deinem Beitrag ein.</div>

          <button id="nurkochen-select-recipe">Einf&uuml;gen</button>

          <script>

            jQuery('#nurkochen-select-recipe').on('click', function(e){
              e.preventDefault();
              
              var shortCode = '[nurkochen]';
              
              var textarea = jQuery( '#wp-content-editor-container' ).find( 'textarea' );
              if(textarea != 'undefined'){
                textarea.val(textarea.val()+shortCode);
              }

              if(typeof tinyMCE != 'undefined' && tinyMCE.activeEditor != 'undefined' && tinyMCE.activeEditor.selection != 'undefined'){
                tinyMCE.activeEditor.selection.setContent(shortCode);
              }
            });
          </script>
        <?php
    }

    public function nurkochen_shortcode_func() {
        return '<div class="nurkochen_order_wrapper"></div>';
    }

    public function nurkochen_admin_head() {
        if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
            return;
        }

        if ( 'true' == get_user_option( 'rich_editing' ) ) {
            add_filter( 'mce_external_plugins', array( $this ,'nurkochen_mce_external_plugins' ) );
            add_filter( 'mce_buttons', array($this, 'nurkochen_mce_buttons' ) );
        }
    }

    public function nurkochen_mce_external_plugins( $plugin_array ) {
      $plugin_array['nurkochen'] = plugins_url( 'js/mce-button.js' , __FILE__ );
      return $plugin_array;
    }
 
    public function nurkochen_mce_buttons( $buttons ) {
      array_push( $buttons, 'nurkochen' );
      return $buttons;
    }

    public function nurkochen_add_quicktags() {
        if (wp_script_is('quicktags')){
    ?>
        <script type="text/javascript">
        QTags.addButton( 'nurkochen_id', 'Nurkochen', '[nurkochen]');
        </script>
    <?php
        }
    }


}

$nurkochen = new Nurkochen();
