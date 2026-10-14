<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function adi_personal_setup() {
 add_theme_support('title-tag'); add_theme_support('post-thumbnails'); add_theme_support('automatic-feed-links'); add_theme_support('responsive-embeds'); add_theme_support('align-wide'); add_theme_support('editor-styles'); add_editor_style('style.css');
 add_theme_support('html5', array('search-form','gallery','caption','style','script'));
}
add_action('after_setup_theme','adi_personal_setup');
// Use text glyphs and avoid loading an external emoji sprite service.
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');
add_filter('emoji_svg_url', '__return_false');
add_action('wp_enqueue_scripts', function() {
 $v=wp_get_theme()->get('Version'); wp_enqueue_style('adi-personal',get_stylesheet_uri(),array(),$v);
 wp_enqueue_script('adi-personal',get_template_directory_uri().'/assets/site.js',array(),$v,array('strategy'=>'defer','in_footer'=>true));
});
add_action('wp_head',function(){
 echo '<link rel="preload" href="'.esc_url(get_template_directory_uri().'/assets/fonts/manrope-latin.woff2').'" as="font" type="font/woff2" crossorigin>';
 if(!defined('WPSEO_VERSION')&&!defined('RANK_MATH_VERSION')&&!defined('AIOSEO_VERSION')){
 $d=is_front_page()?'Consumer behaviour and AI researcher at BFH Business School. Helping early-stage teams understand customer needs and validate value before building.':(is_singular()&&has_excerpt()?get_the_excerpt():get_bloginfo('description')); echo '<meta name="description" content="'.esc_attr(wp_strip_all_tags($d)).'">'; }
});
function adi_setting($key){
 $defaults=array(
 'adi_hero_name'=>'Adithya Kamaraj',
 'adi_hero_descriptor'=>'Consumer Behaviour & AI Researcher',
 'adi_about_title'=>'Validate with consumers before you build.',
 'adi_about_copy'=>"I'm a doctoral researcher at BFH Business School studying how consumers perceive and respond to AI-driven decisions. Drawing on two years in a business incubator and hands-on startup experience, I help early-stage teams understand customer needs, refine value propositions and test assumptions before investing heavily in development.",
 'adi_collaboration_note'=>'Open to selected pro bono collaborations, particularly with AI startups.',
 'adi_contact_email'=>'adithya.kamaraj@gmail.com',
 'adi_contact_title'=>'Have a startup idea worth testing?',
 'adi_contact_note'=>"Working on an AI product or exploring a new market?\n\nI'm open to selected pro bono collaborations with early-stage startups, particularly around customer discovery, consumer research and value proposition development.\n\nIf you're looking to understand your customers better or validate assumptions before building, I'd love to hear what you're working on.",
 'adi_linkedin'=>'https://www.linkedin.com/in/adithya-kamaraj/',
 'adi_bfh'=>'https://www.bfh.ch/en/about-bfh/people/r5a522s6gztj/', 'adi_location'=>'Bern, Switzerland');
 return get_theme_mod($key,$defaults[$key]??'');
}
add_action('customize_register',function($c){
 $c->add_section('adi_personal',array('title'=>'Personal site','priority'=>30));
 $fields=array('adi_hero_name'=>array('Name','text'),'adi_hero_descriptor'=>array('Professional descriptor','text'),'adi_about_title'=>array('Home headline','text'),'adi_about_copy'=>array('Home introduction','textarea'),'adi_collaboration_note'=>array('Home collaboration note','text'),'adi_contact_title'=>array('Contact heading','text'),'adi_contact_email'=>array('Email','email'),'adi_contact_note'=>array('Collaboration invitation','textarea'),'adi_linkedin'=>array('LinkedIn URL','url'),'adi_bfh'=>array('Institutional profile URL','url'),'adi_location'=>array('Current location','text'));
 foreach($fields as $key=>$f){$s='textarea'===$f[1]?'sanitize_textarea_field':('email'===$f[1]?'sanitize_email':('url'===$f[1]?'esc_url_raw':'sanitize_text_field'));$c->add_setting($key,array('default'=>adi_setting($key),'sanitize_callback'=>$s));$c->add_control($key,array('label'=>$f[0],'type'=>$f[1],'section'=>'adi_personal'));}
});
function adi_page_url($slug){if('writing'===$slug&&get_option('page_for_posts')){return get_permalink((int)get_option('page_for_posts'));}$p=get_page_by_path($slug);return $p?get_permalink($p):home_url('/'.$slug.'/');}
function adi_projects_url(){return get_post_type_archive_link('adi_project')?:home_url('/#work');}
function adi_arrow($direction='up'){$p='down'===$direction?'M12 4v16m-6-6 6 6 6-6':('right'===$direction?'M4 12h16m-6-6 6 6-6 6':'M5 19 19 5M5 5h14v14');return '<svg class="arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="'.$p.'"/></svg>';}
function adi_spark(){return '<svg viewBox="0 0 100 100" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="8" aria-hidden="true"><path d="M50 8v28M50 64v28M8 50h28M64 50h28M20 20l20 20M60 60l20 20M20 80l20-20M60 40l20-20"/></svg>';}
function adi_space_info($slug){$s=array(
 'design-ai'=>array('name'=>'Technology & AI','number'=>'02','copy'=>'An engineering background and an interest in how emerging technologies create value, influence consumers and shape decisions.'),
 'research'=>array('name'=>'Research','number'=>'01','copy'=>'Understanding consumer behaviour through qualitative and quantitative research. My PhD explores AI, pricing and perceptions of fairness.'),
 'startups-business'=>array('name'=>'Business & Innovation','number'=>'03','copy'=>'Two years in a business incubator, applying Lean Startup and Design Thinking to customer discovery, value proposition development and innovation.'));return $s[$slug]??$s['design-ai'];}
