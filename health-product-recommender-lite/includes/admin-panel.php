<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'set-screen-option', 'hprl_set_screen_option', 10, 3 );
function hprl_set_screen_option( $status, $option, $value ) {
    if ( 'hprl_results_per_page' === $option ) {
        return (int) $value;
    }
    return $status;
}

add_action( 'admin_menu', 'hprl_admin_menu' );
function hprl_admin_menu() {
    add_menu_page( 'Health Quiz', 'Health Quiz', 'manage_options', 'hprl-questions', 'hprl_questions_page', 'dashicons-heart' );
    add_submenu_page( 'hprl-questions', 'Pitanja', 'Pitanja', 'manage_options', 'hprl-questions', 'hprl_questions_page' );
    $hook = add_submenu_page( 'hprl-questions', 'Rezultati', 'Rezultati', 'manage_options', 'hprl-results', 'hprl_results_page' );
    add_action( "load-$hook", 'hprl_results_screen_option' );
}

function hprl_results_screen_option() {
    $args = array(
        'label'   => 'Rezultata po strani',
        'default' => 20,
        'option'  => 'hprl_results_per_page',
    );
    add_screen_option( 'per_page', $args );
}

add_action( 'admin_init', 'hprl_handle_export' );
function hprl_handle_export() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( empty( $_GET['page'] ) || $_GET['page'] !== 'hprl-results' ) {
        return;
    }
    if ( empty( $_GET['export'] ) ) {
        return;
    }

    global $wpdb;
    $rows = $wpdb->get_results( "SELECT * FROM " . HPRL_TABLE . " ORDER BY created_at DESC", ARRAY_A );
    $header = array( 'ID','First Name','Last Name','Email','Phone','Birth Year','Location','Answers','Product','Date' );

    header( 'Content-Type: text/csv' );
    header( 'Content-Disposition: attachment; filename="hprl-results.csv"' );
    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, $header );
    foreach ( $rows as $row ) {
        fputcsv( $out, array(
            $row['id'],
            isset( $row['first_name'] ) ? $row['first_name'] : '',
            isset( $row['last_name'] ) ? $row['last_name'] : '',
            $row['email'],
            $row['phone'],
            $row['birth_year'],
            $row['location'],
            implode( ',', maybe_unserialize( $row['answers'] ) ),
            $row['product_id'] ? get_the_title( $row['product_id'] ) : '',
            $row['created_at'],
        ) );
    }
    fclose( $out );

    exit;
}

