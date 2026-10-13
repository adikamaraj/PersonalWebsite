<?php get_header(); ?>
<main id="main" class="wrap">
<section class="intro-hero" aria-labelledby="intro-title">
 <div class="intro-copy"><p class="eyebrow"><span class="status-dot"></span>Hello, I’m <?php echo esc_html(adi_setting('adi_hero_name')); ?></p>
 <h1 id="intro-title"><?php echo esc_html(adi_setting('adi_about_title')); ?></h1>
 <p class="intro-description"><?php echo esc_html(adi_setting('adi_about_copy')); ?></p>
 <div class="intro-links"><a class="text-link" href="#contributions">Explore my research <?php echo adi_arrow('down'); ?></a><a class="intro-secondary" href="#work">Selected work <?php echo adi_arrow('right'); ?></a></div></div>
 <?php get_template_part('template-parts/intro-map'); ?>
</section>
<div class="context-strip"><p><span class="small-label">Currently exploring</span>AI × price fairness</p><p><span class="small-label">Based in</span><?php echo esc_html(adi_setting('adi_location')); ?></p><a href="#story"><span class="small-label">The path so far</span>India → Germany → Switzerland <?php echo adi_arrow(); ?></a></div>
<section class="section spaces-section" aria-labelledby="spaces-title"><div class="lenses-intro"><div><p class="eyebrow">One mind, three lenses</p><h2 id="spaces-title">Different lenses.<br>Connected thinking.</h2><p class="lenses-copy">Research, technology, and business.<br>I’m interested in where they meet.</p></div><?php get_template_part('template-parts/venn'); ?></div><div class="spaces-grid">
<?php foreach(array('research','design-ai','startups-business') as $slug):$info=adi_space_info($slug); ?><a class="space-card space-<?php echo esc_attr($slug); ?>" href="<?php echo esc_url(adi_space_url($slug)); ?>"><div class="space-card-top"><span class="mono"><?php echo esc_html($info['number']); ?></span><?php echo adi_arrow(); ?></div><h3><?php echo esc_html($info['name']); ?></h3><p><?php echo esc_html($info['copy']); ?></p><span class="space-line" aria-hidden="true"></span></a><?php endforeach; ?>
</div></section>
<?php adi_publications(); ?>
<section class="section work-section" id="work" aria-labelledby="work-title"><div class="section-heading"><div><p class="eyebrow">Selected work</p><h2 id="work-title">Ideas in practice.</h2></div><a class="text-link" href="<?php echo esc_url(adi_projects_url()); ?>">All projects <?php echo adi_arrow(); ?></a></div><div class="project-collection" data-client-filter="true"><?php adi_filter_bar(); ?><p class="sr-only filter-status" aria-live="polite"></p><div class="project-grid">
<?php $projects=new WP_Query(array('post_type'=>'adi_project','posts_per_page'=>6,'orderby'=>array('menu_order'=>'ASC','date'=>'DESC'))); if($projects->have_posts()):while($projects->have_posts()):$projects->the_post();get_template_part('template-parts/project-card');endwhile;wp_reset_postdata();else: ?><p class="empty-state">New projects are taking shape. In the meantime, explore my <a href="<?php echo esc_url(adi_page_url('about')); ?>">background and interests</a>.</p><?php endif; ?>
</div></div></section>
<?php get_template_part('template-parts/biography'); ?>
<section class="section writing-section" aria-labelledby="writing-title"><div class="section-heading"><div><p class="eyebrow">Writing</p><h2 id="writing-title">Thinking out loud.</h2></div><a class="text-link" href="<?php echo esc_url(adi_page_url('writing')); ?>">The writing space <?php echo adi_arrow(); ?></a></div>
<?php $essays=new WP_Query(array('post_type'=>'post','posts_per_page'=>3,'ignore_sticky_posts'=>true));if($essays->have_posts()): ?><div class="essay-list"><?php while($essays->have_posts()):$essays->the_post();get_template_part('template-parts/essay-row');endwhile;wp_reset_postdata(); ?></div><?php else: ?><div class="writing-preview"><span class="writing-asterisk" aria-hidden="true"><?php echo adi_spark(); ?></span><div><h3>AI, business, and the people in between.</h3><p>A space for evidence, observations, and questions worth sitting with. The first notes are taking shape.</p><div class="topic-list"><span>AI & business</span><span>Pricing & fairness</span><span>Building & learning</span></div></div></div><?php endif; ?></section>
</main><?php get_footer(); ?>
