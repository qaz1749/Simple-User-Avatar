<?php
/*
Plugin Name: Simple User Avatar
Description: Add a <strong>user avatar</strong> using images from your Media Library.
Author: Matteo Manna
Version: 1.0
Author URI: http://matteomanna.com/
Text Domain: mmuseravatar
License: GPL2
*/

function mm_sua_admin_head_scripts() {
    wp_enqueue_media();
    wp_enqueue_style('mm-css-style', plugins_url().'/mm-simple-user-avatar/css/style.css', array(), null);
    wp_enqueue_script('mm-js-custom', plugins_url().'/mm-simple-user-avatar/js/scripts.js', array(), '1.0', true);
}
add_action( 'admin_enqueue_scripts', 'mm_sua_admin_head_scripts' );

/**
 * @param $user_id
 * @return bool
 */
function mm_sua_update_custom_user_profile($user_id) {
    if( !current_user_can('edit_user', $user_id) ) return FALSE;

    delete_user_meta( $user_id, 'mm_sua_attachment_id') ;
    if( isset($_POST['mm_sua_attachment_id']) && $_POST['mm_sua_attachment_id'] > 0 ) add_user_meta( $user_id, 'mm_sua_attachment_id', $_POST['mm_sua_attachment_id'] );
}
add_action( 'personal_options_update', 'mm_sua_update_custom_user_profile' );
add_action( 'edit_user_profile_update', 'mm_sua_update_custom_user_profile' );

/**
 * @param $user
 */
function mm_sua_add_custom_user_profile_fields($user) {
    $mm_sua_attachment_id = (int)get_user_meta( $user->ID, 'mm_sua_attachment_id', true );
    ?>
    <table class="form-table">
        <tbody>
            <tr>
                <th>
                    <label for="mm-sua-add-media"><?php echo __('Select or remove avatar', 'mmuseravatar'); ?></label>
                </th>
                <td>
                    <input type="text" name="mm_sua_attachment_id" class="mm-sua-attachment-id" value="<?php echo $mm_sua_attachment_id; ?>" />
                    <div class="mm-sua-attachment-image">
                        <?php echo get_avatar($user->ID); ?>
                    </div>
                    <div class="wp-media-buttons">
                        <button class="button mm-sua-add-media" id="mm-sua-add-media"><?php echo __('Select'); ?></button>
                        <button class="button mm-sua-remove-media"><?php echo __('Remove'); ?></button>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}
add_action( 'show_user_profile', 'mm_sua_add_custom_user_profile_fields' );
add_action( 'edit_user_profile', 'mm_sua_add_custom_user_profile_fields' );

/**
 * @param int $attachment_id
 * @param string $size
 * @return mixed
 */
function mm_sua_get_attachment_url($attachment_id = 0, $size = 'thumbnail') {
    $image = wp_get_attachment_image_src($attachment_id, $size);
    return $image[0];
}

/**
 * @param $plugin
 */
function mm_sua_redirect_after_activation( $plugin ) {
    if( $plugin == plugin_basename( __FILE__ ) ) {
        wp_redirect( admin_url('profile.php') );
        exit();
    }
}
add_action( 'activated_plugin', 'mm_sua_redirect_after_activation' );

/**
 * @param string $avatar
 * @param $id_or_email
 * @return mixed|string
 */
function mm_sua_get_new_avatar( $avatar = '', $id_or_email ) {
    $user_id = 0;

    if ( is_numeric($id_or_email) ) {
        $user_id = $id_or_email;
    } else if ( is_string($id_or_email) ) {
        $user = get_user_by( 'email', $id_or_email );
        $user_id = $user->id;
    } else if ( is_object($id_or_email) ) {
        $user_id = $id_or_email->user_id;
    }
    if ( $user_id == 0 ) return $avatar;

    $mm_sua_attachment_id = (int)get_user_meta( $user_id, 'mm_sua_attachment_id', true );
    $image = mm_sua_get_attachment_url($mm_sua_attachment_id, 'thumbnail');
    if( empty($image) ) $avatar = '';

    $avatar = preg_replace('/src=("|\').*?("|\')/i', 'src="'.$image.'"', $avatar);
    return $avatar;
}
add_filter( 'get_avatar', 'mm_sua_get_new_avatar', 5, 5 );