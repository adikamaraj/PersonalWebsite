<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Upgrade only known starter fragments; preserve edits, URLs, terms and publication records. */
function adi_content_update_messaging() {
    if ('1.2.0' === get_option('adi_messaging_content_version')) { return; }
    $replacements = array(
        'about' => array(
            '<!-- wp:paragraph --><p>I’m Adithya Kamaraj, a doctoral researcher at BFH Business School’s Institute for Marketing &amp; Global Management. My PhD, in collaboration with USI Università della Svizzera italiana, explores how consumers perceive price fairness when AI is involved in marketplace decisions and interactions.</p><!-- /wp:paragraph -->' => '<!-- wp:paragraph --><p>I\'m Adithya Kamaraj, a doctoral researcher at BFH Business School\'s Institute for Marketing &amp; Global Management. My PhD, in collaboration with USI Università della Svizzera italiana, investigates consumer behaviour and perceptions of fairness in AI-driven marketplace interactions.</p><!-- /wp:paragraph -->',
            '<!-- wp:paragraph --><p>Before research, I worked with startups and innovation teams on customer development, product ideas, marketing, and fundraising materials. My background spans mechatronics engineering and a master’s in Management &amp; Engineering at RWTH Business School. I’m drawn to problems that need both careful evidence and practical action.</p><!-- /wp:paragraph -->' => '<!-- wp:paragraph --><p>Before entering academia, I spent two years at Forge Innovation &amp; Ventures, working with early-stage innovation teams on customer development, value proposition design and product innovation. I applied Lean Startup and Design Thinking principles to help teams move from customer problems to testable ideas.</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>My background in mechatronics engineering and management connects technological possibilities with consumer needs. I\'m particularly interested in helping AI startups understand the people they\'re building for, validate their assumptions and develop products that deliver real customer value.</p><!-- /wp:paragraph -->',
            '<!-- wp:paragraph --><p>Start with the people. Understand what they need before deciding what to build. Prototype early, learn from what happens, and stay willing to change direction. Make the evidence clear, the recommendation useful, and the conversation honest.</p><!-- /wp:paragraph -->' => '<!-- wp:paragraph --><p>Start with the people. Understand what they need before deciding what to build. Combine customer discovery with Lean Startup and Design Thinking to challenge assumptions, prototype early and learn from real feedback. Stay willing to change direction. Make the evidence clear, the recommendation useful, and the conversation honest.</p><!-- /wp:paragraph -->',
            'Innovation Associate, then Innovation Lead. Product innovation, prototyping, and team mentoring.' => 'Innovation Associate, then Innovation Lead. Two years supporting early-stage innovation through customer development, Lean Startup, Design Thinking, value proposition development and team mentoring.'
        ),
        'project-canvas' => array(
            '<p>I created the Concepts Generation Canvas as part of Forge’s innovation toolkit, helping teams evaluate new ideas for feasibility and market readiness. This sat within my work leading the design thinking and innovation cycle for more than 20 hardware product innovations.</p>' => '<p>I created the Concepts Generation Canvas as part of Forge’s innovation toolkit, helping teams connect customer problems with new ideas and evaluate their feasibility and market readiness. This sat within my work leading the Design Thinking and innovation cycle for more than 20 hardware product innovations.</p>',
            '<p>Make the idea explicit. Look at the customer problem, the technical feasibility, and the assumptions that need testing. Give teams a shared structure for discussing what to prototype next.</p>' => '<p>Start with the customer problem. Use Design Thinking to develop ideas, then make the assumptions explicit and test them through customer development and early prototypes. The canvas gave teams a shared structure for deciding which concepts to validate next.</p>'
        ),
        'project-pricing' => array(
            '<p>At BFH Business School’s Institute for Marketing &amp; Global Management, I study these perceptions within AI-infused marketplace transactions. The project connects consumer behaviour with questions that matter to businesses introducing AI into customer interactions.</p>' => '<p>At BFH Business School’s Institute for Marketing &amp; Global Management, I study these perceptions within AI-infused marketplace transactions. The approach uses consumer research to understand how people interpret AI-driven decisions, connecting questions about behaviour and fairness with the choices businesses face when introducing AI into customer interactions.</p>'
        ),
        'project-launch' => array(
            '<p>Startup work meant moving between questions about customers, communication, and growth. I supported the team’s broader initiatives alongside the launch.</p>' => '<p>Alongside the launch, I worked on customer development, helping the team understand the people it hoped to serve. This work connected customer needs with marketing and go-to-market questions, while I also supported fundraising initiatives.</p>'
        ),
        'project-markets' => array(
            '<p>I conducted market and competitor research to identify opportunities and inform strategic decisions. I also supported investor pitch and read decks, brand communication, and the operationalisation of procurement processes in Asana.</p>' => '<p>I conducted market research and competitive analysis to assess business opportunities and inform strategic decisions. I also supported investor pitch and read decks, brand communication, and the operationalisation of procurement processes in Asana.</p>'
        ),
        'project-hardware' => array(
            '<p>At Forge Innovation &amp; Ventures in Coimbatore, I mentored more than ten innovation teams working on prototype development, with a focus on IoT and machine learning.</p>' => '<p>At Forge Innovation &amp; Ventures in Coimbatore, I mentored more than ten innovation teams working on prototype development, with a focus on IoT and machine learning. Lean Startup and Design Thinking helped connect customer problems, value propositions and early prototypes, so teams could test assumptions as they developed their ideas.</p>'
        )
    );
    $excerpts = array(
        'project-canvas' => array('A concept generation canvas to help innovation teams turn possibilities into decisions.', 'Applying Design Thinking, customer development and structured validation to turn early-stage ideas into testable concepts.'),
        'project-pricing' => array('How does AI involvement change what consumers consider a fair price?', 'Exploring how consumers perceive and evaluate AI-driven decisions through behavioural research.'),
        'project-launch' => array('Customer development, marketing, and a Product Hunt launch with a Swiss startup.', 'Customer development and go-to-market experience at an early-stage Swiss startup.')
    );
    $order = array('project-canvas'=>0, 'project-pricing'=>1, 'project-launch'=>2, 'project-markets'=>3, 'project-hardware'=>4, 'project-circular'=>5);
    foreach (array_merge(array('about'), array_keys($order)) as $key) {
        $posts = get_posts(array('post_type'=>'about' === $key ? 'page' : 'adi_project', 'post_status'=>'any', 'numberposts'=>1, 'meta_key'=>'adi_seed_key', 'meta_value'=>$key));
        if (!$posts) { continue; }
        $post = $posts[0];
        $update = array('ID'=>$post->ID);
        $content = strtr($post->post_content, $replacements[$key] ?? array());
        if ($content !== $post->post_content) { $update['post_content'] = $content; }
        if (isset($excerpts[$key]) && $post->post_excerpt === $excerpts[$key][0]) { $update['post_excerpt'] = $excerpts[$key][1]; }
        if (isset($order[$key]) && (int)$post->menu_order !== $order[$key]) { $update['menu_order'] = $order[$key]; }
        if (count($update) === 1) { continue; }
        // Retain the original fields privately, including Order which WP revisions omit.
        if (!metadata_exists('post', $post->ID, '_adi_messaging_backup_1_2_0')) {
            add_post_meta($post->ID, '_adi_messaging_backup_1_2_0', array('post_content'=>$post->post_content, 'post_excerpt'=>$post->post_excerpt, 'menu_order'=>$post->menu_order), true);
        }
        wp_save_post_revision($post->ID);
        $result = wp_update_post(wp_slash($update), true);
        if (is_wp_error($result)) { return; }
    }
    update_option('adi_messaging_content_version', '1.2.0', false);
}
add_action('init', 'adi_content_update_messaging', 30);
