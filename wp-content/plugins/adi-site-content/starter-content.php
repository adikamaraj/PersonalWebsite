<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Adds missing records only; never rewrites edited content. */
function adi_content_import() {
	if ( ! get_option( 'permalink_structure' ) ) {
		$GLOBALS['wp_rewrite']->set_permalink_structure( '/%postname%/' );
	}
	adi_content_register();
	$count = 0;
	$add = function( $key, $data, $meta = array() ) use ( &$count ) {
		$existing = get_posts( array( 'post_type' => $data['post_type'], 'post_status' => 'any', 'posts_per_page' => 1, 'meta_key' => 'adi_seed_key', 'meta_value' => $key, 'fields' => 'ids' ) );
		if ( $existing ) { return $existing[0]; }
		$id = wp_insert_post( array_merge( array( 'post_status' => 'publish' ), $data ), true );
		if ( is_wp_error( $id ) ) { return 0; }
		update_post_meta( $id, 'adi_seed_key', $key );
		foreach ( $meta as $name => $value ) { update_post_meta( $id, $name, $value ); }
		$count++;
		return $id;
	};
	$home = $add( 'home', array( 'post_type' => 'page', 'post_title' => 'Home', 'post_name' => 'home' ) );
	$about = $add( 'about', array( 'post_type' => 'page', 'post_title' => 'About', 'post_name' => 'about', 'post_content' => '<!-- wp:heading --><h2>Engineering, entrepreneurship, and a lot of questions.</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>I’m Adithya Kamaraj, a doctoral researcher at BFH Business School’s Institute for Marketing &amp; Global Management. My PhD, in collaboration with USI Università della Svizzera italiana, explores how consumers perceive price fairness when AI is involved in marketplace decisions and interactions.</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>Before research, I worked with startups and innovation teams on customer development, product ideas, marketing, and fundraising materials. My background spans mechatronics engineering and a master’s in Management &amp; Engineering at RWTH Business School. I’m drawn to problems that need both careful evidence and practical action.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2>How I like to work.</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Start with the people. Understand what they need before deciding what to build. Prototype early, learn from what happens, and stay willing to change direction. Make the evidence clear, the recommendation useful, and the conversation honest.</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>I care about authenticity, integrity, a growth mindset, innovation, and agility. Tackling complex problems, facilitating creativity, sharing skills, and building trust are the things that keep me interested. And yes, I still occasionally walk into a room and forget why I’m there.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2>The path so far.</h2><!-- /wp:heading -->
<!-- wp:list --><ul><li><strong>2024–present · BFH Business School, Switzerland</strong><br>Doctoral research in AI, consumer behaviour, and price fairness. PhD Network Lead, Business School.</li><li><strong>2023 · Ionkraft, Germany</strong><br>Business development and marketing internship with a packaging technology startup.</li><li><strong>2022 · Zario, Switzerland</strong><br>Entrepreneur in Residence internship, working on customer development, marketing, and fundraising.</li><li><strong>2018–2020 · Forge Innovation &amp; Ventures, India</strong><br>Innovation Associate, then Innovation Lead. Product innovation, prototyping, and team mentoring.</li></ul><!-- /wp:list -->
<!-- wp:heading --><h2>Learning, in a few chapters.</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>M.Sc. Management &amp; Engineering in Technology, Innovation, Marketing &amp; Entrepreneurship · RWTH Business School, 2023, top 15%.<br>B.E. Mechatronics Engineering · Kumaraguru College of Technology, Anna University, 2018.<br>Further learning in product management, machine learning, and managing technology &amp; innovation.</p><!-- /wp:paragraph -->' ) );
	$writing = $add( 'writing', array( 'post_type' => 'page', 'post_title' => 'Writing', 'post_name' => 'writing' ) );
	if ( $home && $writing ) {
		update_option( 'show_on_front', 'page' ); update_option( 'page_on_front', $home ); update_option( 'page_for_posts', $writing );
	}
	$spaces = array( 'design-ai' => 'Design & AI', 'research' => 'Research', 'startups-business' => 'Startups & Business' );
	$term_ids = array();
	foreach ( $spaces as $slug => $name ) {
		$term = term_exists( $slug, 'adi_space' );
		if ( ! $term ) { $term = wp_insert_term( $name, 'adi_space', array( 'slug' => $slug ) ); }
		if ( ! is_wp_error( $term ) ) { $term_ids[ $slug ] = (int) $term['term_id']; }
	}
	$projects = array(
		array( 'pricing', 'When AI sets the price', 'research', 'How does AI involvement change what consumers consider a fair price?', '2024–present', 'Doctoral researcher', 'PhD research', 'AI × pricing × consumer behaviour', '<h2>The question</h2><p>AI increasingly takes part in interactions between companies and customers, from chatbots to humanoid robots. My research asks how that involvement shapes consumers’ judgments of price fairness.</p><h2>The work</h2><p>At BFH Business School’s Institute for Marketing &amp; Global Management, I study these perceptions within AI-infused marketplace transactions. The project connects consumer behaviour with questions that matter to businesses introducing AI into customer interactions.</p><h2>Research in progress</h2><p>This is an ongoing PhD project. The conference work with Sven Feurer and Elisa Konya-Baumbach examines speciesist beliefs and fairness judgments in AI-determined pricing. Conference records are listed in the Research space; these are not presented as completed journal publications.</p><h2>Why it matters</h2><p>Understanding a customer’s response to AI requires more than knowing whether a system works. It also means examining how people interpret the decisions it makes.</p>', 'https://www.bfh.ch/en/about-bfh/people/r5a522s6gztj/' ),
		array( 'canvas', 'From ideas to testable concepts', 'design-ai', 'A concept generation canvas to help innovation teams turn possibilities into decisions.', '2019–2020', 'Innovation Lead · Forge', 'Innovation tool', 'Problem validation → prototype', '<h2>The challenge</h2><p>Early product ideas need a way to move from possibility to an informed next step. At Forge Innovation &amp; Ventures, I worked with teams moving through problem validation, customer development, and prototype creation.</p><h2>My contribution</h2><p>I created the Concepts Generation Canvas as part of Forge’s innovation toolkit, helping teams evaluate new ideas for feasibility and market readiness. This sat within my work leading the design thinking and innovation cycle for more than 20 hardware product innovations.</p><h2>The approach</h2><p>Make the idea explicit. Look at the customer problem, the technical feasibility, and the assumptions that need testing. Give teams a shared structure for discussing what to prototype next.</p><h2>What I take forward</h2><p>A useful tool helps a team think together. Its value comes from the decisions and experiments it makes possible, rather than how polished the canvas looks.</p>', '' ),
		array( 'launch', 'A launch moment for Zario', 'startups-business', 'Customer development, marketing, and a Product Hunt launch with a Swiss startup.', '2022', 'Entrepreneur in Residence intern', 'Startup collaboration', '#1 Product of the Day', '<h2>The context</h2><p>During my Entrepreneur in Residence internship at Zario in Zurich, I worked with the CEO and CMO across customer development, marketing, and fundraising initiatives.</p><h2>My contribution</h2><p>I helped lead the Product Hunt campaign, contributing to a launch that achieved #1 Product of the Day. The experience connected audience development with the practical coordination needed to bring a campaign to market.</p><h2>Working across the business</h2><p>Startup work meant moving between questions about customers, communication, and growth. I supported the team’s broader initiatives alongside the launch.</p><h2>The lesson</h2><p>A launch is a useful moment to learn about the people a product hopes to serve. Attention creates an opportunity; understanding those people is what comes next.</p>', '' ),
		array( 'markets', 'Business insights for packaging innovation', 'startups-business', 'Market research, investor materials, and operational support at Ionkraft.', '2023', 'Business development & marketing intern', 'Business development', 'Markets → messaging → decisions', '<h2>The context</h2><p>Ionkraft is a packaging technology startup based in Aachen. During my internship, I supported business development and marketing activities alongside the founders and managing director.</p><h2>My contribution</h2><p>I conducted market and competitor research to identify opportunities and inform strategic decisions. I also supported investor pitch and read decks, brand communication, and the operationalisation of procurement processes in Asana.</p><h2>Connecting the pieces</h2><p>The work moved between an external view of the market and the internal processes that help a startup act. It taught me to communicate findings in a form that makes the next decision easier.</p>', '' ),
		array( 'hardware', 'Building with innovation teams', 'design-ai', 'Mentoring hardware, IoT, and machine learning teams from problem to prototype.', '2018–2020', 'Innovation Associate & Lead · Forge', 'Design & technology', '10+ teams mentored', '<h2>The work</h2><p>At Forge Innovation &amp; Ventures in Coimbatore, I mentored more than ten innovation teams working on prototype development, with a focus on IoT and machine learning.</p><h2>My contribution</h2><p>I delivered workshops on designing and testing value propositions, ideas worth prototyping, machine learning, and IoT. I also helped organise the Smart India Hardware Hackathon at Forge in 2018 and 2019.</p><h2>From engineering to people</h2><p>My mechatronics background gave me a foundation in how things work. Working with innovation teams added another question: who needs this, and how do we find out?</p>', '' ),
		array( 'circular', 'Rethinking the packaging value chain', 'research', 'A master’s thesis exploring a conceptual framework for circular plastic packaging.', '2023', 'Master’s researcher · RWTH', 'Master’s thesis', 'Circular economy × packaging', '<h2>The research</h2><p>My master’s thesis investigated the circular economy and plastic packaging landscape, developing a conceptual framework for a circular plastic packaging value chain.</p><h2>The lens</h2><p>The project asked me to look beyond an individual product and consider the relationships across a value chain. It connected technology, business, and sustainability.</p><h2>A continuing interest</h2><p>I see sustainability as a responsibility to people, society, and the environment. That perspective continues to shape the questions I bring to innovation and research.</p>', '' ),
	);
	foreach ( $projects as $i => $p ) {
		list( $key, $title, $space, $excerpt, $year, $role, $format, $outcome, $content, $source ) = $p;
		$id = $add( 'project-' . $key, array( 'post_type' => 'adi_project', 'post_title' => $title, 'post_name' => sanitize_title( $title ), 'post_excerpt' => $excerpt, 'post_content' => $content, 'menu_order' => $i ), array( 'adi_year' => $year, 'adi_role' => $role, 'adi_format' => $format, 'adi_outcome' => $outcome, 'adi_visual' => $key, 'adi_source_url' => $source ) );
		if ( $id && isset( $term_ids[ $space ] ) && ! has_term( '', 'adi_space', $id ) ) { wp_set_object_terms( $id, array( $term_ids[ $space ] ), 'adi_space' ); }
	}
	$publications = array(
		array( 'emac-2026', 'When AI Sets the Price: How Speciesist Beliefs Shape Consumers’ Price Fairness Judgments', '55th Annual Conference of the European Marketing Academy (EMAC)', 'https://arbor.bfh.ch/handle/arbor/47793' ),
		array( 'ama-2026', 'They Not Like Us: Speciesist Beliefs and Consumer Perceptions of Price Fairness in Artificial Intelligence Determined Pricing', '2026 AMA Winter Academic Conference', 'https://www.bfh.ch/en/about-bfh/people/r5a522s6gztj/' ),
	);
	foreach ( $publications as $p ) {
		$add( $p[0], array( 'post_type' => 'adi_publication', 'post_title' => $p[1], 'post_excerpt' => $p[2], 'post_content' => '<p>Conference work by Adithya Kamaraj, Sven Feurer, and Elisa Konya-Baumbach. See the linked institutional record for publication details.</p>' ), array( 'adi_year' => '2026', 'adi_format' => 'Conference contribution', 'adi_citation' => 'Kamaraj, A., Feurer, S., & Konya-Baumbach, E. (2026). ' . $p[1] . '. ' . $p[2] . '.', 'adi_source_url' => $p[3] ) );
	}
	foreach ( array( 'AI & Business', 'Pricing & Fairness', 'Building & Learning' ) as $name ) { if ( ! term_exists( $name, 'category' ) ) { wp_insert_term( $name, 'category' ); } }
	foreach ( array( 'What makes an AI decision feel fair?', 'From a useful AI demo to a useful business', 'The questions to ask before building' ) as $i => $title ) {
		$add( 'essay-draft-' . $i, array( 'post_type' => 'post', 'post_status' => 'draft', 'post_title' => $title, 'post_content' => '<p>Draft idea for review. Start with a concrete example, explain the question, add the evidence, and finish with implications for a business decision.</p>' ) );
	}
	$sample = get_page_by_path( 'hello-world', OBJECT, 'post' );
	if ( $sample && 'Hello world!' === $sample->post_title && str_contains( $sample->post_content, 'Welcome to WordPress' ) ) { wp_update_post( array( 'ID' => $sample->ID, 'post_status' => 'draft' ) ); }
	if ( ! get_option( 'adi_imported' ) ) { update_option( 'blogname', 'Adithya Kamaraj' ); update_option( 'blogdescription', 'AI, human behaviour, and business. Curiosity, put to work.' ); update_option( 'adi_imported', true ); }
	flush_rewrite_rules();
	return $count;
}
