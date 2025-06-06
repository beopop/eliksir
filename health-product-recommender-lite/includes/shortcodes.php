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
    $debug_log = intval( get_option( 'hprl_debug_log', 0 ) );
    $per_page  = intval( get_option( 'hprl_questions_per_page', 3 ) );
    if ( $per_page < 1 ) $per_page = 1;
    $question_pages = array_chunk( $questions, $per_page );
    $combos_out = array();
    foreach ( $combos as $c ) {
        if ( empty( $c['answers'] ) ) {
            continue;
        }
        if ( is_array( $c['answers'] ) ) {
            $keys = hprl_cartesian_product( array_map( function( $v ) { return (array) $v; }, $c['answers'] ) );
            foreach ( $keys as $k ) {
                $combos_out[ implode( '|', $k ) ] = array(
                    'cheap'   => $c['cheap'],
                    'premium' => $c['premium'],
                    'note'    => isset( $c['note'] ) ? $c['note'] : ''
                );
            }
        } else {
            $combos_out[ $c['answers'] ] = array(
                'cheap'   => $c['cheap'],
                'premium' => $c['premium'],
                'note'    => isset( $c['note'] ) ? $c['note'] : ''
            );
        }
    }

    $product_ids = array();
    foreach ( array( $products['cheap'], $products['premium'] ) as $pid ) {
        if ( $pid ) {
            $product_ids[] = $pid;
        }
    }
    foreach ( $combos_out as $c ) {
        if ( ! empty( $c['cheap'] ) ) {
            $product_ids[] = $c['cheap'];
        }
        if ( ! empty( $c['premium'] ) ) {
            $product_ids[] = $c['premium'];
        }
    }
    $product_ids = array_unique( $product_ids );
    $product_data = array();
    foreach ( $product_ids as $pid ) {
        $prod = wc_get_product( $pid );
        if ( $prod ) {
            $img  = wp_get_attachment_image_url( $prod->get_image_id(), 'medium' );
            $product_data[ $pid ] = array(
                'img'   => $img ? $img : '',
                'price' => $prod->get_price_html(),
                'name'  => $prod->get_name(),
            );
        }
    }

    ob_start();
    ?>
    <div id="hprl-quiz">
        <?php $step = 1; ?>
        <div class="hprl-step" data-step="<?php echo $step; ?>">
            <label>Ime*<br>
                <input type="text" id="hprl-first-name" required>
                <span class="hprl-error" id="hprl-first-name-error"></span>
            </label>
            <label>Prezime*<br>
                <input type="text" id="hprl-last-name" required>
                <span class="hprl-error" id="hprl-last-name-error"></span>
            </label>
            <label>Email*<br>
                <input type="email" id="hprl-email" required>
                <span class="hprl-error" id="hprl-email-error"></span>
            </label>
            <label>Telefon*<br>
                <input type="tel" id="hprl-phone" pattern="[0-9]+" title="Samo brojevi" required>
                <span class="hprl-error" id="hprl-phone-error"></span>
            </label>
            <label>Godina rodjenja*<br>
                <input type="number" id="hprl-year" required>
                <span class="hprl-error" id="hprl-year-error"></span>
            </label>
            <label>Mesto stanovanja<br>
                <input type="text" id="hprl-location">
            </label>
            <button class="hprl-next">Dalje</button>
        </div>
        <?php $q_index = 0; foreach ( $question_pages as $p_idx => $page ) : $step++; ?>
        <div class="hprl-step" data-step="<?php echo $step; ?>" style="display:none;">
            <?php foreach ( $page as $q ) : ?>
                <div class="hprl-question-group" data-question="<?php echo $q_index; ?>">
                    <p><?php echo esc_html( $q['text'] ); ?></p>
                    <?php foreach ( $q['answers'] as $a_idx => $ans ) : ?>
                        <label class="hprl-answer">
                            <input type="radio" name="q<?php echo $q_index; ?>" class="hprl-question" data-index="<?php echo $a_idx; ?>" value="<?php echo esc_attr( $ans ); ?>" required>
                            <span><?php echo esc_html( $ans ); ?></span>
                        </label>
                    <?php endforeach; ?>
                    <span class="hprl-error"></span>
                </div>
            <?php $q_index++; endforeach; ?>
            <button class="hprl-next">Dalje</button>
        </div>
        <?php endforeach; $step++; ?>
        <div class="hprl-step" data-step="<?php echo $step; ?>" style="display:none;">
            <p>Preporucujemo sledece proizvode:</p>
            <div class="hprl-products">
                <?php
                $cheap_data   = isset( $product_data[ $products['cheap'] ] ) ? $product_data[ $products['cheap'] ] : array( 'img' => '', 'price' => '', 'name' => '' );
                $premium_data = isset( $product_data[ $products['premium'] ] ) ? $product_data[ $products['premium'] ] : array( 'img' => '', 'price' => '', 'name' => '' );
                ?>
                <button class="hprl-select" data-type="cheap" data-product="<?php echo esc_attr( $products['cheap'] ); ?>">
                    <?php if ( $cheap_data['img'] ) : ?>
                        <img src="<?php echo esc_url( $cheap_data['img'] ); ?>" alt="">
                    <?php endif; ?>
                    <?php if ( $cheap_data['name'] ) : ?>
                        <span class="hprl-name"><?php echo esc_html( $cheap_data['name'] ); ?></span>
                    <?php endif; ?>
                    <span class="hprl-price"><?php echo wp_kses_post( $cheap_data['price'] ); ?></span>
                    <span class="hprl-label">Pakovanje za mesec dana</span>
                </button>
                <button class="hprl-select" data-type="premium" data-product="<?php echo esc_attr( $products['premium'] ); ?>">
                    <?php if ( $premium_data['img'] ) : ?>
                        <img src="<?php echo esc_url( $premium_data['img'] ); ?>" alt="">
                    <?php endif; ?>
                    <?php if ( $premium_data['name'] ) : ?>
                        <span class="hprl-name"><?php echo esc_html( $premium_data['name'] ); ?></span>
                    <?php endif; ?>
                    <span class="hprl-price"><?php echo wp_kses_post( $premium_data['price'] ); ?></span>
                    <span class="hprl-label">Jeftinija cena za dužu upotrebu</span>
                </button>
            </div>
            <div id="hprl-note" class="hprl-note" style="display:none;"></div>
        </div>
        <div id="hprl-debug-container" style="display:none;">
            <label><input type="checkbox" id="hprl-debug-toggle"> Prikaži log greške</label>
            <pre id="hprl-debug-log" style="display:none;"></pre>
        </div>
    </div>
    <?php
    wp_enqueue_style( 'hprl-style', HPRL_URL . 'assets/css/style.css', array(), '1.0' );
    wp_enqueue_script( 'hprl-script', HPRL_URL . 'assets/js/script.js', array(), '1.0', true );
    wp_localize_script( 'hprl-script', 'hprlData', array(
        'ajaxurl'  => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'hprl_nonce' ),
        'cheap'    => $products['cheap'],
        'premium'  => $products['premium'],
        'checkout' => wc_get_checkout_url(),
        'cart_url' => wc_get_cart_url(),
        'combos'   => $combos_out,
        'products' => $product_data,
        'debug'    => $debug_log
    ) );
    return ob_get_clean();
}
