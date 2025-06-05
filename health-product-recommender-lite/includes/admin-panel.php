<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'hprl_admin_menu' );
function hprl_admin_menu() {
    add_menu_page( 'Health Quiz', 'Health Quiz', 'manage_options', 'hprl-questions', 'hprl_questions_page', 'dashicons-heart' );
    add_submenu_page( 'hprl-questions', 'Pitanja', 'Pitanja', 'manage_options', 'hprl-questions', 'hprl_questions_page' );
    add_submenu_page( 'hprl-questions', 'Rezultati', 'Rezultati', 'manage_options', 'hprl-results', 'hprl_results_page' );
}

function hprl_questions_page() {
    if ( isset( $_POST['hprl_save_questions'] ) ) {
        check_admin_referer( 'hprl_save_questions' );
        $questions = array();
        for ( $i = 0; $i < 4; $i++ ) {
            $questions[$i] = sanitize_text_field( $_POST['question'][$i] );
        }
        update_option( 'hprl_questions', $questions );
        $products['cheap'] = intval( $_POST['cheap_product'] );
        $products['premium'] = intval( $_POST['premium_product'] );
        update_option( 'hprl_products', $products );
        echo '<div class="updated"><p>Sačuvano.</p></div>';
    }
    $questions = get_option( 'hprl_questions', array(
        'Koliko cesto osecate umor?',
        'Da li imate problema sa varenjem?',
        'Koliko sati spavate?',
        'Da li osecate stres?'
    ) );
    $products = get_option( 'hprl_products', array( 'cheap' => '', 'premium' => '' ) );
    ?>
    <div class="wrap">
        <h1>Pitanja</h1>
        <form method="post">
            <?php wp_nonce_field( 'hprl_save_questions' ); ?>
            <table class="form-table">
                <?php foreach ( $questions as $idx => $q ) : ?>
                <tr>
                    <th>Pitanje <?php echo $idx+1; ?></th>
                    <td><input type="text" name="question[<?php echo $idx; ?>]" value="<?php echo esc_attr( $q ); ?>" class="regular-text" required></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <th>ID jeftinijeg proizvoda</th>
                    <td><input type="number" name="cheap_product" value="<?php echo esc_attr( $products['cheap'] ); ?>" class="small-text" required></td>
                </tr>
                <tr>
                    <th>ID skupljeg proizvoda</th>
                    <td><input type="number" name="premium_product" value="<?php echo esc_attr( $products['premium'] ); ?>" class="small-text" required></td>
                </tr>
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
