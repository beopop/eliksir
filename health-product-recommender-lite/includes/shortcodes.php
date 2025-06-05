<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'health_quiz', 'hprl_quiz_shortcode' );
function hprl_quiz_shortcode() {
    $default_questions = array(
        array( 'text' => 'Koliko cesto osecate umor?', 'answers' => array( 'Retko', 'Ponekad', 'Cesto' ) ),
        array( 'text' => 'Da li imate problema sa varenjem?', 'answers' => array( 'Da', 'Ne' ) ),
    );
    $questions = get_option( 'hprl_questions', $default_questions );
    $products  = get_option( 'hprl_products', array( 'cheap' => '', 'premium' => '' ) );
    $combos    = get_option( 'hprl_combos', array() );
    $combos_out = array();
    foreach ( $combos as $c ) {
        if ( empty( $c['answers'] ) ) {
            continue;
        }
        if ( is_array( $c['answers'] ) ) {
            $keys = hprl_cartesian_product( array_map( function( $v ) { return (array) $v; }, $c['answers'] ) );
            foreach ( $keys as $k ) {
                $combos_out[ implode( '|', $k ) ] = array( 'cheap' => $c['cheap'], 'premium' => $c['premium'] );
            }
        } else {
            $combos_out[ $c['answers'] ] = array( 'cheap' => $c['cheap'], 'premium' => $c['premium'] );
        }
    }

    ob_start();
    ?>
    <div id="hprl-quiz">
        <div class="hprl-step" data-step="1">
            <label>Ime i prezime*<br><input type="text" id="hprl-name" required></label>
            <label>Email*<br><input type="email" id="hprl-email" required></label>
            <label>Telefon*<br><input type="text" id="hprl-phone" required></label>
            <label>Godina rodjenja*<br><input type="number" id="hprl-year" required></label>
            <label>Mesto stanovanja<br><input type="text" id="hprl-location"></label>
            <button id="hprl-next1">Dalje</button>
        </div>
        <div class="hprl-step" data-step="2" style="display:none;">
            <?php foreach ( $questions as $idx => $q ) : ?>
                <div class="hprl-question-group" data-question="<?php echo $idx; ?>">
                    <p><?php echo esc_html( $q['text'] ); ?></p>
                    <?php foreach ( $q['answers'] as $a_idx => $ans ) : ?>
                        <label>
                            <input type="radio" name="q<?php echo $idx; ?>" class="hprl-question" data-index="<?php echo $a_idx; ?>" value="<?php echo esc_attr( $ans ); ?>" required>
                            <?php echo esc_html( $ans ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            <button id="hprl-next2">Dalje</button>
        </div>
        <div class="hprl-step" data-step="3" style="display:none;">
            <p>Preporucujemo sledece proizvode:</p>
            <div class="hprl-products">
                <button class="hprl-select" data-type="cheap" data-product="<?php echo esc_attr( $products['cheap'] ); ?>">Jeftiniji paket</button>
                <button class="hprl-select" data-type="premium" data-product="<?php echo esc_attr( $products['premium'] ); ?>">Skuplji paket</button>
            </div>
        </div>
    </div>
    <?php
    wp_enqueue_style( 'hprl-style', HPRL_URL . 'assets/css/style.css', array(), '1.0' );
    wp_enqueue_script( 'hprl-script', HPRL_URL . 'assets/js/script.js', array(), '1.0', true );
    wp_localize_script( 'hprl-script', 'hprlData', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'hprl_nonce' ),
        'cheap'   => $products['cheap'],
        'premium' => $products['premium'],
        'checkout'=> wc_get_checkout_url(),
        'cart_url'=> wc_get_cart_url(),
        'combos'  => $combos_out
    ) );
    return ob_get_clean();
}