function adi_space_url($slug){$t=get_term_by('slug',$slug,'adi_space');if(!$t){return adi_projects_url();}$u=get_term_link($t);return is_wp_error($u)?adi_projects_url():$u;}
function adi_visual($kind,$class=''){get_template_part('template-parts/visual',null,array('kind'=>$kind,'class'=>$class));}
function adi_reading_minutes($content){return max(1,(int)ceil(str_word_count(wp_strip_all_tags($content))/200));}
function adi_filter_bar(){
 $active=is_tax('adi_space')?get_queried_object()->slug:'all'; echo '<nav class="filter-bar" aria-label="Filter projects">';
 foreach(array('all'=>'All work','research'=>'Research','design-ai'=>'Technology & AI','startups-business'=>'Business & Innovation') as $slug=>$name){$u='all'===$slug?adi_projects_url():adi_space_url($slug);echo '<a class="filter '.($slug===$active?'is-active':'').'" data-filter="'.esc_attr($slug).'" href="'.esc_url($u).'" '.($slug===$active?'aria-current="true"':'').'>'.esc_html($name).'</a>'; }echo '</nav>';
}
function adi_publications($use_main_query=false){
 $q=$use_main_query?$GLOBALS['wp_query']:new WP_Query(array('post_type'=>'adi_publication','posts_per_page'=>20)); if(!$q->have_posts()){return;}
 echo '<section class="section publications" id="contributions" aria-labelledby="publication-title"><div class="section-heading"><div><p class="eyebrow">The research record</p><h2 id="publication-title">Scientific contributions.</h2></div><p class="section-note">With Sven Feurer and<br>Elisa Konya-Baumbach.</p></div><p class="publication-intro">My research examines how consumers perceive and respond to AI-driven decisions, with a particular focus on fairness. These questions are increasingly relevant as companies introduce AI into customer-facing products and services.</p><div class="publication-list">';
 while($q->have_posts()){$q->the_post();$u=get_post_meta(get_the_ID(),'adi_source_url',true)?:get_permalink();echo '<article class="publication"><p class="mono">'.esc_html(get_post_meta(get_the_ID(),'adi_year',true)).'</p><div><h3><a href="'.esc_url($u).'">'.esc_html(get_the_title()).'</a></h3><p>'.esc_html(get_the_excerpt()).'</p><span class="small-label">Conference contribution</span></div><a class="icon-link" href="'.esc_url($u).'" aria-label="'.esc_attr('View record: '.get_the_title()).'">'.adi_arrow().'</a></article>';}
 wp_reset_postdata();echo '</div>';if($use_main_query){the_posts_pagination();}else{echo '<a class="text-link" href="'.esc_url(get_post_type_archive_link('adi_publication')).'">All publication records '.adi_arrow().'</a>';}echo '</section>';
}

// Preserve older About links while its editable biography is now presented on Home.
add_action('template_redirect', function() {
 if (is_page('about') && !is_preview() && !is_customize_preview()) {
  wp_safe_redirect(home_url('/#story'), 301);
  exit;
 }
});