function hprl_questions_page() {
    if ( isset( $_POST['hprl_save_questions'] ) ) {
        check_admin_referer( 'hprl_save_questions' );
        $questions = array();
        if ( isset( $_POST['question_text'] ) ) {
            foreach ( $_POST['question_text'] as $i => $qt ) {
                $text = sanitize_text_field( $qt );
                $ans  = isset( $_POST['question_answers'][ $i ] ) ? sanitize_text_field( $_POST['question_answers'][ $i ] ) : '';
                $answers = array();
                if ( $ans !== '' ) {
                    $parts   = array_map( 'trim', explode( ',', $ans ) );
                    $answers = array_map( 'sanitize_text_field', $parts );
                }
                $main    = isset( $_POST['question_main'][ $i ] ) ? intval( $_POST['question_main'][ $i ] ) : 0;
                $extra   = isset( $_POST['question_extra'][ $i ] ) ? intval( $_POST['question_extra'][ $i ] ) : 0;
                $package = isset( $_POST['question_package'][ $i ] ) ? intval( $_POST['question_package'][ $i ] ) : 0;
                $note    = isset( $_POST['question_note'][ $i ] ) ? wp_kses_post( $_POST['question_note'][ $i ] ) : '';
                if ( $text !== '' ) {
                    $questions[] = array(
                        'text'    => $text,
                        'answers' => $answers,
                        'main'    => $main,
                        'extra'   => $extra,
                        'package' => $package,
                        'note'    => $note,
                    );
                }
            }
        }
        update_option( 'hprl_questions', $questions );
        $max_q = count( $questions );

        $per_page = max( 1, intval( $_POST['questions_per_page'] ) );
        update_option( 'hprl_questions_per_page', $per_page );

        $debug_log = isset( $_POST['hprl_debug_log'] ) ? 1 : 0;
        update_option( 'hprl_debug_log', $debug_log );

        $github_token = isset( $_POST['github_token'] ) ? sanitize_text_field( $_POST['github_token'] ) : '';
        update_option( 'hprl_github_token', $github_token );

        $universal = isset( $_POST['universal_package'] ) ? intval( $_POST['universal_package'] ) : 0;
        update_option( 'hprl_universal_package', $universal );

        $status_low  = isset( $_POST['status_low'] ) ? wp_kses_post( $_POST['status_low'] ) : '';
        $status_mid  = isset( $_POST['status_mid'] ) ? wp_kses_post( $_POST['status_mid'] ) : '';
        $status_high = isset( $_POST['status_high'] ) ? wp_kses_post( $_POST['status_high'] ) : '';
        update_option( 'hprl_status_texts', array(
            'low'  => $status_low,
            'mid'  => $status_mid,
            'high' => $status_high,
        ) );

        echo '<div class="updated"><p>Sačuvano.</p></div>';
    }

    $default_questions = array(
        array(
            'text'    => 'Primer pitanja 1',
            'answers' => array( 'Da', 'Ne' ),
            'main'    => 0,
            'extra'   => 0,
            'package' => 0,
            'note'    => ''
        ),
    );
    $questions = get_option( 'hprl_questions', $default_questions );
    $products  = get_option( 'hprl_products', array() );
    $debug_log = intval( get_option( 'hprl_debug_log', 0 ) );
    $github_token = get_option( 'hprl_github_token', '' );
    $universal_package = intval( get_option( 'hprl_universal_package', 0 ) );
    $status_texts = get_option( 'hprl_status_texts', array(
        'low'  => '',
        'mid'  => '',
        'high' => '',
    ) );
    $per_page  = intval( get_option( 'hprl_questions_per_page', 3 ) );
    $max_q = count( $questions );

    $all_products = array();
    $prod_posts = get_posts( array( 'post_type' => 'product', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
    foreach ( $prod_posts as $p ) {
        $all_products[ $p->ID ] = $p->post_title;
    }
    $options_html = '<option value="">-</option>';
    foreach ( $all_products as $pid => $title ) {
        $options_html .= '<option value="' . esc_attr( $pid ) . '">' . esc_html( $title ) . '</option>';
    }
    ?>
    <div class="wrap">
        <h1>Pitanja</h1>
        <form method="post">
            <?php wp_nonce_field( 'hprl_save_questions' ); ?>
            <table class="form-table" id="hprl-questions-table">
                <tbody id="hprl-questions-body">
                <?php for ( $i = 0; $i < $max_q; $i++ ) :
                    $q = isset( $questions[ $i ] ) ? $questions[ $i ] : array( 'text' => '', 'answers' => array() );
                    $ans = implode( ',', $q['answers'] );
                ?>
                <tr>
                    <th>Pitanje <?php echo $i + 1; ?></th>
                    <td>
                        <input type="text" name="question_text[<?php echo $i; ?>]" value="<?php echo esc_attr( $q['text'] ); ?>" class="regular-text" />
                        <br/>
                        <small>Odgovori (zarezom odvojeni, prvo "Da" pa "Ne")</small><br/>
                        <input type="text" name="question_answers[<?php echo $i; ?>]" value="<?php echo esc_attr( $ans ); ?>" class="regular-text" />
                        <br/>
                        <small>Glavni proizvod</small><br/>
                        <select name="question_main[<?php echo $i; ?>]">
                            <option value="">-</option>
                            <?php foreach ( $all_products as $pid => $title ) : ?>
                                <option value="<?php echo esc_attr( $pid ); ?>" <?php selected( isset( $q['main'] ) && intval( $q['main'] ) === $pid ); ?>><?php echo esc_html( $title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <br/>
                        <small>Dodatni proizvod</small><br/>
                        <select name="question_extra[<?php echo $i; ?>]">
                            <option value="">-</option>
                            <?php foreach ( $all_products as $pid => $title ) : ?>
                                <option value="<?php echo esc_attr( $pid ); ?>" <?php selected( isset( $q['extra'] ) && intval( $q['extra'] ) === $pid ); ?>><?php echo esc_html( $title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <br/>
                        <small>Paket proizvoda</small><br/>
                        <select name="question_package[<?php echo $i; ?>]">
                            <option value="">-</option>
                            <?php foreach ( $all_products as $pid => $title ) : ?>
                                <option value="<?php echo esc_attr( $pid ); ?>" <?php selected( isset( $q['package'] ) && intval( $q['package'] ) === $pid ); ?>><?php echo esc_html( $title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <br/>
                        <small>Objašnjenje</small><br/>
                        <?php
                        $note = isset( $q['note'] ) ? $q['note'] : '';
                        $eid  = 'question_note_' . $i;
                        wp_editor( $note, $eid, array(
                            'textarea_name' => "question_note[$i]",
                            'teeny'         => true,
                            'media_buttons' => false,
                            'textarea_rows' => 3,
                        ) );
                        ?>
                    </td>
                </tr>
                <?php endfor; ?>
                </tbody>
                <tbody>
            <tr>
                <th>Broj pitanja po stranici</th>
                <td><input type="number" name="questions_per_page" value="<?php echo esc_attr( $per_page ); ?>" min="1" class="small-text" /></td>
            </tr>
            <tr>
                <th>Univerzalni paket proizvoda</th>
                <td>
                    <select name="universal_package">
                        <option value="">-</option>
                        <?php foreach ( $all_products as $pid => $title ) : ?>
                            <option value="<?php echo esc_attr( $pid ); ?>" <?php selected( intval( $universal_package ) === $pid ); ?>><?php echo esc_html( $title ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
                <tr>
                    <th>Prikaži log grešaka</th>
                    <td>
                        <label><input type="checkbox" name="hprl_debug_log" value="1" <?php checked( $debug_log ); ?> /> Omogući prikaz loga</label>
                    </td>
                </tr>
                <tr>
                    <th>GitHub token</th>
                    <td>
                        <input type="text" name="github_token" value="<?php echo esc_attr( $github_token ); ?>" class="regular-text" />
                        <br/><small>Personal access token za ažuriranje sa privatnog repozitorijuma</small>
                    </td>
                </tr>
                <tr>
                    <th>Tekst za 0-3 "Da" odgovora</th>
                    <td><?php wp_editor( $status_texts['low'], 'status_low', array( 'textarea_name' => 'status_low', 'teeny' => true, 'media_buttons' => false, 'textarea_rows' => 3 ) ); ?></td>
                </tr>
                <tr>
                    <th>Tekst za 4-6 "Da" odgovora</th>
                    <td><?php wp_editor( $status_texts['mid'], 'status_mid', array( 'textarea_name' => 'status_mid', 'teeny' => true, 'media_buttons' => false, 'textarea_rows' => 3 ) ); ?></td>
                </tr>
                <tr>
                    <th>Tekst za 7+ "Da" odgovora</th>
                    <td><?php wp_editor( $status_texts['high'], 'status_high', array( 'textarea_name' => 'status_high', 'teeny' => true, 'media_buttons' => false, 'textarea_rows' => 3 ) ); ?></td>
                </tr>
                </tbody>
            </table>
            <p><button type="button" id="hprl-add-question" class="button">Dodaj novo pitanje</button></p>
            <p><input type="submit" name="hprl_save_questions" class="button-primary" value="Sačuvaj"></p>
            <span id="hprl-products-options" style="display:none;"><?php echo $options_html; ?></span>
        </form>
        <script>
        jQuery(document).ready(function($){
            $('#hprl-add-question').on('click', function(){
                var index = $('#hprl-questions-body tr').length;
                var opts = $('#hprl-products-options').html();
                var row = '<tr>'+
                          '<th>Pitanje '+(index+1)+'</th>'+
                          '<td>'+
                          '<input type="text" name="question_text['+index+']" class="regular-text" />'+
                          '<br/><small>Odgovori (zarezom odvojeni, prvo \\"Da\\" pa \\"Ne\\")</small><br/>'+
                          '<input type="text" name="question_answers['+index+']" class="regular-text" />'+
                          '<br/><small>Glavni proizvod</small><br/>'+
                          '<select name="question_main['+index+']">'+opts+'</select>'+
                          '<br/><small>Dodatni proizvod</small><br/>'+
                          '<select name="question_extra['+index+']">'+opts+'</select>'+
                          '<br/><small>Paket proizvoda</small><br/>'+
                          '<select name="question_package['+index+']">'+opts+'</select>'+
                          '<br/><small>Objašnjenje</small><br/>'+
                          '<textarea name="question_note['+index+']" rows="3"></textarea>'+
                          '</td>'+
                          '</tr>';
                $('#hprl-questions-body').append(row);
            });
        });
        </script>
    </div>
    <?php
}

function hprl_results_page() {
    require_once HPRL_DIR . 'includes/results-table.php';

    $table = new HPRL_Results_Table();
    $table->process_bulk_action();
    $table->prepare_items();

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Rezultati</h1>
        <a href="?page=hprl-results&export=1" class="page-title-action">Export CSV</a>
        <form method="post">
            <?php
            wp_nonce_field( 'bulk-' . $table->_args['plural'] );
            $table->search_box( 'Pretraga', 'hprl-search' );
            $table->display();
            ?>
        </form>
    </div>
    <?php
}
