<?php
$about = get_posts(array('post_type'=>'page','post_status'=>'publish','meta_key'=>'adi_seed_key','meta_value'=>'about','posts_per_page'=>1,'fields'=>'ids'));
if (!$about) { $page = get_page_by_path('about'); if ($page && 'publish' === $page->post_status) { $about = array($page->ID); } }
if (!$about) { return; }
?>
<div class="ethos-strip"><span><span aria-hidden="true">◎</span> People first.</span><span><span aria-hidden="true"><?php echo adi_arrow(); ?></span> Evidence always.</span><span><span aria-hidden="true"><?php echo adi_spark(); ?></span> Keep making.</span></div>
<section class="section biography" id="story" aria-labelledby="story-title"><h2 id="story-title" class="eyebrow">The longer story</h2><div class="entry-content about-content"><?php $bio = new WP_Query(array('post_type'=>'page','post_status'=>'publish','p'=>$about[0])); while ($bio->have_posts()) { $bio->the_post(); the_content(); } wp_reset_postdata(); ?></div></section>
