<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'hprl_admin_menu' );
function hprl_admin_menu() {
    add_menu_page( 'Health Quiz', 'Health Quiz', 'manage_options', 'hprl-questions', 'hprl_questions_page', 'dashicons-heart' );
    add_submenu_page( 'hprl-questions', 'Pitanja', 'Pitanja', 'manage_options', 'hprl-questions', 'hprl_questions_page' );
    add_submenu_page( 'hprl-questions', 'Rezultati', 'Rezultati', 'manage_options', 'hprl-results', 'hprl_results_page' );
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
    $header = array( 'ID','First Name','Last Name','Email','Phone','Birth Year','Location','Answers','Product ID','Date' );

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
            $row['product_id'],
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
                $ans  = isset( $_POST['question_answers'][$i] ) ? sanitize_text_field( $_POST['question_answers'][$i] ) : '';
                $answers = array();
                if ( $ans !== '' ) {
                    $parts = array_map( 'trim', explode( ',', $ans ) );
                    $answers = array_map( 'sanitize_text_field', $parts );
                }
                if ( $text !== '' ) {
                    $questions[] = array( 'text' => $text, 'answers' => $answers );
                }
            }
        }
        update_option( 'hprl_questions', $questions );
        $max_q = count( $questions );

        $per_page = max( 1, intval( $_POST['questions_per_page'] ) );
        update_option( 'hprl_questions_per_page', $per_page );

        $products['cheap']   = intval( $_POST['cheap_product'] );
        $products['premium'] = intval( $_POST['premium_product'] );
        update_option( 'hprl_products', $products );

        $debug_log = isset( $_POST['hprl_debug_log'] ) ? 1 : 0;
        update_option( 'hprl_debug_log', $debug_log );

        $github_token = isset( $_POST['github_token'] ) ? sanitize_text_field( $_POST['github_token'] ) : '';
        update_option( 'hprl_github_token', $github_token );

        $combos = array();
        if ( isset( $_POST['combo_cheap'] ) ) {
            $count_c = count( $_POST['combo_cheap'] );
            for ( $i = 0; $i < $count_c; $i++ ) {
                $answers = array();
                $empty = true;
                for ( $q = 0; $q < $max_q; $q++ ) {
                    $vals = isset( $_POST["combo_q{$q}"][$i] ) ? (array) $_POST["combo_q{$q}"][$i] : array();
                    $vals = array_map( 'intval', array_filter( $vals, 'strlen' ) );
                    if ( ! empty( $vals ) ) {
                        $empty = false;
                    }
                    $answers[] = $vals;
                }
                if ( $empty ) {
                    continue;
                }
                $note = isset( $_POST['combo_note'][ $i ] ) ? wp_kses_post( $_POST['combo_note'][ $i ] ) : '';
                $combos[] = array(
                    'answers' => $answers,
                    'cheap'   => intval( $_POST['combo_cheap'][ $i ] ),
                    'premium' => intval( $_POST['combo_premium'][ $i ] ),
                    'note'    => $note,
                );
            }
        }
        update_option( 'hprl_combos', $combos );

        echo '<div class="updated"><p>Sačuvano.</p></div>';
    }

    $default_questions = array(
        array( 'text' => 'Koliko cesto osecate umor?', 'answers' => array( 'Retko', 'Ponekad', 'Cesto' ) ),
        array( 'text' => 'Da li imate problema sa varenjem?', 'answers' => array( 'Da', 'Ne' ) ),
    );
    $questions = get_option( 'hprl_questions', $default_questions );
    $products  = get_option( 'hprl_products', array( 'cheap' => '', 'premium' => '' ) );
    $combos    = get_option( 'hprl_combos', array() );
    $debug_log = intval( get_option( 'hprl_debug_log', 0 ) );
    $github_token = get_option( 'hprl_github_token', '' );
    $per_page  = intval( get_option( 'hprl_questions_per_page', 3 ) );
    $max_q = count( $questions );
    foreach ( $combos as &$c ) {
        if ( ! is_array( $c['answers'] ) ) {
            $parts = array_pad( explode( '|', $c['answers'] ), $max_q, '' );
            $new = array();
            foreach ( $parts as $p ) {
                $new[] = ( $p === '' ) ? array() : array( intval( $p ) );
            }
            $c['answers'] = $new;
        }
        if ( ! isset( $c['note'] ) ) {
            $c['note'] = '';
        }
    }
    unset( $c );
    if ( empty( $combos ) || ( isset( $combos[0]['answers'] ) && count( $combos[0]['answers'] ) !== $max_q ) ) {
        $combos = hprl_generate_all_combos( $questions );
        update_option( 'hprl_combos', $combos );
    }
    $max_c = count( $combos );

    $all_products = array();
    $prod_posts = get_posts( array( 'post_type' => 'product', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
    foreach ( $prod_posts as $p ) {
        $all_products[ $p->ID ] = $p->post_title;
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
                        <small>Odgovori (zarezom odvojeni)</small><br/>
                        <input type="text" name="question_answers[<?php echo $i; ?>]" value="<?php echo esc_attr( $ans ); ?>" class="regular-text" />
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
                <th>ID jeftinijeg proizvoda (podrazumevano)</th>
                <td>
                    <select name="cheap_product">
                            <option value="">-</option>
                            <?php foreach ( $all_products as $pid => $title ) : ?>
                                <option value="<?php echo esc_attr( $pid ); ?>" <?php selected( intval( $products['cheap'] ) === $pid ); ?>><?php echo esc_html( $title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>ID skupljeg proizvoda (podrazumevano)</th>
                    <td>
                        <select name="premium_product">
                            <option value="">-</option>
                            <?php foreach ( $all_products as $pid => $title ) : ?>
                                <option value="<?php echo esc_attr( $pid ); ?>" <?php selected( intval( $products['premium'] ) === $pid ); ?>><?php echo esc_html( $title ); ?></option>
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
                </tbody>
            </table>
            <p><button type="button" id="hprl-add-question" class="button">Dodaj novo pitanje</button></p>
            <h2>Kombinacije proizvoda</h2>
            <p class="description">Odaberite odgovore za svako pitanje kako biste definisali kombinaciju.</p>
            <table class="form-table">
                <tr>
                    <th>Kombinacija odgovora</th>
                    <th>Jeftiniji proizvod</th>
                    <th>Skuplji proizvod</th>
                    <th>Objašnjenje</th>
                </tr>
                <?php for ( $i = 0; $i < $max_c; $i++ ) :
                    $c = isset( $combos[ $i ] ) ? $combos[ $i ] : array( 'answers' => array(), 'cheap' => '', 'premium' => '', 'note' => '' );
                ?>
                <tr>
                    <td>
                        <?php for ( $q = 0; $q < $max_q; $q++ ) :
                            $current = isset( $c['answers'][ $q ] ) ? (array) $c['answers'][ $q ] : array();
                            $qdata   = isset( $questions[ $q ] ) ? $questions[ $q ] : array( 'answers' => array() );
                        ?>
                        <select name="combo_q<?php echo $q; ?>[<?php echo $i; ?>][]" multiple>
                            <?php foreach ( $qdata['answers'] as $a_idx => $ans ) : ?>
                                <option value="<?php echo $a_idx; ?>" <?php selected( in_array( $a_idx, $current ) ); ?>><?php echo esc_html( $ans ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php endfor; ?>
                    </td>
                    <td>
                        <select name="combo_cheap[<?php echo $i; ?>]">
                            <option value="">-</option>
                            <?php foreach ( $all_products as $pid => $title ) : ?>
                                <option value="<?php echo esc_attr( $pid ); ?>" <?php selected( intval( $c['cheap'] ) === $pid ); ?>><?php echo esc_html( $title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="combo_premium[<?php echo $i; ?>]">
                            <option value="">-</option>
                            <?php foreach ( $all_products as $pid => $title ) : ?>
                                <option value="<?php echo esc_attr( $pid ); ?>" <?php selected( intval( $c['premium'] ) === $pid ); ?>><?php echo esc_html( $title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <?php
                        $note = isset( $c['note'] ) ? $c['note'] : '';
                        $eid  = 'combo_note_' . $i;
                        wp_editor( $note, $eid, array(
                            'textarea_name' => "combo_note[$i]",
                            'teeny'         => true,
                            'media_buttons' => false,
                            'textarea_rows' => 3,
                        ) );
                        ?>
                    </td>
                </tr>
                <?php endfor; ?>
            </table>
            <p><input type="submit" name="hprl_save_questions" class="button-primary" value="Sačuvaj"></p>
        </form>
        <script>
        jQuery(document).ready(function($){
            $('#hprl-add-question').on('click', function(){
                var index = $('#hprl-questions-body tr').length;
                var row = '<tr>'+
                          '<th>Pitanje '+(index+1)+'</th>'+
                          '<td>'+
                          '<input type="text" name="question_text['+index+']" class="regular-text" />'+
                          '<br/><small>Odgovori (zarezom odvojeni)</small><br/>'+
                          '<input type="text" name="question_answers['+index+']" class="regular-text" />'+
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
    global $wpdb;
    if ( isset( $_GET['delete'] ) ) {
        $id = intval( $_GET['delete'] );
        if ( $id > 0 ) {
            $wpdb->delete( HPRL_TABLE, array( 'id' => $id ) );
        }
    }
    $results = $wpdb->get_results( "SELECT * FROM " . HPRL_TABLE . " ORDER BY created_at DESC" );
    ?>
    <div class="wrap">
        <h1>Rezultati</h1>
        <p>
            <a href="?page=hprl-results&export=1" class="button">Export CSV</a>
        </p>
        <table class="widefat">
            <thead>
            <tr>
                <th>ID</th><th>Ime</th><th>Prezime</th><th>Email</th><th>Telefon</th><th>Godina</th><th>Mesto</th><th>Odgovori</th><th>Proizvod</th><th>Datum</th><th>Akcija</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ( $results as $row ) : ?>
                <tr>
                    <td><?php echo esc_html( $row->id ); ?></td>
                    <td><?php echo esc_html( $row->first_name ); ?></td>
                    <td><?php echo esc_html( $row->last_name ); ?></td>
                    <td><?php echo esc_html( $row->email ); ?></td>
                    <td><?php echo esc_html( $row->phone ); ?></td>
                    <td><?php echo esc_html( $row->birth_year ); ?></td>
                    <td><?php echo esc_html( $row->location ); ?></td>
                    <?php $ans = maybe_unserialize( $row->answers ); ?>
                    <?php if ( ! is_array( $ans ) ) $ans = array( $ans ); ?>
                    <td><?php echo esc_html( implode( ',', $ans ) ); ?></td>
                    <td><?php echo esc_html( $row->product_id ); ?></td>
                    <td><?php echo esc_html( $row->created_at ); ?></td>
                    <td><a href="?page=hprl-results&delete=<?php echo intval( $row->id ); ?>" onclick="return confirm('Obrisati ovaj unos?');">Obriši</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
