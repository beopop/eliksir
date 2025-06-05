<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'hprl_admin_menu' );
function hprl_admin_menu() {
    add_menu_page( 'Health Quiz', 'Health Quiz', 'manage_options', 'hprl-questions', 'hprl_questions_page', 'dashicons-heart' );
    add_submenu_page( 'hprl-questions', 'Pitanja', 'Pitanja', 'manage_options', 'hprl-questions', 'hprl_questions_page' );
    add_submenu_page( 'hprl-questions', 'Rezultati', 'Rezultati', 'manage_options', 'hprl-results', 'hprl_results_page' );
}

function hprl_questions_page() {
    $max_q = 6;
    $max_c = 6;
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

        $products['cheap']   = intval( $_POST['cheap_product'] );
        $products['premium'] = intval( $_POST['premium_product'] );
        update_option( 'hprl_products', $products );

        $combos = array();
        if ( isset( $_POST['combo_cheap'] ) ) {
            for ( $i = 0; $i < $max_c; $i++ ) {
                $parts = array();
                $empty = true;
                for ( $q = 0; $q < $max_q; $q++ ) {
                    $val = isset( $_POST["combo_q{$q}"][$i] ) ? sanitize_text_field( $_POST["combo_q{$q}"][$i] ) : '';
                    if ( $val !== '' ) {
                        $empty = false;
                    }
                    $parts[] = $val;
                }
                if ( $empty ) {
                    continue;
                }
                $combos[] = array(
                    'answers' => implode( '|', $parts ),
                    'cheap'   => intval( $_POST['combo_cheap'][ $i ] ),
                    'premium' => intval( $_POST['combo_premium'][ $i ] ),
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
            <table class="form-table">
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
            </table>
            <h2>Kombinacije proizvoda</h2>
            <p class="description">Odaberite odgovore za svako pitanje kako biste definisali kombinaciju.</p>
            <table class="form-table">
                <tr>
                    <th>Kombinacija odgovora</th>
                    <th>Jeftiniji proizvod</th>
                    <th>Skuplji proizvod</th>
                </tr>
                <?php for ( $i = 0; $i < $max_c; $i++ ) :
                    $c = isset( $combos[ $i ] ) ? $combos[ $i ] : array( 'answers' => '', 'cheap' => '', 'premium' => '' );
                    $parts = array_pad( explode( '|', $c['answers'] ), $max_q, '' );
                ?>
                <tr>
                    <td>
                        <?php for ( $q = 0; $q < $max_q; $q++ ) :
                            $current = isset( $parts[ $q ] ) ? $parts[ $q ] : '';
                            $qdata   = isset( $questions[ $q ] ) ? $questions[ $q ] : array( 'answers' => array() );
                        ?>
                        <select name="combo_q<?php echo $q; ?>[<?php echo $i; ?>]">
                            <option value="">-</option>
                            <?php foreach ( $qdata['answers'] as $a_idx => $ans ) : ?>
                                <option value="<?php echo $a_idx; ?>" <?php selected( $current !== '' && intval( $current ) === $a_idx ); ?>><?php echo esc_html( $ans ); ?></option>
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
                </tr>
                <?php endfor; ?>
            </table>
            <p><input type="submit" name="hprl_save_questions" class="button-primary" value="Sačuvaj"></p>
        </form>
    </div>
    <?php
}

function hprl_results_page() {
    global $wpdb;
    if ( isset( $_GET['export'] ) ) {
        $rows = $wpdb->get_results( "SELECT * FROM " . HPRL_TABLE . " ORDER BY created_at DESC", ARRAY_A );
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="hprl-results.csv"');
        $out = fopen('php://output', 'w');
        fputcsv( $out, array('ID','Name','Email','Phone','Birth Year','Location','Answers','Product ID','Date') );
        foreach ( $rows as $row ) {
            fputcsv( $out, $row );
        }
        fclose($out);
        exit;
    }
    $results = $wpdb->get_results( "SELECT * FROM " . HPRL_TABLE . " ORDER BY created_at DESC" );
    ?>
    <div class="wrap">
        <h1>Rezultati</h1>
        <p><a href="?page=hprl-results&export=1" class="button">Export CSV</a></p>
        <table class="widefat">
            <thead>
            <tr>
                <th>ID</th><th>Ime</th><th>Email</th><th>Telefon</th><th>Godina</th><th>Mesto</th><th>Odgovori</th><th>Proizvod</th><th>Datum</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ( $results as $row ) : ?>
                <tr>
                    <td><?php echo esc_html( $row->id ); ?></td>
                    <td><?php echo esc_html( $row->name ); ?></td>
                    <td><?php echo esc_html( $row->email ); ?></td>
                    <td><?php echo esc_html( $row->phone ); ?></td>
                    <td><?php echo esc_html( $row->birth_year ); ?></td>
                    <td><?php echo esc_html( $row->location ); ?></td>
                    <td><?php echo esc_html( implode( ',', maybe_unserialize( $row->answers ) ) ); ?></td>
                    <td><?php echo esc_html( $row->product_id ); ?></td>
                    <td><?php echo esc_html( $row->created_at ); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
