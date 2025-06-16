<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'health_quiz', 'hprl_quiz_shortcode' );
function hprl_quiz_shortcode() {
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
    $debug_log = intval( get_option( 'hprl_debug_log', 0 ) );
    $per_page  = intval( get_option( 'hprl_questions_per_page', 3 ) );
    if ( $per_page < 1 ) $per_page = 1;
    $question_pages = array_chunk( $questions, $per_page );

    $universal_package = intval( get_option( 'hprl_universal_package', 0 ) );

    $product_ids = array();
    foreach ( $questions as $q ) {
        foreach ( array( $q['main'], $q['extra'], $q['package'] ) as $pid ) {
            if ( $pid ) {
                $product_ids[] = $pid;
            }
        }
    }
    if ( $universal_package ) {
        $product_ids[] = $universal_package;
    }
    $product_ids = array_unique( $product_ids );
    $product_data = array();
    foreach ( $product_ids as $pid ) {
        $prod = wc_get_product( $pid );
        if ( $prod ) {
            $img  = wp_get_attachment_image_url( $prod->get_image_id(), 'medium' );
            $price_html = $prod->get_price_html();
            $price_html = preg_replace( '/<[^>]*vip[^>]*>.*?<\/[^>]+>/i', '', $price_html );
            $price_html = preg_replace( '/Cena za VIP[\s\S]*/i', '', $price_html );
            $price_html = preg_replace( '/\bvip\b[^\d]*\d+[\d.,]*\s*(?:rsd|din|eur|€)?/i', '', $price_html );
            $price_html = trim( $price_html );
            $product_data[ $pid ] = array(
                'img'   => $img ? $img : '',
                'price' => $price_html,
                'name'  => $prod->get_name(),
                'link'  => get_permalink( $pid ),
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
            <button class="hprl-prev">Nazad</button>
            <button class="hprl-next">Dalje</button>
        </div>
        <?php endforeach; $step++; ?>
        <div class="hprl-step" data-step="<?php echo $step; ?>" style="display:none;">
            <h2 class="hprl-results-title">Analiza organizma i savet za poboljšanje vašeg stanja</h2>
            <div id="hprl-explanations" class="hprl-note" style="display:none;"></div>

            <h2 class="hprl-results-title">Preporučujemo proizvode</h2>
            <div class="hprl-products">
                <button class="hprl-select" data-type="main" data-product="">
                    <img src="" alt="" style="display:none;">
                    <span class="hprl-name"></span>
                    <span class="hprl-price"></span>
                    <p class="hprl-desc">Ovo je glavni proizvod zbog analize vašeg organizma koji ga preporučuje.</p>
                    <span class="hprl-label">Glavni proizvod</span>
                    <span class="hprl-buy-now">Kupi sada</span>
                </button>
                <button class="hprl-select" data-type="extra" data-product="">
                    <img src="" alt="" style="display:none;">
                    <span class="hprl-name"></span>
                    <span class="hprl-price"></span>
                    <p class="hprl-desc">Ovo je dodatni proizvod koji preporučujemo radi bržeg oporavka organizma.</p>
                    <span class="hprl-label">Dodatni proizvod</span>
                    <span class="hprl-buy-now">Kupi sada</span>
                </button>
                <button class="hprl-select" data-type="package" data-product="">
                    <img src="" alt="" style="display:none;">
                    <span class="hprl-name"></span>
                    <span class="hprl-price"></span>
                    <p class="hprl-desc">Najeftinije rešenje: kombinacija oba proizvoda u paketu – niža cena.</p>
                    <span class="hprl-label">Paket proizvoda</span>
                    <span class="hprl-buy-now">Kupi sada</span>
                </button>
                <button class="hprl-select" data-type="universal" data-product="" style="display:none;">
                    <img src="" alt="" style="display:none;">
                    <span class="hprl-name"></span>
                    <span class="hprl-price"></span>
                    <p class="hprl-desc">Uzmite sve proizvode po najboljoj ceni i dajte svom organizmu pravu stvar.</p>
                    <span class="hprl-label">Univerzalni paket</span>
                    <span class="hprl-buy-now">Kupi sada</span>
                </button>
            </div>
            <div id="hprl-note" class="hprl-note" style="display:none;"></div>
            <button class="hprl-prev">Nazad</button>
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
        'ajaxurl'   => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'hprl_nonce' ),
        'checkout'  => wc_get_checkout_url(),
        'cart_url'  => wc_get_cart_url(),
        'products'  => $product_data,
        'questions' => $questions,
        'universal' => $universal_package,
        'debug'     => $debug_log
    ) );
    return ob_get_clean();
}
