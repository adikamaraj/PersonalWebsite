<?php
// Loaded through verify-site.cjs after local wp-load.php; all writes are rolled back.
if (!in_array(wp_parse_url(home_url(), PHP_URL_HOST), array('127.0.0.1', 'localhost'), true)) {
    throw new RuntimeException('Messaging regression checks require local WordPress.');
}
$check = function($ok, $message) {
    if (!$ok) { throw new RuntimeException($message); }
};
$about = get_posts(array('post_type'=>'page', 'meta_key'=>'adi_seed_key', 'meta_value'=>'about', 'numberposts'=>1))[0];
$original_paragraphs = array(
    'I’m Adithya Kamaraj, a doctoral researcher at BFH Business School’s Institute for Marketing &amp; Global Management. My PhD, in collaboration with USI Università della Svizzera italiana, explores how consumers perceive price fairness when AI is involved in marketplace decisions and interactions.',
    'Before research, I worked with startups and innovation teams on customer development, product ideas, marketing, and fundraising materials. My background spans mechatronics engineering and a master’s in Management &amp; Engineering at RWTH Business School. I’m drawn to problems that need both careful evidence and practical action.',
    'Start with the people. Understand what they need before deciding what to build. Prototype early, learn from what happens, and stay willing to change direction. Make the evidence clear, the recommendation useful, and the conversation honest.',
    'A personal paragraph to preserve.',
    'Education details to preserve.',
);
$original_content = implode("\n\n", array_map(function($text) { return '<!-- wp:paragraph --><p>'.$text.'</p><!-- /wp:paragraph -->'; }, $original_paragraphs));
$publications = serialize(get_posts(array('post_type'=>'adi_publication', 'numberposts'=>-1)));
$user = get_users(array('role'=>'administrator', 'number'=>1))[0];
global $wpdb;
$wpdb->query('START TRANSACTION');
try {
    $blocks = parse_blocks($original_content);
    foreach ($blocks as &$block) {
        if ('core/paragraph' !== $block['blockName']) { continue; }
        $block['attrs']['className'] = 'preserved-style';
        $block['innerHTML'] = "\n".str_replace('<p>', '<p class="preserved-style">', $block['innerHTML'])."\n";
        $block['innerContent'] = array($block['innerHTML']);
    }
    unset($block);
    $marker = '<p>A paragraph edited independently by the owner.</p>';
    $formatted = serialize_blocks($blocks).'<!-- wp:paragraph -->'.$marker.'<!-- /wp:paragraph -->';
    $fixtures = array(
        $formatted,
        '<!-- wp:group --><div class="wp-block-group">'.$formatted.'</div><!-- /wp:group -->',
        preg_replace('~<!--.*?-->~s', '', $formatted),
    );
    foreach ($fixtures as $fixture) {
        wp_update_post(wp_slash(array('ID'=>$about->ID, 'post_content'=>$fixture)));
        update_option('adi_messaging_content_version', '1.2.0');
        adi_content_update_messaging();
        $updated = get_post($about->ID)->post_content;
        $check(str_contains($updated, 'Before entering academia'), 'Formatted Forge paragraph migrated.');
        $check(str_contains($updated, "I'm Adithya Kamaraj"), 'Formatted researcher paragraph migrated.');
        $check(str_contains($updated, 'Combine customer discovery with Lean Startup'), 'Working approach migrated.');
        $check(str_contains($updated, 'mechatronics engineering and management'), 'Third introduction paragraph added.');
        $check(str_contains($updated, $marker), 'Owner paragraph preserved.');
        $check(str_contains($updated, 'preserved-style'), 'Paragraph styling preserved.');
        $check(substr_count($updated, 'Before entering academia') === 1, 'No duplicate introduction.');
        $before = $updated;
        adi_content_update_messaging();
        $check(get_post($about->ID)->post_content === $before, 'Migration is idempotent.');
        if (has_blocks($fixture)) {
            $count = 0;
            $walk = function($blocks) use (&$walk, &$count) {
                foreach ($blocks as $block) {
                    if ('core/paragraph' === $block['blockName']) { $count++; }
                    $walk($block['innerBlocks']);
                }
            };
            $walk(parse_blocks($updated));
            $check($count === 7, 'All biography paragraphs survive block serialisation.');
        }
    }
    $check(serialize(get_posts(array('post_type'=>'adi_publication', 'numberposts'=>-1))) === $publications, 'Publication records unchanged.');
    delete_option('adi_messaging_theme_backup_2_2_0');
    set_theme_mod('adi_about_title', 'Old saved headline');
    set_theme_mod('adi_about_copy', 'Old saved introduction');
    set_theme_mod('adi_contact_email', 'email-kept@example.test');
    wp_set_current_user(0);
    $check(is_wp_error(adi_content_apply_messaging_defaults()), 'Messaging action requires an administrator.');
    $check(get_theme_mod('adi_about_title') === 'Old saved headline', 'Unauthorised action changes nothing.');
    wp_set_current_user($user->ID);
    $check(true === adi_content_apply_messaging_defaults(), 'Administrator can apply messaging.');
    $check(adi_setting('adi_about_title') === 'Validate with consumers before you build.', 'Saved headline override removed.');
    $check(str_contains(adi_setting('adi_about_copy'), 'studying how consumers perceive'), 'Saved introduction override removed.');
    $check(get_theme_mod('adi_contact_email') === 'email-kept@example.test', 'Contact email preserved.');
    $check(get_option('adi_messaging_theme_backup_2_2_0')['adi_about_title'] === 'Old saved headline', 'Previous copy backed up.');
    echo "Passed: formatted, nested and classic biographies; saved Customizer copy; permissions; backups; preserved publications.\n";
} finally {
    $wpdb->query('ROLLBACK');
    wp_cache_flush();
    wp_set_current_user(0);
}
